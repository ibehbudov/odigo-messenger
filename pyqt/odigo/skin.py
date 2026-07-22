"""Retro skin as Qt Style Sheets — a translation of frontend/odigo/skin.css.

Qt Style Sheets are NOT CSS: there are no `.class` selectors, no box-shadow,
no conic-gradient. So elements are targeted by `#objectName` and by a dynamic
`cls` property (`widget[cls="oval"]`), set via skin.tag(). Shadows are dropped
or approximated with borders; the radar sweep is painted by hand in panels.py.
"""
from PyQt6.QtWidgets import QWidget


def tag(w: QWidget, cls: str) -> QWidget:
    """Mark a widget so the `[cls="..."]` QSS rules below apply to it."""
    w.setProperty("cls", cls)
    return w


def flag(w: QWidget, name: str, on: bool = True) -> QWidget:
    """Toggle a boolean-ish state property (e.g. lit/on/off) and re-polish so
    the matching `[name="1"]` QSS takes effect immediately."""
    w.setProperty(name, "1" if on else "0")
    s = w.style()
    s.unpolish(w)
    s.polish(w)
    w.update()
    return w


_DEVICE = ("qlineargradient(x1:0,y1:0,x2:1,y2:1,"
           "stop:0 #2e4a78, stop:0.10 #4a6ba0, stop:0.32 #5d7fb4,"
           "stop:0.62 #4f72a8, stop:1 #33517f)")
_SLAB = ("qlineargradient(x1:0,y1:0,x2:1,y2:1,"
         "stop:0 #3a4252, stop:0.4 #2b3340, stop:1 #1f2630)")
_NAVY = "qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #33517f, stop:1 #243a5e)"

GLOBAL_QSS = f"""
* {{ font-family: "Tahoma", "DejaVu Sans", sans-serif; }}
QToolTip {{ color:#101820; background:#e8eef6; border:1px solid #1c2836; }}

/* ---- blue plastic device (login / people-finder / status) ---- */
#device {{ background: {_DEVICE}; border:1px solid #16294a; border-radius:16px; }}
#devTitle {{ color:#eaf2fc; font-weight:bold; font-size:12px; }}
QLabel[cls="oglyph"] {{ color:#eaf2fc; font-family:"Georgia",serif; font-style:italic; font-weight:bold; }}

/* ---- login ---- */
#loginLogo {{ font-family:"URW Chancery L","Comic Sans MS",cursive; font-style:italic;
    font-size:48px; color:#e8edf4; }}
QLabel[cls="lgLabel"] {{ color:#cfe0f4; font-size:11px; font-weight:bold; }}
QLineEdit[cls="lgInput"] {{
    background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #1a2230, stop:1 #252e3e);
    border:1px solid #10151e; border-radius:6px; padding:0 10px; color:#eaf2fc; min-height:24px; }}
#lgHint {{ color:#7f8ea3; font-size:10px; }}

/* ---- oval pill buttons ---- */
QPushButton[cls="oval"] {{
    background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #dfe6ee, stop:0.5 #9aa7b8, stop:1 #7e8da1);
    border:1px solid #2c3a52; border-radius:10px; color:#23344e; font-weight:bold; font-size:11px; min-height:20px; }}
QPushButton[cls="oval"]:pressed {{
    background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #c7d0da, stop:0.5 #8592a4, stop:1 #6b7889); }}

/* ---- people finder ---- */
#pfScreen {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #4a6da8, stop:0.6 #3d5f9a, stop:1 #34548c);
    border:1px solid #243d66; border-radius:12px; }}
#pfTitle {{ color:#fff; font-weight:bold; font-size:15px; }}
#toolstrip {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #27406b, stop:1 #35578a);
    border:1px solid #1b2f52; border-radius:6px; }}
QLabel[cls="toolbtn"] {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #6e8fbe, stop:1 #46699c);
    border:1px solid #243d66; border-radius:4px; }}
QLabel[cls="toolbtn"][lit="1"] {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #8fb0da, stop:1 #5b80b4); }}
QLabel[cls="menubar"] {{ color:#eaf2fc; font-size:12px; }}
#logoPanel {{ background: qlineargradient(x1:0,y1:0,x2:1,y2:1, stop:0 #3a3f4a, stop:0.55 #23272f, stop:1 #15181f);
    border-radius:10px; }}
#scriptLogo {{ font-family:"URW Chancery L","Comic Sans MS",cursive; font-style:italic; color:#e8edf4; font-size:40px; }}
#dotcom {{ color:#fff; font-size:13px; font-weight:bold; }}
#oRound {{ background: qlineargradient(x1:0,y1:0,x2:1,y2:1, stop:0 #7d9fcc, stop:0.6 #3a5c92, stop:1 #26406e);
    border:1px solid #142848; border-radius:17px; color:#dbe8f8; font-size:22px; font-style:italic;
    font-family:"Georgia",serif; font-weight:bold; }}
QPushButton[cls="sideBtn-globe"]  {{ border:1px solid #142848; border-radius:13px;
    background: qlineargradient(x1:0,y1:0,x2:1,y2:1, stop:0 #7ee08a, stop:0.65 #1f8f3a, stop:1 #0d5c22); }}
QPushButton[cls="sideBtn-smiley"] {{ border:1px solid #142848; border-radius:13px;
    background: qlineargradient(x1:0,y1:0,x2:1,y2:1, stop:0 #ffe27a, stop:0.65 #e0a312, stop:1 #9a6c04); }}
QPushButton[cls="sideBtn-note"]   {{ border:1px solid #142848; border-radius:13px;
    background: qlineargradient(x1:0,y1:0,x2:1,y2:1, stop:0 #f5d98a, stop:0.65 #caa53c, stop:1 #8a6c1a); }}

/* ---- filter slab ---- */
#slab {{ background: {_SLAB}; border:1px solid #0d1118; border-radius:8px; }}
#filtTitlebar {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #5e87c2, stop:0.55 #33598f, stop:1 #27497c);
    border:1px solid #14264a; border-radius:6px; color:#fff; font-weight:bold; font-size:13px; }}
#filtSearch {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #1a2230, stop:1 #252e3e);
    border:1px solid #10151e; border-radius:8px; }}
QLineEdit[cls="filtInput"] {{ background:transparent; border:0; color:#dce6f2; font-weight:bold; }}
QLabel[cls="frowLbl"] {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #4b5a6e, stop:1 #39455a);
    color:#fff; font-size:11px; font-weight:bold; border:1px solid #141a24;
    border-top-left-radius:7px; border-bottom-left-radius:7px; padding:0 8px; }}
QComboBox[cls="frow"] {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #161c28, stop:1 #222b3a);
    color:#c4d2e4; font-size:11px; border:1px solid #10151e;
    border-top-right-radius:7px; border-bottom-right-radius:7px; padding:0 8px; }}
QComboBox[cls="frow"]::drop-down {{ border:0; width:16px; }}
QComboBox QAbstractItemView {{ background:#222b3a; color:#dce6f2; selection-background-color:#33598f; }}
QPushButton[cls="sq"] {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #39455a, stop:1 #222b3a);
    border:1px solid #10151e; border-radius:6px; color:#7fb2f0; font-size:13px; }}

/* ---- details (navy) ---- */
#navyWin {{ background: {_NAVY}; border:1px solid #0d1626; border-radius:4px; }}
#blueTitlebar {{ background: qlineargradient(x1:0,y1:0,x2:1,y2:0, stop:0 #1a4d9e, stop:0.6 #3f7ad0, stop:1 #5b95e4);
    color:#fff; font-weight:bold; font-size:12px; border-top-left-radius:3px; border-top-right-radius:3px; }}
#detHeader {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #0c1422, stop:1 #101c30);
    color:#fff; font-size:14px; font-weight:bold; border-bottom:1px solid #000; }}
#detBody {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #101a2c, stop:1 #0b1322); }}
#neonStrip {{ background: qlineargradient(x1:0,y1:0,x2:1,y2:1, stop:0 #1b2330, stop:0.7 #0c1018);
    border:1px solid #2c3a52; border-radius:12px; }}
QLabel[cls="neon"] {{ color:#9feaff; font-family:"Arial",sans-serif; font-size:28px; font-weight:900; }}
#detPanel {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #2c4260, stop:1 #1d3048);
    border:1px solid #0c1626; border-radius:10px; }}
QFrame[cls="detCard"] {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #22364f, stop:1 #16273c);
    border:1px solid #0a141f; border-radius:8px; }}
QLabel[cls="detIc"] {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #bfe2f5, stop:1 #1c5a96);
    border:2px solid #44608a; border-radius:6px; font-size:20px; }}
QLabel[cls="detQ"] {{ color:#fff; font-size:12px; font-weight:bold; }}
QFrame[cls="pillfield"] {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #0e1828, stop:1 #1a2c44);
    border:1px solid #000; border-radius:11px; }}
QLabel[cls="fl"] {{ color:#cfe0f4; font-weight:bold; font-size:11px; }}
QLabel[cls="fv"] {{ color:#74e860; font-weight:bold; font-size:11px; }}
#detFoot {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #0b1322, stop:1 #0d1626); }}
QPushButton[cls="detBtn"] {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #3c5a86, stop:1 #26405f);
    border:1px solid #0a141f; border-radius:3px; color:#fff; font-size:12px; font-weight:bold;
    min-width:84px; min-height:24px; }}
QPushButton[cls="detBtn"]:disabled {{ color:#7e92ac; }}

/* ---- communication (win2k grey) — force dark text on the light window ---- */
#w2k QLabel {{ color:#101820; }}
#w2k {{ background:#c6d0de; border:1px solid #3a4a5e; border-radius:2px; }}
QLabel[cls="ccSendto"] {{ font-weight:bold; font-size:13px; color:#101820; }}
QPushButton[cls="w95btn"] {{ background:#cdd6e2; border:1px solid #1c2836; color:#101820; font-size:11px;
    min-height:19px; padding:0 10px; }}
QPushButton[cls="w95btn"]:pressed {{ background:#b7c2d2; }}
QLabel[cls="ccId"] {{ font-size:11px; color:#101820; }}
QLabel[cls="ccTab"] {{ background:#b7c2d2; border:1px solid #1c2836; color:#101820; font-size:11px;
    padding:3px 14px; border-top-left-radius:4px; border-top-right-radius:4px; }}
QLabel[cls="ccTab"][on="1"] {{ background:#cdd6e2; font-weight:bold; }}
#ccPage {{ background:#cdd6e2; border:1px solid #1c2836; }}
QLabel[cls="combo"] {{ background:#fff; border:1px solid #1c2836; color:#101820; font-size:11px; padding:0 4px; }}
QPushButton[cls="tgl"] {{ background:#cdd6e2; border:1px solid #9aa6b6; color:#101820; font-weight:bold;
    min-width:20px; max-width:20px; min-height:20px; max-height:20px; }}
QPushButton[cls="tgl"]:checked {{ background:#b7c2d2; border:1px solid #7b8a9e; }}
QTextEdit[cls="ccText"] {{ background:#fff; border:1px solid #1c2836; color:#101820; font-size:12px; }}
#ccLog {{ background:#fff; border:1px solid #1c2836; color:#101820; }}
#ccStatus {{ font-size:11px; color:#101820; background:#c6d0de; border:1px solid #9aa6b6; padding:2px 6px; }}
#mascot {{ background: qlineargradient(x1:0,y1:0,x2:1,y2:1, stop:0 #e8e2d6, stop:1 #c9bda6); border:1px solid #1c2836; }}

/* ---- send widget ---- */
#sendOuter {{ background: qlineargradient(x1:0,y1:0,x2:1,y2:1, stop:0 #6b4438, stop:0.6 #3a2018, stop:1 #241008);
    border:1px solid #120804; border-radius:6px; }}
#sendInner {{ background: qlineargradient(x1:0,y1:0,x2:1,y2:1, stop:0 #2c4a7c, stop:0.7 #1a2c52, stop:1 #101c3a);
    border:1px solid #0a1326; border-radius:4px; }}
#sendWord {{ font-family:"Arial Black","Arial",sans-serif; font-size:17px; font-weight:900; font-style:italic; color:#e23a2a; }}
QPushButton[cls="onlineTab"] {{ background: qlineargradient(x1:0,y1:0,x2:1,y2:0, stop:0 #1f8f2a, stop:1 #5ddc4a);
    color:#04330a; font-weight:bold; font-size:10px; border:1px solid #0d4d06; border-radius:3px; }}
QPushButton[cls="onlineTab"][off="1"] {{ background: qlineargradient(x1:0,y1:0,x2:1,y2:0, stop:0 #8f1f1f, stop:1 #dc4a4a);
    border:1px solid #4d0606; color:#330404; }}
#sendLogo {{ background: qlineargradient(x1:0,y1:0,x2:1,y2:1, stop:0 #4a6ba0, stop:0.6 #1c3258, stop:1 #0e1c38);
    border:2px solid #5d7fb4; border-radius:27px; }}

/* ---- status (yahoo mini) ---- */
#yhInner {{ background: qlineargradient(x1:0,y1:0,x2:0,y2:1, stop:0 #101a30, stop:1 #1a2848);
    border:1px solid #0a1226; border-radius:8px; }}
QLabel[cls="yhH"] {{ color:#fff; font-size:11px; font-weight:bold; }}
QLabel[cls="yhN"] {{ color:#ffe27a; font-size:16px; font-weight:bold; }}
"""
