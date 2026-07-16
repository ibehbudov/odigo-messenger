@verbatim
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Status</title>
<link rel="stylesheet" href="/odigo/skin.css">
</head>
<body id="yh">
<div class="panel">
  <div class="device">
    <div class="dev-titlebar drag">
      <span class="o-glyph" style="font-size:14px;">&#216;</span><span class="ttl">status</span>
      <span class="win-btns"><span class="nodrag">&#8211;</span><span class="nodrag" data-close="status">&times;</span></span>
    </div>
    <div class="yh-inner">
      <div class="yh-cols">
        <div class="yh-col"><div class="h">People</div><div class="row"><span class="n" id="s-people">0</span><span id="yh-p1"></span></div></div>
        <div class="yh-col"><div class="h">Notes</div><div class="row"><span class="n" id="s-notes">0</span><span id="yh-note"></span></div></div>
        <div class="yh-col"><div class="h">Invisible</div><div class="row"><span class="n" id="s-invis">0</span><span id="yh-p2"></span></div></div>
      </div>
      <div class="yh-handle"><i></i><i></i><i></i></div>
    </div>
  </div>
</div>
<script src="/odigo/bus.js"></script>
<script>
const { $, $$, api, sprite, on, closeSelf } = Odigo;
$('#yh-p1').innerHTML = sprite(2.2,'orange');
$('#yh-p2').innerHTML = sprite(2.2,'orange');
$('#yh-note').innerHTML = '<svg width="16" height="19" viewBox="0 0 16 19"><path d="M2 1 H14 V14 L10 18 H2 Z" fill="#f0b428" stroke="#7a5200" stroke-width="1"/><path d="M14 14 L10 14 L10 18" fill="#c88a10" stroke="#7a5200" stroke-width="1"/><line x1="4.5" y1="5" x2="11.5" y2="5" stroke="#7a5200" stroke-width="1.2"/><line x1="4.5" y1="8" x2="11.5" y2="8" stroke="#7a5200" stroke-width="1.2"/></svg>';
async function load(){ const s=await api('/odigo/stats'); $('#s-people').textContent=s.people; $('#s-notes').textContent=s.notes; $('#s-invis').textContent=s.invisible; }
on('stats-changed',load);
$$('[data-close]').forEach(b=>b.addEventListener('click',()=>closeSelf(b.dataset.close)));
load(); setInterval(load, 8000);
</script>
</body>
</html>
@endverbatim
