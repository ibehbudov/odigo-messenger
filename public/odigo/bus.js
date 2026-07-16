/* ============================================================
   Odigo shared runtime — used by every panel window.
   - api(): JSON helpers against /odigo/*
   - sprite(): retro pixel doll
   - BroadcastChannel bus: cross-window sync (works across native
     Electron windows AND across same-origin browser iframes)
   - window control: desktop -> Laravel routes (Window facade);
     web (inside the shell iframe) -> postMessage to the parent shell
============================================================ */
(function () {
  const WEB = window.self !== window.top; // inside the web shell iframe

  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

  async function api(url, opts) {
    const r = await fetch(url, opts);
    if (!r.ok) throw new Error(url + ' -> ' + r.status);
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

  /* ---- cross-window bus ---- */
  const bus = ('BroadcastChannel' in window) ? new BroadcastChannel('odigo') : null;
  const handlers = {};
  function on(type, cb) { (handlers[type] = handlers[type] || []).push(cb); }
  function emit(type, payload) {
    const msg = { type, payload };
    if (bus) bus.postMessage(msg);
    // also deliver locally (BroadcastChannel does not echo to sender)
    (handlers[type] || []).forEach((cb) => cb(payload));
  }
  if (bus) bus.onmessage = (e) => {
    const { type, payload } = e.data || {};
    (handlers[type] || []).forEach((cb) => cb(payload));
  };

  /* ---- window control ---- */
  function openWindow(panel) {
    if (WEB) { window.parent.postMessage({ odigo: 'open', panel }, '*'); return; }
    api('/odigo/win/open', { method: 'POST', headers: jsonHeaders(), body: JSON.stringify({ panel }) }).catch(() => {});
  }
  function closeSelf(id) {
    if (WEB) { window.parent.postMessage({ odigo: 'close', id }, '*'); return; }
    api('/odigo/win/close', { method: 'POST', headers: jsonHeaders(), body: JSON.stringify({ id }) }).catch(() => {});
  }

  window.Odigo = { WEB, $, $$, api, qs, jsonHeaders, escapeHtml, sprite, on, emit, openWindow, closeSelf, ME: { handle: 'ventura', id: 'ventura@odigo.im' } };
})();
