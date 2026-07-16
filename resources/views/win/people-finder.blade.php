@verbatim
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>People Finder</title>
<link rel="stylesheet" href="/odigo/skin.css">
</head>
<body id="pf">
<div class="panel">
  <div class="device">
    <div class="dev-titlebar drag">
      <span class="o-glyph" style="font-size:15px;">&#216;</span><span class="ttl" id="pf-user">ventura</span>
      <span class="win-btns"><span class="nodrag" title="Filter" data-open="filter">&#9776;</span><span class="nodrag">&#8211;</span><span class="nodrag" data-close="pf">&times;</span></span>
    </div>
    <div class="menubar"><span><i>L</i>ogin</span><span><i>V</i>iew</span><span><i>T</i>ools</span><span><i>H</i>elp</span></div>
    <div class="toolstrip" id="pf-tools"></div>
    <div class="pf-screen">
      <div class="pf-title" id="pf-title">People Finder<br>All Topics</div>
      <div class="radar" id="radar"><div class="sweep"></div></div>
      <div class="pf-ctl">
        <div class="oval sm" id="pf-prev">&#9650;</div>
        <div class="oval go" id="pf-go">GO</div>
        <div class="oval sm" id="pf-next">&#9660;</div>
      </div>
    </div>
    <div class="logo-panel">
      <div class="script-logo"><span class="slash-o">o</span>digo</div>
      <div class="dotcom">odigo.com</div>
    </div>
    <div class="pf-bottom"><div class="o-round">&#216;</div></div>
    <div class="side-btns">
      <div class="side-btn globe" title="Filter" data-open="filter"></div>
      <div class="side-btn smiley" title="Status" data-open="status"></div>
      <div class="side-btn note" title="Messages" data-open="communication"></div>
    </div>
  </div>
</div>
<script src="/odigo/bus.js"></script>
<script>
const { $, $$, api, qs, sprite, on, emit, openWindow, closeSelf } = Odigo;
const state = { filters: {}, search: '', page: 1, pages: 1, people: [], current: null };

$('#pf-tools').innerHTML = ['bars','person','two','radio','crowd']
  .map((k,i)=>`<div class="toolbtn${i===2?' lit':''}">${sprite(1.6, i%2?'gold':'orange')}</div>`).join('');

function placePeople(list){
  const radar=$('#radar');
  radar.querySelectorAll('.person').forEach(n=>n.remove());
  const n=list.length, cx=50, cy=50;
  list.forEach((p,i)=>{
    let x,y;
    if(n===1){ x=cx; y=cy; } else {
      const half=Math.ceil(n/2);
      const ring = i<half ? 34 : 20;
      const count = i<half ? half : (n-half);
      const idx = i<half ? i : i-half;
      const ang = (idx/count)*Math.PI*2 - Math.PI/2;
      x = cx + Math.cos(ang)*ring; y = cy + Math.sin(ang)*ring;
    }
    const el=document.createElement('div');
    el.className='person'+(state.current===p.handle?' sel':'');
    el.style.left=x+'%'; el.style.top=y+'%';
    el.innerHTML = sprite(2.4,p.sprite)+`<div class="nm">${p.display_name}</div>`;
    el.title = `${p.display_name} — ${p.topic} (${p.status})  ·  click: details, double-click: message`;
    el.addEventListener('click',()=>{ select(p.handle); openWindow('details'); });
    el.addEventListener('dblclick',()=>{ select(p.handle); openWindow('communication'); });
    radar.appendChild(el);
  });
}
async function search(page=1){
  const data = await api('/odigo/people?'+qs({ ...state.filters, search:state.search, page }));
  state.people=data.people; state.page=data.page; state.pages=data.pages;
  placePeople(data.people);
  const topic = state.filters.topic && state.filters.topic!=='All Topics' ? state.filters.topic : 'All Topics';
  $('#pf-title').innerHTML = `People Finder<br>${topic}`;
}
function select(handle){ state.current=handle; emit('select',{handle}); placePeople(state.people); }

$('#pf-go').addEventListener('click',()=>search(1));
$('#pf-prev').addEventListener('click',()=>{ if(state.page>1) search(state.page-1); });
$('#pf-next').addEventListener('click',()=>{ if(state.page<state.pages) search(state.page+1); });
$$('[data-open]').forEach(b=>b.addEventListener('click',()=>openWindow(b.dataset.open)));
$$('[data-close]').forEach(b=>b.addEventListener('click',()=>closeSelf(b.dataset.close)));

// Filter window drives the radar
on('filters',(p)=>{ state.filters=p.filters||{}; state.search=p.search||''; search(1); });
// keep highlight in sync if selection happens elsewhere
on('select',(p)=>{ state.current=p.handle; placePeople(state.people); });

search(1);
</script>
</body>
</html>
@endverbatim
