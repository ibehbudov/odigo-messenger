@verbatim
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Odigo</title>
<style>
  *{box-sizing:border-box;}
  html,body{margin:0;height:100%;}
  body{
    font-family:Tahoma,"MS Sans Serif",Geneva,sans-serif;
    overflow:hidden;background:#000;user-select:none;
  }
  #desktop{
    position:fixed;inset:0;overflow:hidden;
    background:linear-gradient(135deg,#c2c5c9 0%,#909499 48%,#55585c 100%);
  }
  #poster-title{position:absolute;left:24px;top:12px;font-size:30px;font-weight:900;
    color:#111;font-family:'Arial Black',Arial,sans-serif;opacity:.35;pointer-events:none;}

  .win{position:absolute;z-index:10;}
  .win.dragging{opacity:.97;}
  .drag{cursor:move;}
  .hidden{display:none !important;}

  /* ============ ODIGO DEVICE SKIN (blue plastic) ============ */
  .device{
    border-radius:18px;
    background:
      linear-gradient(180deg,rgba(255,255,255,.35),rgba(255,255,255,0) 10%),
      linear-gradient(105deg,#2e4a78 0%,#4a6ba0 10%,#5d7fb4 32%,#4f72a8 62%,#33517f 100%);
    border:1px solid #16294a;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.55),inset 1px 0 0 rgba(255,255,255,.3),
      inset -2px -3px 6px rgba(0,0,0,.45),0 8px 20px rgba(0,0,0,.5);
  }
  .dev-titlebar{display:flex;align-items:center;gap:6px;height:24px;padding:2px 10px 0;
    color:#eaf2fc;font-size:12px;font-weight:bold;text-shadow:0 1px 1px rgba(0,0,0,.55);}
  .dev-titlebar .ttl{flex:1;}
  .o-glyph{font-style:italic;font-weight:bold;font-family:Georgia,serif;}
  .dev-titlebar .btns,.blue-titlebar .btns{display:flex;gap:8px;font-weight:bold;color:#cfe0f4;font-size:12px;}
  .dev-titlebar .btns span,.blue-titlebar .btns span{cursor:pointer;}
  .dev-titlebar .btns span:hover,.blue-titlebar .btns span:hover{color:#fff;}

  /* ============ PEOPLE FINDER ============ */
  #pf{left:430px;top:60px;width:300px;}
  #pf .device{height:600px;display:flex;flex-direction:column;padding:0 9px 10px;position:relative;}
  .menubar{display:flex;gap:14px;font-size:12px;color:#eaf2fc;padding:1px 4px 4px;
    text-shadow:0 1px 1px rgba(0,0,0,.5);}
  .menubar span i{font-style:normal;text-decoration:underline;}
  .toolstrip{display:flex;gap:3px;justify-content:center;padding:3px 4px;border-radius:6px;
    background:linear-gradient(180deg,#27406b,#35578a);
    box-shadow:inset 0 2px 3px rgba(0,0,0,.5),inset 0 -1px 0 rgba(255,255,255,.2);border:1px solid #1b2f52;}
  .toolbtn{width:42px;height:24px;border-radius:4px;display:flex;align-items:center;justify-content:center;cursor:pointer;
    background:linear-gradient(180deg,#6e8fbe,#46699c);border:1px solid #243d66;box-shadow:inset 0 1px 0 rgba(255,255,255,.45);}
  .toolbtn.lit{background:linear-gradient(180deg,#8fb0da,#5b80b4);}
  .pf-screen{flex:1;margin-top:6px;border-radius:12px;position:relative;
    background:linear-gradient(180deg,#4a6da8,#3d5f9a 60%,#34548c);
    box-shadow:inset 0 2px 5px rgba(0,0,0,.5),inset 0 0 0 1px #243d66;padding:6px 8px;display:flex;flex-direction:column;}
  .pf-title{text-align:center;color:#fff;font-weight:bold;font-size:15px;line-height:1.2;text-shadow:1px 1px 1px rgba(0,0,0,.6);}
  .radar{position:relative;margin:6px auto 0;width:230px;height:230px;border-radius:50%;
    background:radial-gradient(circle at 50% 40%,#16244a 0%,#101b3a 55%,#0a1228 100%);
    box-shadow:inset 0 3px 10px rgba(0,0,0,.8),0 1px 0 rgba(255,255,255,.25),inset 0 0 0 1px #000;overflow:hidden;}
  .radar::before{content:"";position:absolute;inset:10px;border-radius:50%;border:1px solid rgba(110,150,200,.3);}
  .sweep{position:absolute;inset:0;border-radius:50%;pointer-events:none;
    background:conic-gradient(from 0deg,rgba(90,220,120,.35),rgba(90,220,120,0) 60deg);
    animation:spin 4s linear infinite;}
  @keyframes spin{to{transform:rotate(360deg);}}
  .person{position:absolute;text-align:center;transform:translate(-50%,-50%);cursor:pointer;z-index:2;}
  .person .nm{font-size:9px;color:#fff;margin-top:-1px;white-space:nowrap;font-family:Tahoma,sans-serif;}
  .person:hover .nm{color:#9feaff;}
  .person.sel .nm{color:#ffe27a;font-weight:bold;}
  .pf-ctl{display:flex;align-items:center;gap:6px;margin-top:auto;padding:8px 2px 2px;}
  .oval{height:20px;border-radius:10px;
    background:linear-gradient(180deg,#dfe6ee,#9aa7b8 50%,#7e8da1);
    border:1px solid #2c3a52;box-shadow:inset 0 1px 0 #fff,0 1px 2px rgba(0,0,0,.5);
    color:#23344e;font-weight:bold;font-size:10px;text-align:center;
    display:flex;align-items:center;justify-content:center;cursor:pointer;}
  .oval.sm{width:34px;}
  .oval.go{flex:1;font-size:11px;letter-spacing:1px;}
  .oval:active{background:linear-gradient(180deg,#c7d0da,#8592a4 50%,#6b7889);}
  .logo-panel{margin-top:7px;height:74px;border-radius:10px;position:relative;overflow:hidden;margin-right:36px;
    background:repeating-linear-gradient(115deg,rgba(255,255,255,.07) 0 2px,rgba(255,255,255,0) 2px 7px,rgba(0,0,0,.18) 7px 9px,rgba(0,0,0,0) 9px 14px),
      linear-gradient(115deg,#3a3f4a 0%,#23272f 55%,#15181f 100%);
    box-shadow:inset 0 2px 6px rgba(0,0,0,.7),inset 0 0 0 1px #000,0 1px 0 rgba(255,255,255,.25);}
  .script-logo{position:absolute;left:50%;top:44%;transform:translate(-50%,-50%) rotate(-4deg);
    font-family:'Brush Script MT','Segoe Script',cursive;font-size:44px;color:#e8edf4;font-style:italic;
    text-shadow:2px 2px 2px rgba(0,0,0,.8),0 0 1px #fff;white-space:nowrap;}
  .script-logo .slash-o{position:relative;display:inline-block;}
  .script-logo .slash-o::after{content:"";position:absolute;left:-6px;right:-2px;top:52%;height:3px;
    background:#e8edf4;transform:rotate(-38deg);border-radius:2px;box-shadow:1px 1px 1px rgba(0,0,0,.7);}
  .logo-panel .dotcom{position:absolute;right:12px;bottom:6px;color:#fff;font-size:13px;font-weight:bold;text-shadow:1px 1px 1px #000;}
  .pf-bottom{display:flex;align-items:flex-end;gap:7px;margin-top:7px;}
  .o-round{width:34px;height:34px;border-radius:50%;flex:0 0 auto;
    background:radial-gradient(circle at 35% 28%,#7d9fcc,#3a5c92 60%,#26406e);
    border:1px solid #142848;box-shadow:inset 0 2px 2px rgba(255,255,255,.5),0 2px 3px rgba(0,0,0,.5);
    color:#dbe8f8;font-size:22px;font-style:italic;font-family:Georgia,serif;font-weight:bold;text-align:center;line-height:32px;}
  .side-btns{position:absolute;right:10px;bottom:44px;display:flex;flex-direction:column;gap:8px;}
  .side-btn{width:26px;height:26px;border-radius:50%;border:1px solid #142848;cursor:pointer;
    box-shadow:inset 0 2px 2px rgba(255,255,255,.55),0 2px 3px rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;}
  .side-btn.globe{background:radial-gradient(circle at 35% 28%,#7ee08a,#1f8f3a 65%,#0d5c22);}
  .side-btn.smiley{background:radial-gradient(circle at 35% 28%,#ffe27a,#e0a312 65%,#9a6c04);}
  .side-btn.note{background:radial-gradient(circle at 35% 28%,#f5d98a,#caa53c 65%,#8a6c1a);}

  /* ============ FILTER PANEL ============ */
  #filter{left:70px;top:120px;width:320px;}
  .slab{border-radius:8px;background:linear-gradient(160deg,#3a4252 0%,#2b3340 40%,#1f2630 100%);
    border:1px solid #0d1118;box-shadow:inset 0 1px 0 rgba(255,255,255,.22),inset -1px -2px 4px rgba(0,0,0,.6),0 8px 20px rgba(0,0,0,.5);
    padding:8px 12px 12px;}
  #filter .slab{display:flex;flex-direction:column;}
  .filt-titlebar{height:24px;border-radius:6px;margin-bottom:9px;position:relative;
    background:linear-gradient(180deg,#5e87c2 0%,#33598f 55%,#27497c 100%);
    border:1px solid #14264a;box-shadow:inset 0 1px 0 rgba(255,255,255,.5);
    color:#fff;font-weight:bold;font-size:13px;text-align:center;line-height:22px;text-shadow:1px 1px 1px rgba(0,0,0,.6);}
  .filt-titlebar .grip{position:absolute;right:6px;top:0;font-size:11px;color:#cfe0f4;}
  .filt-search{display:flex;align-items:center;height:30px;border-radius:8px;margin-bottom:9px;padding:3px;gap:5px;
    background:linear-gradient(180deg,#1a2230,#252e3e);box-shadow:inset 0 2px 3px rgba(0,0,0,.6),0 1px 0 rgba(255,255,255,.15);border:1px solid #10151e;}
  .filt-search .sicon{width:26px;height:22px;border-radius:5px;flex:0 0 auto;
    background:linear-gradient(180deg,#5e87c2,#2e5288);border:1px solid #14264a;box-shadow:inset 0 1px 0 rgba(255,255,255,.45);
    display:flex;align-items:center;justify-content:center;}
  .filt-search input{flex:1;background:transparent;border:0;outline:0;color:#dce6f2;font-size:12px;font-weight:bold;padding-left:4px;font-family:inherit;}
  .filt-search input::placeholder{color:#7f8ea3;}
  .filt-rows{display:flex;flex-direction:column;gap:6px;flex:1;}
  .frow{display:flex;align-items:stretch;height:26px;border-radius:7px;overflow:hidden;border:1px solid #10151e;
    box-shadow:0 1px 0 rgba(255,255,255,.15),inset 0 1px 0 rgba(255,255,255,.2);}
  .frow .lbl{flex:0 0 104px;background:linear-gradient(180deg,#4b5a6e,#39455a);
    color:#fff;font-size:11px;font-weight:bold;display:flex;align-items:center;padding:0 10px;
    text-shadow:1px 1px 1px rgba(0,0,0,.6);border-right:1px solid #141a24;}
  .frow .val{flex:1;background:linear-gradient(180deg,#161c28,#222b3a);box-shadow:inset 0 2px 3px rgba(0,0,0,.5);position:relative;}
  .frow select{width:100%;height:100%;background:transparent;border:0;outline:0;color:#c4d2e4;font-size:11px;
    padding:0 8px;font-family:inherit;cursor:pointer;-webkit-appearance:none;appearance:none;}
  .frow select option{background:#222b3a;color:#dce6f2;}
  .filt-foot{display:flex;align-items:center;gap:7px;margin-top:10px;}
  .filt-foot .sq{width:34px;height:22px;border-radius:6px;cursor:pointer;
    background:linear-gradient(180deg,#39455a,#222b3a);border:1px solid #10151e;box-shadow:inset 0 1px 0 rgba(255,255,255,.25);
    display:flex;align-items:center;justify-content:center;font-size:11px;color:#7fb2f0;}
  .filt-foot .spacer{flex:1;}
  .filt-foot .oval{width:92px;}

  /* ============ DETAILS ============ */
  #det{left:760px;top:60px;width:520px;}
  .blue-titlebar{height:22px;display:flex;align-items:center;padding:0 8px;gap:6px;border-radius:3px 3px 0 0;
    background:linear-gradient(90deg,#1a4d9e 0%,#3f7ad0 60%,#5b95e4 100%);color:#fff;font-weight:bold;font-size:12px;text-shadow:1px 1px 1px rgba(0,0,0,.5);}
  .blue-titlebar .ttl{flex:1;}
  .navy-win{background:linear-gradient(180deg,#33517f,#243a5e);border:1px solid #0d1626;border-radius:4px;
    box-shadow:0 8px 20px rgba(0,0,0,.5),inset 0 1px 0 rgba(255,255,255,.3);padding:3px;}
  .det-header{background:linear-gradient(180deg,#0c1422,#101c30);color:#fff;font-size:14px;font-weight:bold;line-height:1.35;
    padding:8px 12px;border-bottom:1px solid #000;}
  .det-body{display:flex;gap:12px;padding:12px;background:linear-gradient(180deg,#101a2c,#0b1322);}
  .neon-strip{flex:0 0 74px;border-radius:12px;align-self:stretch;
    background:linear-gradient(105deg,#1b2330,#0c1018 70%);border:1px solid #2c3a52;
    box-shadow:inset 0 0 18px rgba(0,0,0,.9),inset 0 1px 0 rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;}
  .neon{display:flex;flex-direction:column;gap:2px;align-items:center;}
  .neon b{font-family:Arial,sans-serif;font-size:34px;font-weight:900;line-height:.92;color:rgba(10,30,50,.15);
    -webkit-text-stroke:2px #9feaff;text-shadow:0 0 6px #2fb9f0,0 0 14px #1487d8,0 0 26px #0a5cb0;}
  .det-panel{flex:1;border-radius:10px;padding:10px;background:linear-gradient(180deg,#2c4260,#1d3048);
    border:1px solid #0c1626;box-shadow:inset 0 1px 0 rgba(255,255,255,.2);display:flex;flex-direction:column;gap:9px;}
  .det-card{border-radius:8px;padding:8px 10px;display:flex;gap:12px;align-items:center;
    background:linear-gradient(180deg,#22364f,#16273c);border:1px solid #0a141f;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.15),0 1px 2px rgba(0,0,0,.4);}
  .det-ic{width:46px;height:46px;border-radius:6px;flex:0 0 auto;border:2px solid #44608a;
    box-shadow:0 1px 3px rgba(0,0,0,.5),inset 0 0 0 1px #0a141f;display:flex;align-items:center;justify-content:center;font-size:24px;}
  .det-fields{flex:1;display:flex;flex-direction:column;gap:5px;}
  .det-q{font-size:12px;font-weight:bold;color:#fff;text-shadow:1px 1px 1px rgba(0,0,0,.6);}
  .pillfield{height:22px;border-radius:11px;display:flex;align-items:center;
    background:linear-gradient(180deg,#0e1828,#1a2c44);border:1px solid #000;
    box-shadow:inset 0 2px 3px rgba(0,0,0,.7),0 1px 0 rgba(255,255,255,.18);font-size:11px;padding:0 12px;justify-content:space-between;}
  .pillfield .fl{color:#cfe0f4;font-weight:bold;}
  .pillfield .fv{color:#74e860;font-weight:bold;letter-spacing:.5px;white-space:nowrap;}
  .det-foot{display:flex;gap:8px;justify-content:flex-end;padding:10px 12px 9px;background:linear-gradient(180deg,#0b1322,#0d1626);}
  .det-btn{min-width:84px;height:24px;border-radius:3px;cursor:pointer;
    background:linear-gradient(180deg,#3c5a86,#26405f);border:1px solid #0a141f;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.35),0 1px 2px rgba(0,0,0,.4);
    color:#fff;font-size:12px;font-weight:bold;text-align:center;line-height:22px;text-shadow:1px 1px 1px rgba(0,0,0,.5);}
  .det-btn.dis{color:#7e92ac;cursor:default;}

  /* ============ COMMUNICATION CENTER ============ */
  #cc{left:150px;top:470px;width:560px;}
  .w2k{background:#c6d0de;border:1px solid #3a4a5e;border-radius:2px;
    box-shadow:0 8px 20px rgba(0,0,0,.5),inset 0 0 0 1px #eef2f8;padding:3px;}
  .w2k .blue-titlebar{margin:-1px -1px 0;}
  .cc-body{padding:7px 8px 4px;}
  .cc-row{display:flex;align-items:center;gap:8px;}
  .cc-sendto{font-weight:bold;font-size:13px;color:#101820;display:flex;align-items:center;gap:7px;flex:1;}
  .w95btn{background:#cdd6e2;border:1px solid #1c2836;box-shadow:inset 1px 1px 0 #fff,inset -1px -1px 0 #7b8a9e;
    font-size:11px;color:#101820;text-align:center;cursor:pointer;height:21px;line-height:19px;padding:0 10px;}
  .w95btn:active{box-shadow:inset -1px -1px 0 #fff,inset 1px 1px 0 #7b8a9e;}
  .w95btn u{text-decoration:underline;}
  .cc-id{font-size:11px;color:#101820;margin:4px 0 2px;}
  .cc-icons{display:flex;gap:5px;align-items:center;}
  .led{width:14px;height:14px;border-radius:50%;background:radial-gradient(circle at 35% 30%,#a8f59a,#1f9b22 65%,#0c5c10);
    border:1px solid #0a3a0c;box-shadow:inset 0 1px 1px rgba(255,255,255,.7);}
  .led.off{background:radial-gradient(circle at 35% 30%,#e0a0a0,#9b1f1f 65%,#5c0c0c);border-color:#3a0a0a;}
  .cc-ic{width:18px;height:17px;border:1px solid #8a96a6;background:#dde4ee;box-shadow:inset 1px 1px 0 #fff;
    display:flex;align-items:center;justify-content:center;font-size:11px;}
  .cc-tabs{display:flex;margin-top:6px;padding-left:2px;}
  .cc-tab{padding:3px 14px 4px;font-size:11px;color:#101820;position:relative;cursor:pointer;
    background:#b7c2d2;border:1px solid #1c2836;border-bottom:none;border-radius:4px 4px 0 0;
    box-shadow:inset 1px 1px 0 #e8eef6;margin-right:2px;top:2px;}
  .cc-tab.on{background:#cdd6e2;top:0;padding-top:5px;z-index:2;font-weight:bold;}
  .cc-page{border:1px solid #1c2836;box-shadow:inset 1px 1px 0 #e8eef6;background:#cdd6e2;padding:6px 7px;position:relative;z-index:1;}
  .cc-toolbar{display:flex;align-items:center;gap:5px;margin-bottom:6px;}
  .combo{height:20px;background:#fff;border:1px solid #1c2836;box-shadow:inset 1px 1px 0 #7b8a9e;
    font-size:11px;color:#101820;display:flex;align-items:center;padding:0 2px 0 5px;gap:5px;}
  .combo .ar{width:15px;height:14px;background:#cdd6e2;border:1px solid #1c2836;box-shadow:inset 1px 1px 0 #fff;
    font-size:8px;text-align:center;line-height:13px;margin-left:auto;}
  .tgl{width:20px;height:20px;background:#cdd6e2;border:1px solid #9aa6b6;box-shadow:inset 1px 1px 0 #fff;
    display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:bold;color:#101820;cursor:pointer;}
  .tgl.on{box-shadow:inset -1px -1px 0 #fff,inset 1px 1px 0 #7b8a9e;background:#b7c2d2;}
  .cc-text{height:96px;background:#fff;border:1px solid #1c2836;box-shadow:inset 1px 1px 2px rgba(0,0,0,.3);
    margin-bottom:7px;width:100%;resize:none;outline:0;font-family:inherit;font-size:12px;padding:5px 6px;color:#101820;}
  .cc-log{height:96px;overflow-y:auto;background:#fff;border:1px solid #1c2836;box-shadow:inset 1px 1px 2px rgba(0,0,0,.3);
    margin-bottom:7px;padding:4px 6px;font-size:11px;color:#101820;}
  .cc-log .m{margin:2px 0;}
  .cc-log .m .who{font-weight:bold;}
  .cc-log .m.out .who{color:#1a4d9e;}
  .cc-log .m.in .who{color:#a52020;}
  .cc-foot{display:flex;align-items:flex-end;gap:7px;}
  .cc-foot .grow{flex:1;}
  .mascot{width:46px;height:42px;border:1px solid #1c2836;box-shadow:inset 1px 1px 0 #7b8a9e;
    background:linear-gradient(160deg,#e8e2d6,#c9bda6);display:flex;align-items:center;justify-content:center;overflow:hidden;}
  .cc-status{font-size:11px;color:#101820;margin-top:5px;padding:2px 6px;border:1px solid #9aa6b6;
    box-shadow:inset 1px 1px 0 #7b8a9e,inset -1px -1px 0 #fff;background:#c6d0de;}

  /* ============ SEND WIDGET ============ */
  #send{left:70px;top:660px;width:220px;height:96px;}
  #send .outer{width:100%;height:100%;border-radius:6px;padding:6px;cursor:pointer;
    background:linear-gradient(160deg,#6b4438,#3a2018 60%,#241008);border:1px solid #120804;
    box-shadow:0 6px 16px rgba(0,0,0,.5),inset 0 1px 0 rgba(255,255,255,.25);display:flex;align-items:center;}
  #send .inner{flex:1;height:100%;border-radius:4px;display:flex;align-items:center;gap:10px;padding:5px 8px;
    background:linear-gradient(105deg,#2c4a7c,#1a2c52 70%,#101c3a);border:1px solid #0a1326;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.25),inset 0 -2px 5px rgba(0,0,0,.6);}
  .online-tab{align-self:stretch;display:flex;align-items:center;justify-content:center;flex:0 0 18px;
    writing-mode:vertical-rl;transform:rotate(180deg);background:linear-gradient(90deg,#1f8f2a,#5ddc4a);
    color:#04330a;font-weight:bold;font-size:10px;letter-spacing:1px;border-radius:3px;border:1px solid #0d4d06;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.5);}
  .online-tab.off{background:linear-gradient(90deg,#8f1f1f,#dc4a4a);border-color:#4d0606;color:#330404;}
  .send-stack{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;}
  .send-word{font-family:'Arial Black',Arial,sans-serif;font-size:17px;font-weight:900;font-style:italic;
    color:#e23a2a;letter-spacing:1px;text-shadow:1px 1px 0 #6b0e06,0 0 4px rgba(255,80,50,.5);}
  .send-env{width:34px;height:23px;background:linear-gradient(180deg,#ffd84a,#d89e08);border-radius:2px;
    border:1px solid #7a5a00;position:relative;box-shadow:0 1px 2px rgba(0,0,0,.5);}
  .send-env::after{content:"";position:absolute;left:1px;right:1px;top:1px;
    border-top:10px solid #ffeb9a;border-left:15px solid transparent;border-right:15px solid transparent;}
  .send-logo{width:54px;height:54px;border-radius:50%;flex:0 0 auto;position:relative;
    background:radial-gradient(circle at 35% 28%,#4a6ba0,#1c3258 60%,#0e1c38);border:2px solid #5d7fb4;
    box-shadow:inset 0 2px 3px rgba(255,255,255,.3),0 2px 4px rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;}
  .send-logo .script-logo{position:static;transform:rotate(-6deg);font-size:20px;}

  /* ============ YAHOO MINI ============ */
  #yh{left:330px;top:660px;width:250px;}
  #yh .device{border-radius:10px;padding:0 7px 6px;}
  #yh .dev-titlebar{height:22px;font-size:12px;}
  .yh-inner{border-radius:8px;padding:7px 9px 4px;background:linear-gradient(180deg,#101a30,#1a2848);
    box-shadow:inset 0 2px 4px rgba(0,0,0,.7),0 1px 0 rgba(255,255,255,.2);border:1px solid #0a1226;}
  .yh-cols{display:flex;}
  .yh-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;color:#fff;}
  .yh-col .h{font-size:11px;font-weight:bold;text-shadow:1px 1px 1px rgba(0,0,0,.7);}
  .yh-col .row{display:flex;align-items:flex-end;gap:5px;}
  .yh-col .n{font-size:14px;font-weight:bold;color:#ffe27a;}
  .yh-handle{display:flex;justify-content:center;gap:4px;padding-top:4px;}
  .yh-handle i{width:7px;height:4px;border-radius:2px;background:#e09018;display:block;box-shadow:0 1px 1px rgba(0,0,0,.6);}
  .px{image-rendering:pixelated;}

  /* ============ TASKBAR ============ */
  #taskbar{position:fixed;left:0;right:0;bottom:0;height:32px;z-index:9999;display:flex;align-items:center;gap:6px;padding:0 8px;
    background:linear-gradient(180deg,#4a6da8,#26406e);border-top:1px solid #6f93c8;box-shadow:0 -2px 8px rgba(0,0,0,.4);}
  #taskbar .brand{font-family:'Brush Script MT','Segoe Script',cursive;font-style:italic;font-size:22px;color:#eaf2fc;
    padding:0 10px;text-shadow:1px 1px 2px rgba(0,0,0,.6);}
  .tb-btn{height:22px;padding:0 12px;line-height:20px;font-size:11px;color:#eaf2fc;cursor:pointer;border-radius:4px;
    background:linear-gradient(180deg,#5e87c2,#33598f);border:1px solid #1b2f52;box-shadow:inset 0 1px 0 rgba(255,255,255,.35);}
  .tb-btn.active{background:linear-gradient(180deg,#8fb0da,#4f72a8);font-weight:bold;}
  #tb-clock{margin-left:auto;color:#eaf2fc;font-size:11px;padding:0 10px;}
</style>
</head>
<body>
<div id="desktop">
  <div id="poster-title">odigo</div>

  <!-- ============ FILTER PANEL ============ -->
  <div class="win" id="filter" data-name="Filter">
    <div class="slab">
      <div class="filt-titlebar drag" data-win="filter">People Finder Filter<span class="grip">&#10097;&#10097;</span></div>
      <div class="filt-search">
        <div class="sicon"><svg width="14" height="14" viewBox="0 0 14 14"><circle cx="7" cy="7" r="5.5" fill="#2a72c8" stroke="#cfe4fa" stroke-width="1.2"/><path d="M1.5 7 H12.5 M7 1.5 C4 4 4 10 7 12.5 C10 10 10 4 7 1.5" stroke="#cfe4fa" stroke-width="1" fill="none"/></svg></div>
        <input id="f-search" type="text" placeholder="Search topic or name…">
      </div>
      <div class="filt-rows" id="filt-rows"></div>
      <div class="filt-foot">
        <div class="sq" title="Reset filters" id="f-reset">&#8635;</div>
        <div class="sq" title="Random pick" id="f-random">&#10033;</div>
        <div class="spacer"></div>
        <div class="oval" style="width:92px;" id="f-go">GO</div>
      </div>
    </div>
  </div>

  <!-- ============ PEOPLE FINDER ============ -->
  <div class="win" id="pf" data-name="People Finder">
    <div class="device">
      <div class="dev-titlebar drag" data-win="pf">
        <span class="o-glyph" style="font-size:15px;">&#216;</span><span class="ttl" id="pf-user">ventura</span>
        <span class="btns"><span>?</span><span>&#8211;</span><span data-close="pf">&times;</span></span>
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
        <div class="side-btn globe" title="Worldwide"></div>
        <div class="side-btn smiley" title="Mood"></div>
        <div class="side-btn note" title="Notes"></div>
      </div>
    </div>
  </div>

  <!-- ============ DETAILS ============ -->
  <div class="win navy-win hidden" id="det" data-name="Details">
    <div class="blue-titlebar drag" data-win="det"><span class="ttl" id="det-title">Details</span>
      <span class="btns"><span>&#8211;</span><span data-close="det">&times;</span></span></div>
    <div class="det-header" id="det-header">Here's the profile — it's confidential, no one's getting personal.</div>
    <div class="det-body">
      <div class="neon-strip"><div class="neon"><b>O</b><b>D</b><b>I</b><b>G</b><b>O</b></div></div>
      <div class="det-panel" id="det-panel"></div>
    </div>
    <div class="det-foot">
      <div class="det-btn" style="margin-right:auto;" data-close="det">Close</div>
      <div class="det-btn" id="det-msg">Message</div>
      <div class="det-btn" id="det-friend">Add Friend</div>
    </div>
  </div>

  <!-- ============ COMMUNICATION CENTER ============ -->
  <div class="win w2k" id="cc" data-name="Communication Center">
    <div class="blue-titlebar drag" data-win="cc">
      <span class="o-glyph" style="font-size:14px;">&#216;</span><span class="ttl">Communication Center</span>
      <span class="btns"><span>&#8211;</span><span data-close="cc">&times;</span></span>
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
          <div class="tgl"><svg width="12" height="12" viewBox="0 0 12 12"><rect x="0" y="0" width="6" height="6" fill="#d01818"/><rect x="6" y="0" width="6" height="6" fill="#1850c8"/><rect x="0" y="6" width="6" height="6" fill="#18a028"/><rect x="6" y="6" width="6" height="6" fill="#e8c818"/></svg></div>
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
      <div class="cc-status" id="cc-status">Compose a Message</div>
    </div>
  </div>

  <!-- ============ SEND WIDGET ============ -->
  <div class="win" id="send" data-name="Send">
    <div class="outer" id="send-outer">
      <div class="inner">
        <div class="online-tab" id="send-tab">online</div>
        <div class="send-stack"><div class="send-word">SEND</div><div class="send-env"></div></div>
        <div class="send-logo"><div class="script-logo"><span class="slash-o">o</span>digo</div></div>
      </div>
    </div>
  </div>

  <!-- ============ YAHOO MINI ============ -->
  <div class="win" id="yh" data-name="Status">
    <div class="device">
      <div class="dev-titlebar drag" data-win="yh">
        <span class="o-glyph" style="font-size:14px;">&#216;</span><span class="ttl">status</span>
        <span class="btns"><span>&#8211;</span><span data-close="yh">&times;</span></span>
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
</div>

<div id="taskbar">
  <div class="brand">odigo</div>
  <div class="tb-btn" data-toggle="pf">People Finder</div>
  <div class="tb-btn" data-toggle="filter">Filter</div>
  <div class="tb-btn" data-toggle="cc">Communication</div>
  <div class="tb-btn" data-toggle="det">Details</div>
  <div class="tb-btn" data-toggle="yh">Status</div>
  <div id="tb-clock"></div>
</div>

<script>
const ME = { handle:'ventura', id:'ventura@odigo.im' };
const $ = (s,r=document)=>r.querySelector(s);
const $$ = (s,r=document)=>Array.from(r.querySelectorAll(s));
const state = { filters:{}, options:{}, page:1, pages:1, people:[], current:null, msgType:'Message', online:true };

/* ---------- pixel-art doll sprite (Odigo style) ---------- */
const SPR = ["..eee..","e.eee.e","..eee..","...b...","..bbb..",".bbbbb.","bbbbbbb",".s...s.",".s...s."];
const PAL = {
  gold:['#f2b81e','#b07a08'], orange:['#f09a28','#a85e0a'], blue:['#7db0e6','#2f5f9c'],
  green:['#7ee08a','#1f8f3a'], pink:['#f28bb8','#b04a7a']
};
function sprite(px, key){
  const [body,dark] = PAL[key] || PAL.gold; let out='';
  for(let y=0;y<SPR.length;y++) for(let x=0;x<SPR[y].length;x++){
    const c=SPR[y][x]; if(c==='.') continue;
    const fill = c==='e'?body : (c==='b'?body:dark);
    out += `<rect x="${x*px}" y="${y*px}" width="${px}" height="${px}" fill="${fill}"/>`;
  }
  const w=7*px,h=9*px;
  return `<svg class="px" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}" shape-rendering="crispEdges" style="filter:drop-shadow(1px 1px 0 rgba(0,0,0,.55));">${out}</svg>`;
}

/* ---------- toolbar + status sprites ---------- */
(function(){
  const kinds=['bars','person','two','radio','crowd'];
  $('#pf-tools').innerHTML = kinds.map((k,i)=>`<div class="toolbtn${i===2?' lit':''}">${sprite(1.6, i%2?'gold':'orange')}</div>`).join('');
  $('#yh-p1').innerHTML = sprite(2.2,'orange');
  $('#yh-p2').innerHTML = sprite(2.2,'orange');
  $('#yh-note').innerHTML = '<svg width="16" height="19" viewBox="0 0 16 19"><path d="M2 1 H14 V14 L10 18 H2 Z" fill="#f0b428" stroke="#7a5200" stroke-width="1"/><path d="M14 14 L10 14 L10 18" fill="#c88a10" stroke="#7a5200" stroke-width="1"/><line x1="4.5" y1="5" x2="11.5" y2="5" stroke="#7a5200" stroke-width="1.2"/><line x1="4.5" y1="8" x2="11.5" y2="8" stroke="#7a5200" stroke-width="1.2"/></svg>';
})();

/* ---------- API ---------- */
async function api(url, opts){
  const r = await fetch(url, opts);
  if(!r.ok) throw new Error(url+' -> '+r.status);
  return r.json();
}
const qs = o => Object.entries(o).filter(([,v])=>v!=null&&v!=='').map(([k,v])=>`${k}=${encodeURIComponent(v)}`).join('&');

/* ---------- filter panel ---------- */
const FILTER_LABELS = [
  ['topic','Topic'],['ageGroup','Age Group'],['gender','Gender'],['region','Region'],
  ['language','Language'],['occupation','Occupation'],['status','Status'],
  ['mood','Mood'],['intention','Intention'],['zodiac','Zodiac']
];
async function loadFilters(){
  state.options = await api('/odigo/filters');
  $('#filt-rows').innerHTML = FILTER_LABELS.map(([key,label])=>{
    const opts=(state.options[key]||[]).map(o=>`<option value="${o}">${o}</option>`).join('');
    return `<div class="frow"><div class="lbl">${label}</div><div class="val"><select data-key="${key}">${opts}</select></div></div>`;
  }).join('');
  $$('#filt-rows select').forEach(sel=>sel.addEventListener('change',()=>{ state.filters[sel.dataset.key]=sel.value; }));
}

/* ---------- radar ---------- */
function placePeople(list){
  const radar=$('#radar');
  radar.querySelectorAll('.person').forEach(n=>n.remove());
  const n=list.length, cx=50, cy=50;
  list.forEach((p,i)=>{
    let x,y;
    if(n===1){ x=cx; y=cy; }
    else {
      const ring = i< Math.ceil(n/2) ? 34 : 20;
      const count = i< Math.ceil(n/2) ? Math.ceil(n/2) : Math.floor(n/2);
      const idx = i< Math.ceil(n/2) ? i : i-Math.ceil(n/2);
      const ang = (idx/count)*Math.PI*2 - Math.PI/2;
      x = cx + Math.cos(ang)*ring; y = cy + Math.sin(ang)*ring;
    }
    const el=document.createElement('div');
    el.className='person'+(state.current&&state.current.handle===p.handle?' sel':'');
    el.style.left=x+'%'; el.style.top=y+'%';
    el.innerHTML = sprite(2.4,p.sprite)+`<div class="nm">${p.display_name}</div>`;
    el.title = `${p.display_name} — ${p.topic} (${p.status})`;
    el.addEventListener('click',()=>selectPerson(p.handle));
    radar.appendChild(el);
  });
}
async function search(page=1){
  const q = qs({ ...state.filters, search:$('#f-search').value.trim(), page });
  const data = await api('/odigo/people?'+q);
  state.people=data.people; state.page=data.page; state.pages=data.pages;
  placePeople(data.people);
  const topic = state.filters.topic && state.filters.topic!=='All Topics' ? state.filters.topic : 'All Topics';
  $('#pf-title').innerHTML = `People Finder<br>${topic}`;
  setStatus(`${data.total} people found · page ${data.page}/${data.pages}`);
}

/* ---------- details ---------- */
async function selectPerson(handle){
  const p = await api('/odigo/person/'+encodeURIComponent(handle));
  state.current = p;
  $$('#radar .person').forEach(el=>el.classList.remove('sel'));
  // set communication target
  $('#cc-to').textContent = p.display_name;
  $('#cc-id').textContent = 'ID: '+p.odigo_id;
  $('#cc-led').className = 'led'+(p.status==='Invisible'||p.status==='Away'?' off':'');
  renderDetails(p);
  placePeople(state.people);
  setStatus('Selected '+p.display_name);
}
function renderDetails(p){
  $('#det-title').textContent = p.display_name+"'s Details";
  const rows = [
    ['&#8987;', 'My age is (6-120)', 'Age', `${p.ageRange}  (${p.age})`],
    ['&#9792;', 'Please indicate', 'Gender', p.gender],
    ['&#127758;', 'Where are you from?', 'Region', `${p.region}`],
    ['&#128172;', 'Language', 'Speaks', p.language],
    ['&#127775;', 'Zodiac', 'Sign', p.zodiac],
    ['&#128188;', 'Occupation', 'Works as', p.occupation],
    ['&#9834;', 'Interested in', 'Topic', `${p.topic} · ${p.intention}`],
    ['&#128512;', 'Current mood', 'Mood', `${p.mood} · ${p.status}`],
  ];
  $('#det-panel').innerHTML = rows.map(([ic,q,fl,fv])=>`
    <div class="det-card">
      <div class="det-ic" style="background:linear-gradient(170deg,#bfe2f5,#1c5a96);">${ic}</div>
      <div class="det-fields"><div class="det-q">${q}</div>
        <div class="pillfield"><span class="fl">${fl}</span><span class="fv">${fv}</span></div></div>
    </div>`).join('');
  $('#det-header').textContent = p.tagline ? '“'+p.tagline+'”' : "Here's the profile — confidential, no one's getting personal.";
  $('#det-friend').textContent = p.is_friend ? '✓ Friend' : 'Add Friend';
}

/* ---------- communication center ---------- */
function setStatus(t){ $('#cc-status').textContent=t; }
async function sendMessage(){
  if(!state.current){ setStatus('Pick someone in the People Finder first'); return; }
  const body=$('#cc-text').value.trim();
  if(!body){ setStatus('Nothing to send'); return; }
  const res = await api('/odigo/messages',{ method:'POST', headers:jsonHeaders(), body:JSON.stringify({to:state.current.handle, body, type:state.msgType}) });
  $('#cc-text').value='';
  setStatus(res.status);
  loadStats();
  if(!$('#cc-log').classList.contains('hidden')) showHistory();
}
async function showHistory(){
  if(!state.current){ setStatus('Pick someone first'); return; }
  const data = await api('/odigo/messages/'+encodeURIComponent(state.current.handle));
  const log=$('#cc-log');
  log.innerHTML = data.messages.length ? data.messages.map(m=>`
    <div class="m ${m.direction}"><span class="who">${m.direction==='out'?ME.handle:state.current.display_name}:</span> ${escapeHtml(m.body)} <span style="color:#8a96a6">${m.time}</span></div>`).join('')
    : '<div class="m">No messages yet — say hi!</div>';
  log.scrollTop=log.scrollHeight;
  $('#cc-text').classList.add('hidden'); log.classList.remove('hidden');
  $('#cc-compose-toggle').style.display='';
  setStatus('History with '+state.current.display_name);
}
function showCompose(){
  $('#cc-log').classList.add('hidden'); $('#cc-text').classList.remove('hidden');
  $('#cc-compose-toggle').style.display='none';
  setStatus('Compose a '+state.msgType);
}
async function addFriend(){
  if(!state.current){ setStatus('Pick someone first'); return; }
  const res = await api('/odigo/friends',{ method:'POST', headers:jsonHeaders(), body:JSON.stringify({handle:state.current.handle}) });
  state.current.is_friend=true; $('#det-friend').textContent='✓ Friend';
  setStatus(res.status); loadStats();
}
function jsonHeaders(){ return {'Content-Type':'application/json','Accept':'application/json'}; }
function escapeHtml(s){ return s.replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

/* ---------- stats ---------- */
async function loadStats(){
  const s = await api('/odigo/stats');
  $('#s-people').textContent=s.people; $('#s-notes').textContent=s.notes; $('#s-invis').textContent=s.invisible;
}

/* ---------- window mgmt: drag, close, toggle, focus ---------- */
let zTop=10;
function focusWin(win){ win.style.zIndex=(++zTop); }
$$('.win').forEach(win=>{ win.addEventListener('mousedown',()=>focusWin(win)); });
$$('.drag').forEach(handle=>{
  handle.addEventListener('mousedown',e=>{
    const win=handle.closest('.win'); focusWin(win); win.classList.add('dragging');
    const r=win.getBoundingClientRect(); const ox=e.clientX-r.left, oy=e.clientY-r.top;
    function move(ev){ win.style.left=Math.max(0,ev.clientX-ox)+'px'; win.style.top=Math.max(0,ev.clientY-oy)+'px'; }
    function up(){ win.classList.remove('dragging'); document.removeEventListener('mousemove',move); document.removeEventListener('mouseup',up); syncTaskbar(); }
    document.addEventListener('mousemove',move); document.addEventListener('mouseup',up);
    e.preventDefault();
  });
});
$$('[data-close]').forEach(b=>b.addEventListener('click',e=>{ e.stopPropagation(); $('#'+b.dataset.close).classList.add('hidden'); syncTaskbar(); }));
$$('#taskbar [data-toggle]').forEach(b=>b.addEventListener('click',()=>{
  const win=$('#'+b.dataset.toggle);
  if(win.classList.contains('hidden')){ win.classList.remove('hidden'); focusWin(win); }
  else focusWin(win);
  syncTaskbar();
}));
function syncTaskbar(){ $$('#taskbar [data-toggle]').forEach(b=>{
  const win=$('#'+b.dataset.toggle); b.classList.toggle('active', !win.classList.contains('hidden')); }); }

/* ---------- wire controls ---------- */
$('#f-go').addEventListener('click',()=>search(1));
$('#pf-go').addEventListener('click',()=>search(1));
$('#f-search').addEventListener('keydown',e=>{ if(e.key==='Enter') search(1); });
$('#f-reset').addEventListener('click',()=>{ state.filters={}; $('#f-search').value=''; $$('#filt-rows select').forEach(s=>s.selectedIndex=0); search(1); });
$('#f-random').addEventListener('click',()=>{ if(state.people.length){ selectPerson(state.people[Math.floor(Math.random()*state.people.length)].handle); openWin('cc'); } });
$('#pf-prev').addEventListener('click',()=>{ if(state.page>1) search(state.page-1); });
$('#pf-next').addEventListener('click',()=>{ if(state.page<state.pages) search(state.page+1); });
$('#cc-send').addEventListener('click',sendMessage);
$('#cc-cancel').addEventListener('click',()=>{ $('#cc-text').value=''; setStatus('Cancelled'); });
$('#cc-history').addEventListener('click',showHistory);
$('#cc-compose-toggle').addEventListener('click',showCompose);
$('#cc-addfriend').addEventListener('click',addFriend);
$('#cc-details').addEventListener('click',()=>{ if(state.current){ renderDetails(state.current); openWin('det'); } else setStatus('Pick someone first'); });
$('#det-msg').addEventListener('click',()=>openWin('cc'));
$('#det-friend').addEventListener('click',addFriend);
$$('#cc-tabs .cc-tab').forEach(t=>t.addEventListener('click',()=>{
  $$('#cc-tabs .cc-tab').forEach(x=>x.classList.remove('on')); t.classList.add('on');
  state.msgType=t.dataset.type; showCompose();
}));
$$('.tgl').forEach(t=>t.addEventListener('click',()=>t.classList.toggle('on')));
$('#send-outer').addEventListener('click',()=>{
  state.online=!state.online;
  $('#send-tab').textContent=state.online?'online':'offline';
  $('#send-tab').classList.toggle('off',!state.online);
  setStatus('You are now '+(state.online?'online':'offline'));
});
function openWin(id){ const w=$('#'+id); w.classList.remove('hidden'); focusWin(w); syncTaskbar(); }

/* ---------- clock ---------- */
function tick(){ const d=new Date(); $('#tb-clock').textContent=d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}); }
setInterval(tick,1000); tick();

/* ---------- boot ---------- */
(async function boot(){
  $('#pf-user').textContent=ME.handle;
  try{
    await loadFilters();
    await search(1);
    await loadStats();
    syncTaskbar();
    // preselect the first friend to populate the Communication Center
    const friend = state.people[0];
    if(friend) selectPerson(friend.handle);
  }catch(err){ setStatus('Error: '+err.message); console.error(err); }
})();
</script>
</body>
</html>
@endverbatim
