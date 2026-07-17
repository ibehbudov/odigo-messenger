/* ============================================================
   Odigo shared runtime — used by every panel window.
   Works in THREE environments:
     - Wails v3 desktop  : each panel is its own native window.
                           Cross-window sync via the Wails event bus
                           (window.wails.Events), which broadcasts to all
                           windows. Window open/close is delegated to Go via
                           'odigo:win-open' / 'odigo:win-close' events.
     - Web shell (iframe): panels run inside iframes of the desktop shell.
                           Cross-window via BroadcastChannel; open/close via
                           postMessage to the parent shell.
     - Plain browser tab : falls back to BroadcastChannel.
   All data goes to the Go backend at API_BASE over HTTP (CORS-enabled).
============================================================ */
(function () {
  const API_BASE = 'https://api-odigo.your.team';

  const W = window.wails;
  const DESKTOP = !!(W && W.Events);            // running inside the Wails webview
  const WEB_IFRAME = !DESKTOP && (window.self !== window.top);

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

  /* ---- cross-window bus (select / filters / stats-changed) ---- */
  const EV = (t) => 'odigo:' + t;
  const handlers = {};
  const seen = new Set();                       // nonce dedup (avoid self echo)
  const nonce = () => Math.random().toString(36).slice(2) + Date.now();
  const bc = (!DESKTOP && 'BroadcastChannel' in window) ? new BroadcastChannel('odigo') : null;

  function deliver(type, payload) { (handlers[type] || []).forEach((cb) => cb(payload)); }

  function on(type, cb) {
    (handlers[type] = handlers[type] || []).push(cb);
    if (DESKTOP) W.Events.On(EV(type), (ev) => {
      const m = ev && ev.data;
      if (m && m._id && seen.has(m._id)) return; // already delivered locally
      deliver(type, m ? m.payload : undefined);
    });
  }
  function emit(type, payload) {
    const id = nonce();
    seen.add(id); setTimeout(() => seen.delete(id), 5000);
    deliver(type, payload);                      // local, immediate
    const msg = { type, payload, _id: id };
    if (DESKTOP) W.Events.Emit(EV(type), msg);
    else if (bc) bc.postMessage(msg);
  }
  if (bc) bc.onmessage = (e) => {
    const m = e.data || {};
    if (m._id && seen.has(m._id)) return;
    deliver(m.type, m.payload);
  };

  /* ---- window control ---- */
  function openWindow(panel) {
    if (DESKTOP) { W.Events.Emit('odigo:win-open', panel); return; }
    if (WEB_IFRAME) { window.parent.postMessage({ odigo: 'open', panel }, '*'); return; }
  }
  function closeSelf(id) {
    if (DESKTOP) { W.Events.Emit('odigo:win-close', id); return; }
    if (WEB_IFRAME) { window.parent.postMessage({ odigo: 'close', id }, '*'); return; }
  }

  window.Odigo = {
    API_BASE, DESKTOP, WEB: WEB_IFRAME,
    $, $$, api, qs, jsonHeaders, escapeHtml, sprite, on, emit, openWindow, closeSelf,
    ME: { handle: 'ventura', id: 'ventura@odigo.im' }
  };
})();
