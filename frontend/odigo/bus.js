/* ============================================================
   Odigo shared runtime — used by every panel window.
   Cross-window sync + window control:
     - Wails v3 desktop  : detected via window._wails. Uses a SAME-ORIGIN control
                           channel to the app's own asset server — fetch /cmd/*
                           (handled in desktop/main.go). Window open/close/login are
                           direct commands; select/filters/stats are stored in Go and
                           windows POLL /cmd/state. This avoids the Wails JS<->Go event
                           transport entirely (reliable everywhere).
     - Web shell (iframe): BroadcastChannel + postMessage to the parent shell.
   Data goes to the Go backend at API_BASE over HTTP (CORS-enabled).
============================================================ */
(function () {
  const API_BASE = 'https://api-odigo.your.team';

  // Desktop detection must be RACE-FREE: window._wails is injected by the Wails core
  // but may not be present yet when bus.js runs. The page URL is reliable — the web
  // build is served from *.your.team, the desktop app from a custom scheme
  // (wails://localhost etc.). So: not a your.team host => desktop app.
  const WEB_HOST = /(^|\.)your\.team$/i.test(location.hostname);
  const DESKTOP = !WEB_HOST;
  const WEB_IFRAME = WEB_HOST && (window.self !== window.top);

  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

  async function api(path, opts) {
    const url = /^https?:/.test(path) ? path : API_BASE + path;
    const r = await fetch(url, opts);
    if (!r.ok) throw new Error(path + ' -> ' + r.status);
    return r.json();
  }
  const qs = (o) => Object.entries(o)
    .filter(([, v]) => v != null && v !== '')
    .map(([k, v]) => `${k}=${encodeURIComponent(v)}`).join('&');
  const jsonHeaders = () => ({ 'Content-Type': 'application/json', 'Accept': 'application/json' });
  const escapeHtml = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

  /* ---- pixel-art doll sprite ---- */
  const SPR = ['..eee..', 'e.eee.e', '..eee..', '...b...', '..bbb..', '.bbbbb.', 'bbbbbbb', '.s...s.', '.s...s.'];
  const PAL = {
    gold: ['#f2b81e', '#b07a08'], orange: ['#f09a28', '#a85e0a'], blue: ['#7db0e6', '#2f5f9c'],
    green: ['#7ee08a', '#1f8f3a'], pink: ['#f28bb8', '#b04a7a']
  };
  function sprite(px, key) {
    const [body, dark] = PAL[key] || PAL.gold; let out = '';
    for (let y = 0; y < SPR.length; y++) for (let x = 0; x < SPR[y].length; x++) {
      const c = SPR[y][x]; if (c === '.') continue;
      const fill = c === 's' ? dark : body;
      out += `<rect x="${x * px}" y="${y * px}" width="${px}" height="${px}" fill="${fill}"/>`;
    }
    const w = 7 * px, h = 9 * px;
    return `<svg class="px" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}" shape-rendering="crispEdges" style="filter:drop-shadow(1px 1px 0 rgba(0,0,0,.55));">${out}</svg>`;
  }

  /* ---- local + cross-window event delivery ---- */
  const handlers = {};
  function deliver(type, payload) { (handlers[type] || []).forEach((cb) => cb(payload)); }
  function on(type, cb) { (handlers[type] = handlers[type] || []).push(cb); }

  // ===== DIAGNOSTICS (temporary) — phone home + on-screen overlay =====
  const DBG = [];
  function paintOverlay() {
    let el = document.getElementById('__odbg');
    if (!el) {
      if (!document.body) return;
      el = document.createElement('div');
      el.id = '__odbg';
      el.style.cssText = 'position:fixed;left:0;right:0;bottom:0;z-index:99999;background:rgba(0,0,0,.8);color:#0f0;font:9px/1.25 monospace;padding:2px 4px;max-height:30px;overflow:hidden;white-space:nowrap;pointer-events:none;';
      document.body.appendChild(el);
    }
    el.textContent = 'D=' + DESKTOP + ' ' + (DBG.slice(-2).join(' ¦ '));
  }
  function dbg(m) {
    DBG.push(m);
    try { fetch(API_BASE + '/odigo/dbg?m=' + encodeURIComponent(location.pathname + ' | ' + m)); } catch (e) {}
    if (document.readyState !== 'loading') paintOverlay();
    else document.addEventListener('DOMContentLoaded', paintOverlay, { once: true });
  }
  dbg('LOAD desktop=' + DESKTOP + ' _wails=' + (typeof window._wails) + ' wails=' + (typeof window.wails));

  // ---- DESKTOP: same-origin /cmd control channel + /cmd/state polling ----
  async function cmd(path, opts) {
    dbg('cmd-> ' + path);
    try {
      const r = await fetch(location.origin + '/cmd/' + path, opts);
      dbg('cmd<- ' + path + ' status=' + r.status);
      return r;
    } catch (e) { dbg('cmd!! ' + path + ' err=' + e); }
  }

  if (DESKTOP) {
    let lastRev = -1, lastSel = null, lastFilters = null, lastStats = null;
    async function poll() {
      try {
        const r = await fetch(location.origin + '/cmd/state', { cache: 'no-store' });
        const s = await r.json();
        if (s.rev !== lastRev) {
          if (s.selection && s.selection !== lastSel) deliver('select', { handle: s.selection });
          if (s.filters !== lastFilters && s.filters) { try { deliver('filters', JSON.parse(s.filters)); } catch (e) {} }
          if (s.statsRev !== lastStats && lastStats !== null) deliver('stats-changed');
          lastRev = s.rev; lastSel = s.selection; lastFilters = s.filters; lastStats = s.statsRev;
        }
      } catch (e) {}
    }
    setInterval(poll, 400); poll();
  }

  // ---- WEB: BroadcastChannel + postMessage ----
  const seen = new Set();
  const nonce = () => Math.random().toString(36).slice(2) + Date.now();
  const bc = (!DESKTOP && 'BroadcastChannel' in window) ? new BroadcastChannel('odigo') : null;
  if (bc) bc.onmessage = (e) => {
    const m = e.data || {};
    if (m._id && seen.has(m._id)) return;
    deliver(m.type, m.payload);
  };

  function emit(type, payload) {
    deliver(type, payload); // immediate local
    if (DESKTOP) {
      if (type === 'select' && payload && payload.handle) cmd('select/' + encodeURIComponent(payload.handle));
      else if (type === 'filters') cmd('filters', { method: 'POST', body: JSON.stringify(payload || {}) });
      else if (type === 'stats-changed') cmd('stats');
      return;
    }
    if (bc) { const id = nonce(); seen.add(id); setTimeout(() => seen.delete(id), 5000); bc.postMessage({ type, payload, _id: id }); }
  }

  /* ---- window control ---- */
  function openWindow(panel) {
    dbg('openWindow ' + panel);
    if (DESKTOP) { cmd('open/' + panel); return; }
    if (WEB_IFRAME) { window.parent.postMessage({ odigo: 'open', panel }, '*'); return; }
  }
  function closeSelf(id) {
    dbg('closeSelf ' + id);
    if (DESKTOP) { cmd('close/' + id); return; }
    if (WEB_IFRAME) { window.parent.postMessage({ odigo: 'close', id }, '*'); return; }
  }
  function signalLogin() {
    dbg('signalLogin');
    if (DESKTOP) { cmd('login'); return; }
    if (WEB_IFRAME) { window.parent.postMessage({ odigo: 'login' }, '*'); return; }
  }

  window.Odigo = {
    API_BASE, DESKTOP, WEB: WEB_IFRAME, dbg,
    $, $$, api, qs, jsonHeaders, escapeHtml, sprite, on, emit, openWindow, closeSelf, signalLogin,
    ME: { handle: 'ventura', id: 'ventura@odigo.im' }
  };
  // Desktop self-test on launch (no click needed): if the login window can reach
  // /cmd and open the Status window on its own, the /cmd channel works and the only
  // remaining suspect is click delivery. Runs only in the login window.
  if (DESKTOP && location.pathname.indexOf('login') !== -1) {
    setTimeout(async () => {
      dbg('SELFTEST begin');
      try { const r = await fetch(location.origin + '/cmd/state'); dbg('SELFTEST /cmd/state=' + r.status + ' body=' + (await r.text()).slice(0, 60)); } catch (e) { dbg('SELFTEST /cmd/state ERR=' + e); }
      await cmd('open/status');   // should pop the Status window if /cmd works
      dbg('SELFTEST end (Status window should have appeared)');
    }, 2500);
  }
})();
