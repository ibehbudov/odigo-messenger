<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Odigo</title>
<link rel="stylesheet" href="/odigo/skin.css">
</head>
<body>
<div id="desktop">
  <div id="poster-title">odigo</div>
  @foreach ($windows as $name => $c)
    <div class="frame" id="frame-{{ $name }}"
         style="left:{{ $c['x'] }}px;top:{{ $c['y'] }}px;width:{{ $c['w'] }}px;height:{{ $c['h'] }}px;">
      <div class="grab" data-frame="{{ $name }}"></div>
      <iframe src="/w/{{ $name }}" title="{{ $c['title'] }}"></iframe>
    </div>
  @endforeach
</div>

<div id="taskbar">
  <div class="brand">odigo</div>
  <div class="tb-btn" data-toggle="people-finder">People Finder</div>
  <div class="tb-btn" data-toggle="filter">Filter</div>
  <div class="tb-btn" data-toggle="communication">Communication</div>
  <div class="tb-btn" data-toggle="details">Details</div>
  <div class="tb-btn" data-toggle="status">Status</div>
  <div class="tb-btn" data-toggle="send">Send</div>
  <div id="tb-clock"></div>
</div>

@verbatim
<script>
const $ = (s,r=document)=>r.querySelector(s);
const $$ = (s,r=document)=>Array.from(r.querySelectorAll(s));
let zTop=10;
function frame(id){ return $('#frame-'+id); }
function focusFrame(f){ f.style.zIndex=(++zTop); }
function openFrame(id){ const f=frame(id); if(!f) return; f.classList.remove('hidden'); focusFrame(f); sync(); }
function closeFrame(id){ const f=frame(id); if(f){ f.classList.add('hidden'); sync(); } }
function sync(){ $$('#taskbar [data-toggle]').forEach(b=>{ const f=frame(b.dataset.toggle); b.classList.toggle('active', f && !f.classList.contains('hidden')); }); }

// drag frames by their grab strip
$$('.frame .grab').forEach(g=>{
  g.addEventListener('mousedown',e=>{
    const f=g.closest('.frame'); focusFrame(f);
    const r=f.getBoundingClientRect(); const ox=e.clientX-r.left, oy=e.clientY-r.top;
    const move=ev=>{ f.style.left=Math.max(0,ev.clientX-ox)+'px'; f.style.top=Math.max(0,ev.clientY-oy)+'px'; };
    const up=()=>{ document.removeEventListener('mousemove',move); document.removeEventListener('mouseup',up); };
    document.addEventListener('mousemove',move); document.addEventListener('mouseup',up); e.preventDefault();
  });
});
// bring a frame to front when its iframe is interacted with
window.addEventListener('message',e=>{
  const d=e.data||{};
  if(d.odigo==='open') openFrame(d.panel);
  else if(d.odigo==='close') closeFrame(d.id);
});
$$('#taskbar [data-toggle]').forEach(b=>b.addEventListener('click',()=>{
  const f=frame(b.dataset.toggle);
  if(f.classList.contains('hidden')) openFrame(b.dataset.toggle); else focusFrame(f);
  sync();
}));

function tick(){ $('#tb-clock').textContent=new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}); }
setInterval(tick,1000); tick(); sync();
</script>
@endverbatim
</body>
</html>
