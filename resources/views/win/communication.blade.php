@verbatim
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Communication Center</title>
<link rel="stylesheet" href="/odigo/skin.css">
</head>
<body>
<div class="panel">
  <div class="w2k">
    <div class="blue-titlebar drag">
      <span class="o-glyph" style="font-size:14px;">&#216;</span><span class="ttl">Communication Center</span>
      <span class="win-btns"><span class="nodrag">&#8211;</span><span class="nodrag" data-close="communication">&times;</span></span>
    </div>
    <div class="cc-body">
      <div class="cc-row">
        <div class="cc-sendto">
          <svg width="22" height="16" viewBox="0 0 22 16"><rect x="0.5" y="0.5" width="21" height="15" rx="1" fill="#ffd84a" stroke="#7a5a00"/><path d="M1 1 L11 9 L21 1" fill="none" stroke="#7a5a00" stroke-width="1.2"/></svg>
          Send to: <span id="cc-to">—</span>
        </div>
        <div class="w95btn" id="cc-history"><u>H</u>istory…</div>
        <div class="w95btn" id="cc-details"><u>D</u>etails…</div>
      </div>
      <div class="cc-row" style="justify-content:space-between;margin-top:2px;">
        <div class="cc-id" id="cc-id">ID: —</div>
        <div class="cc-icons">
          <span class="led" id="cc-led"></span>
          <span class="cc-ic" style="color:#d01818;">&#9829;</span>
          <span class="cc-ic"><svg width="13" height="13" viewBox="0 0 13 13"><circle cx="6.5" cy="6.5" r="5.5" fill="#e23a2a" stroke="#7a0e06"/><circle cx="4.5" cy="5" r=".9" fill="#fff"/><circle cx="8.5" cy="5" r=".9" fill="#fff"/><path d="M4 9.5 Q6.5 7.5 9 9.5" stroke="#fff" stroke-width="1" fill="none"/></svg></span>
        </div>
      </div>
      <div class="cc-tabs" id="cc-tabs">
        <div class="cc-tab on" data-type="Message">Message</div>
        <div class="cc-tab" data-type="Chat request">Chat request</div>
        <div class="cc-tab" data-type="URL">URL</div>
        <div class="cc-tab" data-type="File">File</div>
      </div>
      <div class="cc-page">
        <div class="cc-toolbar">
          <div class="combo" style="width:150px;">Arial<span class="ar">&#9660;</span></div>
          <div class="combo" style="width:52px;">10<span class="ar">&#9660;</span></div>
          <div class="tgl"><b>B</b></div>
          <div class="tgl"><i>I</i></div>
          <div class="tgl"><span style="text-decoration:underline;">U</span></div>
          <div class="tgl"><svg width="12" height="12" viewBox="0 0 12 12"><rect width="6" height="6" fill="#d01818"/><rect x="6" width="6" height="6" fill="#1850c8"/><rect y="6" width="6" height="6" fill="#18a028"/><rect x="6" y="6" width="6" height="6" fill="#e8c818"/></svg></div>
        </div>
        <textarea class="cc-text" id="cc-text" placeholder="Type a message…"></textarea>
        <div class="cc-log hidden" id="cc-log"></div>
        <div class="cc-foot">
          <div class="w95btn" id="cc-compose-toggle" style="display:none;">Compose</div>
          <div class="w95btn" id="cc-addfriend">Add Friend</div>
          <div class="grow"></div>
          <div class="w95btn" id="cc-cancel">Cancel</div>
          <div class="w95btn" style="font-weight:bold;" id="cc-send"><u>S</u>end</div>
          <div class="mascot">
            <svg width="36" height="34" viewBox="0 0 36 34"><path d="M4 4 Q1 2 2 8 Q3 13 8 13 M32 4 Q35 2 34 8 Q33 13 28 13" fill="none" stroke="#5a2a18" stroke-width="2.4"/><ellipse cx="18" cy="17" rx="11" ry="12" fill="#8a4a2a"/><ellipse cx="18" cy="24" rx="6.5" ry="5" fill="#d8a878"/><circle cx="14" cy="14" r="1.6" fill="#241008"/><circle cx="22" cy="14" r="1.6" fill="#241008"/></svg>
          </div>
        </div>
      </div>
      <div class="cc-status" id="cc-status">Pick someone in the People Finder</div>
    </div>
  </div>
</div>
<script src="/odigo/bus.js"></script>
<script>
const { $, $$, api, jsonHeaders, escapeHtml, on, emit, openWindow, closeSelf, ME } = Odigo;
let current=null, msgType='Message';
function setStatus(t){ $('#cc-status').textContent=t; }

async function setTarget(handle){
  const p = await api('/odigo/person/'+encodeURIComponent(handle));
  current=p;
  $('#cc-to').textContent=p.display_name;
  $('#cc-id').textContent='ID: '+p.odigo_id;
  $('#cc-led').className='led'+(p.status==='Invisible'||p.status==='Away'?' off':'');
  setStatus('Compose a '+msgType+' to '+p.display_name);
  if(!$('#cc-log').classList.contains('hidden')) showHistory();
}
async function send(){
  if(!current){ setStatus('Pick someone first'); return; }
  const body=$('#cc-text').value.trim(); if(!body){ setStatus('Nothing to send'); return; }
  const res=await api('/odigo/messages',{method:'POST',headers:jsonHeaders(),body:JSON.stringify({to:current.handle,body,type:msgType})});
  $('#cc-text').value=''; setStatus(res.status); emit('stats-changed');
  if(!$('#cc-log').classList.contains('hidden')) showHistory();
}
async function showHistory(){
  if(!current){ setStatus('Pick someone first'); return; }
  const data=await api('/odigo/messages/'+encodeURIComponent(current.handle));
  const log=$('#cc-log');
  log.innerHTML = data.messages.length ? data.messages.map(m=>`
    <div class="m ${m.direction}"><span class="who">${m.direction==='out'?ME.handle:current.display_name}:</span> ${escapeHtml(m.body)} <span style="color:#8a96a6">${m.time}</span></div>`).join('')
    : '<div class="m">No messages yet — say hi!</div>';
  log.scrollTop=log.scrollHeight;
  $('#cc-text').classList.add('hidden'); log.classList.remove('hidden'); $('#cc-compose-toggle').style.display='';
  setStatus('History with '+current.display_name);
}
function showCompose(){ $('#cc-log').classList.add('hidden'); $('#cc-text').classList.remove('hidden'); $('#cc-compose-toggle').style.display='none'; setStatus('Compose a '+msgType); }

on('select',(p)=>{ if(p&&p.handle) setTarget(p.handle); });
$('#cc-send').addEventListener('click',send);
$('#cc-cancel').addEventListener('click',()=>{ $('#cc-text').value=''; setStatus('Cancelled'); });
$('#cc-history').addEventListener('click',showHistory);
$('#cc-compose-toggle').addEventListener('click',showCompose);
$('#cc-details').addEventListener('click',()=>openWindow('details'));
$('#cc-addfriend').addEventListener('click',async()=>{
  if(!current) return;
  const res=await api('/odigo/friends',{method:'POST',headers:jsonHeaders(),body:JSON.stringify({handle:current.handle})});
  current.is_friend=true; setStatus(res.status); emit('stats-changed');
});
$$('#cc-tabs .cc-tab').forEach(t=>t.addEventListener('click',()=>{ $$('#cc-tabs .cc-tab').forEach(x=>x.classList.remove('on')); t.classList.add('on'); msgType=t.dataset.type; showCompose(); }));
$$('.tgl').forEach(t=>t.addEventListener('click',()=>t.classList.toggle('on')));
$$('[data-close]').forEach(b=>b.addEventListener('click',()=>closeSelf(b.dataset.close)));
</script>
</body>
</html>
@endverbatim
