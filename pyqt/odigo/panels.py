"""The seven panel windows + the radar widget, as native PyQt6.

Each class subclasses FramelessWindow and wires itself to the shared Bus and
WindowManager. Behaviour mirrors the original per-panel <script> blocks in
frontend/w/*.html one-to-one (search/paginate, filters, person details, send,
history, stats, status toggle).
"""
from __future__ import annotations

import math
import random
from urllib.parse import quote

from PyQt6.QtCore import Qt, QTimer, QRectF, pyqtSignal
from PyQt6.QtGui import (
    QPainter, QRadialGradient, QConicalGradient, QColor, QPen, QPainterPath, QCursor,
)
from PyQt6.QtWidgets import (
    QWidget, QFrame, QLabel, QLineEdit, QPushButton, QComboBox, QTextEdit,
    QVBoxLayout, QHBoxLayout, QGridLayout, QScrollArea, QSizePolicy,
)

from .core import (
    FramelessWindow, Draggable, WindowManager, Bus, ME,
    api_get, api_post, sprite_pixmap, win_button,
)
from . import skin

PT = Qt.CursorShape.PointingHandCursor
LEFT = Qt.MouseButton.LeftButton


# ---------------------------------------------------------------------------
# small helpers
# ---------------------------------------------------------------------------
class ClickLabel(QLabel):
    clicked = pyqtSignal()

    def __init__(self, *a, **k):
        super().__init__(*a, **k)
        self.setCursor(QCursor(PT))

    def mousePressEvent(self, e):
        if e.button() == LEFT:
            self.clicked.emit()


def _hbox(parent=None, m=0, s=0):
    lo = QHBoxLayout(parent)
    lo.setContentsMargins(m, m, m, m)
    lo.setSpacing(s)
    return lo


def _vbox(parent=None, m=0, s=0):
    lo = QVBoxLayout(parent)
    lo.setContentsMargins(m, m, m, m)
    lo.setSpacing(s)
    return lo


def _root(win: FramelessWindow, container: QWidget):
    """Fill the translucent frameless window with a single styled container."""
    lo = _vbox(win)
    lo.addWidget(container)


def _titlebar(name_close, wm: WindowManager, title: str, obj: str,
              extra_buttons=None, btn_color="#eaf2fc", glyph=True) -> Draggable:
    """A draggable strip with an Ø glyph + title + close (and optional buttons)."""
    bar = Draggable()
    bar.setObjectName(obj)
    lo = _hbox(bar, m=6, s=6)
    if glyph:
        g = QLabel("Ø")
        skin.tag(g, "oglyph")
        lo.addWidget(g)
    t = QLabel(title)
    t.setObjectName("devTitle")
    lo.addWidget(t)
    lo.addStretch(1)
    for b in (extra_buttons or []):
        lo.addWidget(b)
    lo.addWidget(win_button("×", lambda: wm.close(name_close), btn_color))
    return bar


# ===========================================================================
# LOGIN
# ===========================================================================
class LoginWindow(FramelessWindow):
    def __init__(self, wm: WindowManager, bus: Bus):
        super().__init__("login")
        dev = QFrame()
        dev.setObjectName("device")
        _root(self, dev)
        col = _vbox(dev, m=12, s=7)
        col.setContentsMargins(12, 6, 12, 16)

        col.addWidget(_titlebar("login", wm, "Odigo", "devTitle_bar"))
        logo = QLabel("odigo")
        logo.setObjectName("loginLogo")
        logo.setAlignment(Qt.AlignmentFlag.AlignCenter)
        col.addWidget(logo)

        col.addWidget(skin.tag(QLabel("Odigo ID"), "lgLabel"))
        self.id = QLineEdit(ME["id"])
        skin.tag(self.id, "lgInput")
        col.addWidget(self.id)

        col.addWidget(skin.tag(QLabel("Password"), "lgLabel"))
        self.pw = QLineEdit("demodemo")
        self.pw.setEchoMode(QLineEdit.EchoMode.Password)
        skin.tag(self.pw, "lgInput")
        col.addWidget(self.pw)

        btn = QPushButton("Sign In")
        skin.tag(btn, "oval")
        btn.setCursor(QCursor(PT))
        btn.setMinimumHeight(30)
        btn.clicked.connect(wm.login)
        self.pw.returnPressed.connect(wm.login)
        col.addWidget(btn)

        hint = QLabel("Demo login — press Sign In (any password works)")
        hint.setObjectName("lgHint")
        hint.setAlignment(Qt.AlignmentFlag.AlignCenter)
        col.addWidget(hint)
        col.addStretch(1)


# ===========================================================================
# RADAR (used by People Finder)
# ===========================================================================
class PersonMarker(QWidget):
    def __init__(self, radar: "Radar", person: dict, selected: bool):
        super().__init__(radar)
        self.radar = radar
        self.handle = person["handle"]
        self.setCursor(QCursor(PT))
        self.setToolTip(f'{person["display_name"]} — {person["topic"]} ({person["status"]})'
                        '  ·  click: details, double-click: message')
        lo = _vbox(self, m=0, s=0)
        lo.setAlignment(Qt.AlignmentFlag.AlignCenter)
        img = QLabel()
        img.setPixmap(sprite_pixmap(2.4, person.get("sprite", "gold")))
        img.setAlignment(Qt.AlignmentFlag.AlignCenter)
        lo.addWidget(img)
        self.nm = QLabel(person["display_name"])
        self.nm.setAlignment(Qt.AlignmentFlag.AlignCenter)
        lo.addWidget(self.nm)
        self.set_selected(selected)
        self.adjustSize()

    def set_selected(self, selected: bool):
        color = "#ffe27a" if selected else "#ffffff"
        weight = "bold" if selected else "normal"
        self.nm.setStyleSheet(
            f"color:{color};font-size:9px;font-weight:{weight};background:transparent;")

    def mousePressEvent(self, e):
        if e.button() == LEFT:
            self.radar.personClicked.emit(self.handle)

    def mouseDoubleClickEvent(self, e):
        if e.button() == LEFT:
            self.radar.personDbl.emit(self.handle)


class Radar(QWidget):
    personClicked = pyqtSignal(str)
    personDbl = pyqtSignal(str)

    def __init__(self):
        super().__init__()
        self.setMinimumSize(230, 230)
        self._angle = 0.0
        self._markers: list[PersonMarker] = []
        self._people: list[dict] = []
        self._current: str | None = None
        self._timer = QTimer(self)
        self._timer.timeout.connect(self._spin)
        self._timer.start(40)

    def _spin(self):
        self._angle = (self._angle + 3) % 360
        self.update()

    def _circle_rect(self) -> QRectF:
        w, h = self.width(), self.height()
        side = min(w, h)
        return QRectF((w - side) / 2, (h - side) / 2, side, side)

    def set_people(self, people, current):
        self._people = people
        self._current = current
        self._relayout()

    def set_current(self, current):
        # Restyle in place — do NOT rebuild markers. Rebuilding here deletes the
        # very marker being clicked, which breaks real (physical) double-clicks:
        # the second press would land on a freshly-created widget and Qt's
        # double-click detection (same-widget) never fires -> Communication
        # Center wouldn't open on double-click.
        self._current = current
        for m in self._markers:
            m.set_selected(m.handle == current)

    def resizeEvent(self, e):
        self._place_markers()

    def _relayout(self):
        for m in self._markers:
            m.deleteLater()
        self._markers = []
        for p in self._people:
            self._markers.append(PersonMarker(self, p, self._current == p["handle"]))
        self._place_markers()
        for m in self._markers:
            m.show()

    def _place_markers(self):
        rect = self._circle_rect()
        n = len(self._markers)
        for i, m in enumerate(self._markers):
            if n == 1:
                xp, yp = 50.0, 50.0
            else:
                half = math.ceil(n / 2)
                ring = 34 if i < half else 20
                count = half if i < half else (n - half)
                idx = i if i < half else i - half
                ang = (idx / count) * math.pi * 2 - math.pi / 2
                xp = 50 + math.cos(ang) * ring
                yp = 50 + math.sin(ang) * ring
            cx = rect.left() + xp / 100 * rect.width()
            cy = rect.top() + yp / 100 * rect.height()
            m.move(int(cx - m.width() / 2), int(cy - m.height() / 2))

    def paintEvent(self, e):
        p = QPainter(self)
        p.setRenderHint(QPainter.RenderHint.Antialiasing)
        rect = self._circle_rect()
        center = rect.center()
        rg = QRadialGradient(center.x(), rect.top() + rect.height() * 0.4, rect.width() / 2)
        rg.setColorAt(0.0, QColor("#16244a"))
        rg.setColorAt(0.55, QColor("#101b3a"))
        rg.setColorAt(1.0, QColor("#0a1228"))
        p.setPen(Qt.PenStyle.NoPen)
        p.setBrush(rg)
        p.drawEllipse(rect)
        # inner ring
        p.setBrush(Qt.BrushStyle.NoBrush)
        p.setPen(QPen(QColor(110, 150, 200, 80), 1))
        p.drawEllipse(rect.adjusted(10, 10, -10, -10))
        # sweep (approximates the CSS conic sweep)
        cg = QConicalGradient(center.x(), center.y(), -self._angle)
        cg.setColorAt(0.0, QColor(90, 220, 120, 90))
        cg.setColorAt(0.16, QColor(90, 220, 120, 0))
        cg.setColorAt(1.0, QColor(90, 220, 120, 0))
        path = QPainterPath()
        path.addEllipse(rect)
        p.setClipPath(path)
        p.setPen(Qt.PenStyle.NoPen)
        p.setBrush(cg)
        p.drawEllipse(rect)
        p.end()


# ===========================================================================
# PEOPLE FINDER (hub)
# ===========================================================================
class PeopleFinderWindow(FramelessWindow):
    def __init__(self, wm: WindowManager, bus: Bus):
        super().__init__("people-finder")
        self.wm, self.bus = wm, bus
        self.filters: dict = {}
        self.search_text = ""
        self.page = 1
        self.pages = 1
        self.people: list[dict] = []
        self.current: str | None = None

        dev = QFrame()
        dev.setObjectName("device")
        _root(self, dev)
        col = _vbox(dev, s=4)
        col.setContentsMargins(9, 4, 9, 10)

        filt_btn = win_button("☰", lambda: wm.open("filter"))
        col.addWidget(_titlebar("people-finder", wm, ME["handle"], "pf_bar",
                                extra_buttons=[filt_btn]))

        menu = skin.tag(QLabel("Login    View    Tools    Help"), "menubar")
        col.addWidget(menu)

        tools = QFrame()
        tools.setObjectName("toolstrip")
        tlo = _hbox(tools, m=3, s=3)
        tlo.addStretch(1)
        for i in range(5):
            b = QLabel()
            skin.tag(b, "toolbtn")
            if i == 2:
                skin.flag(b, "lit", True)
            b.setFixedSize(42, 24)
            b.setPixmap(sprite_pixmap(1.6, "gold" if i % 2 else "orange"))
            b.setAlignment(Qt.AlignmentFlag.AlignCenter)
            tlo.addWidget(b)
        tlo.addStretch(1)
        col.addWidget(tools)

        screen = QFrame()
        screen.setObjectName("pfScreen")
        slo = _vbox(screen, m=6, s=6)
        self.title = QLabel("People Finder\nAll Topics")
        self.title.setObjectName("pfTitle")
        self.title.setAlignment(Qt.AlignmentFlag.AlignCenter)
        slo.addWidget(self.title)

        self.radar = Radar()
        self.radar.personClicked.connect(self._on_person_click)
        self.radar.personDbl.connect(self._on_person_dbl)
        slo.addWidget(self.radar, 1)

        ctl = _hbox(s=6)
        self.prev = self._oval("▲", 34)
        self.go = self._oval("GO", None)
        self.nxt = self._oval("▼", 34)
        self.prev.clicked.connect(lambda: self._search(self.page - 1) if self.page > 1 else None)
        self.go.clicked.connect(lambda: self._search(1))
        self.nxt.clicked.connect(lambda: self._search(self.page + 1) if self.page < self.pages else None)
        ctl.addWidget(self.prev)
        ctl.addWidget(self.go, 1)
        ctl.addWidget(self.nxt)
        slo.addLayout(ctl)
        col.addWidget(screen, 1)

        logo = QFrame()
        logo.setObjectName("logoPanel")
        logo.setFixedHeight(64)
        llo = _hbox(logo, m=8)
        sl = QLabel("odigo")
        sl.setObjectName("scriptLogo")
        llo.addWidget(sl)
        llo.addStretch(1)
        llo.addWidget(QLabel("odigo.com", objectName="dotcom"),
                      alignment=Qt.AlignmentFlag.AlignBottom)
        col.addWidget(logo)

        bottom = _hbox(s=7)
        oround = QLabel("Ø")
        oround.setObjectName("oRound")
        oround.setFixedSize(34, 34)
        oround.setAlignment(Qt.AlignmentFlag.AlignCenter)
        bottom.addWidget(oround)
        for cls, target in (("sideBtn-globe", "filter"), ("sideBtn-smiley", "status"),
                            ("sideBtn-note", "communication")):
            b = QPushButton()
            skin.tag(b, cls)
            b.setCursor(QCursor(PT))
            b.setFixedSize(26, 26)
            b.clicked.connect(lambda _, t=target: wm.open(t))
            bottom.addWidget(b)
        bottom.addStretch(1)
        col.addLayout(bottom)

        # bus wiring
        bus.filters.connect(self._on_filters)
        bus.select.connect(self._on_select_ext)

        self._search(1)

    def _oval(self, text, width):
        b = QPushButton(text)
        skin.tag(b, "oval")
        b.setCursor(QCursor(PT))
        if width:
            b.setFixedWidth(width)
        return b

    def _search(self, page=1):
        page = max(1, page)
        params = dict(self.filters)
        params["search"] = self.search_text
        params["page"] = page

        def done(data):
            self.people = data.get("people", [])
            self.page = data.get("page", 1)
            self.pages = data.get("pages", 1)
            self.radar.set_people(self.people, self.current)
            topic = self.filters.get("topic")
            topic = topic if topic and topic != "All Topics" else "All Topics"
            self.title.setText(f"People Finder\n{topic}")

        api_get("/odigo/people", params, done)

    def _select(self, handle):
        self.current = handle
        self.bus.select.emit(handle)
        self.radar.set_current(handle)

    def _on_person_click(self, handle):
        self._select(handle)
        self.wm.open("details")

    def _on_person_dbl(self, handle):
        self._select(handle)
        self.wm.open("communication")

    def _on_filters(self, payload: dict):
        self.filters = payload.get("filters", {}) or {}
        self.search_text = payload.get("search", "") or ""
        self._search(1)

    def _on_select_ext(self, handle):
        self.current = handle
        self.radar.set_current(handle)


# ===========================================================================
# FILTER
# ===========================================================================
class FilterWindow(FramelessWindow):
    LABELS = [("topic", "Topic"), ("ageGroup", "Age Group"), ("gender", "Gender"),
              ("region", "Region"), ("language", "Language"), ("occupation", "Occupation"),
              ("status", "Status"), ("mood", "Mood"), ("intention", "Intention"),
              ("zodiac", "Zodiac")]

    def __init__(self, wm: WindowManager, bus: Bus):
        super().__init__("filter")
        self.wm, self.bus = wm, bus
        self.filters: dict = {}
        self.combos: dict[str, QComboBox] = {}

        slab = QFrame()
        slab.setObjectName("slab")
        _root(self, slab)
        col = _vbox(slab, s=8)
        col.setContentsMargins(12, 8, 12, 12)

        tb = Draggable()
        tb.setObjectName("filtTitlebar")
        tb.setFixedHeight(26)
        tlo = _hbox(tb, m=8)
        tlo.addStretch(1)
        tlo.addWidget(QLabel("People Finder Filter"))
        tlo.addStretch(1)
        tlo.addWidget(win_button("×", lambda: wm.close("filter"), "#cfe0f4"))
        col.addWidget(tb)

        sr = QFrame()
        sr.setObjectName("filtSearch")
        sr.setFixedHeight(30)
        srlo = _hbox(sr, m=6, s=5)
        srlo.addWidget(QLabel("\U0001f50d"))
        self.search = QLineEdit()
        skin.tag(self.search, "filtInput")
        self.search.setPlaceholderText("Search topic or name…")
        self.search.returnPressed.connect(self._apply)
        srlo.addWidget(self.search, 1)
        col.addWidget(sr)

        grid = QGridLayout()
        grid.setContentsMargins(0, 0, 0, 0)
        grid.setHorizontalSpacing(0)
        grid.setVerticalSpacing(6)
        for row, (key, label) in enumerate(self.LABELS):
            lbl = skin.tag(QLabel(label), "frowLbl")
            lbl.setFixedWidth(104)
            lbl.setFixedHeight(26)
            combo = QComboBox()
            skin.tag(combo, "frow")
            combo.setFixedHeight(26)
            combo.currentTextChanged.connect(lambda v, k=key: self.filters.__setitem__(k, v))
            self.combos[key] = combo
            grid.addWidget(lbl, row, 0)
            grid.addWidget(combo, row, 1)
        col.addLayout(grid, 1)

        foot = _hbox(s=7)
        reset = self._sq("↻")
        rand = self._sq("✱")
        reset.clicked.connect(self._reset)
        rand.clicked.connect(self._random)
        foot.addWidget(reset)
        foot.addWidget(rand)
        foot.addStretch(1)
        go = QPushButton("GO")
        skin.tag(go, "oval")
        go.setCursor(QCursor(PT))
        go.setFixedWidth(92)
        go.clicked.connect(self._apply)
        foot.addWidget(go)
        col.addLayout(foot)

        self._boot()

    def _sq(self, text):
        b = QPushButton(text)
        skin.tag(b, "sq")
        b.setCursor(QCursor(PT))
        b.setFixedSize(34, 22)
        return b

    def _boot(self):
        def done(opts):
            for key, combo in self.combos.items():
                combo.blockSignals(True)
                combo.clear()
                combo.addItems([str(o) for o in opts.get(key, [])])
                combo.blockSignals(False)
        api_get("/odigo/filters", None, done)

    def _apply(self):
        self.bus.filters.emit({"filters": dict(self.filters), "search": self.search.text().strip()})

    def _reset(self):
        self.filters = {}
        self.search.clear()
        for combo in self.combos.values():
            combo.setCurrentIndex(0)
        self._apply()

    def _random(self):
        def done(data):
            people = data.get("people", [])
            if people:
                self.bus.select.emit(random.choice(people)["handle"])
        api_get("/odigo/people", None, done)


# ===========================================================================
# DETAILS
# ===========================================================================
class DetailsWindow(FramelessWindow):
    def __init__(self, wm: WindowManager, bus: Bus):
        super().__init__("details")
        self.wm, self.bus = wm, bus
        self.current: dict | None = None

        navy = QFrame()
        navy.setObjectName("navyWin")
        _root(self, navy)
        col = _vbox(navy, m=3, s=0)

        tb = Draggable()
        tb.setObjectName("blueTitlebar")
        tb.setFixedHeight(24)
        tlo = _hbox(tb, m=8)
        self.title = QLabel("Details")
        tlo.addWidget(self.title)
        tlo.addStretch(1)
        tlo.addWidget(win_button("×", lambda: wm.close("details")))
        col.addWidget(tb)

        self.header = QLabel("Pick someone in the People Finder to see their profile.")
        self.header.setObjectName("detHeader")
        self.header.setWordWrap(True)
        self.header.setContentsMargins(12, 8, 12, 8)
        col.addWidget(self.header)

        body = QFrame()
        body.setObjectName("detBody")
        blo = _hbox(body, m=12, s=12)
        neon = QFrame()
        neon.setObjectName("neonStrip")
        neon.setFixedWidth(74)
        nlo = _vbox(neon)
        nlo.setAlignment(Qt.AlignmentFlag.AlignCenter)
        for ch in "ODIGO":
            lab = skin.tag(QLabel(ch), "neon")
            lab.setAlignment(Qt.AlignmentFlag.AlignCenter)
            nlo.addWidget(lab)
        blo.addWidget(neon)

        self.scroll = QScrollArea()
        self.scroll.setObjectName("detPanel")
        self.scroll.setWidgetResizable(True)
        self.scroll.setHorizontalScrollBarPolicy(Qt.ScrollBarPolicy.ScrollBarAlwaysOff)
        self.panel = QWidget()
        self.panel_lo = _vbox(self.panel, m=10, s=9)
        self.panel_lo.addStretch(1)
        self.scroll.setWidget(self.panel)
        blo.addWidget(self.scroll, 1)
        col.addWidget(body, 1)

        foot = QFrame()
        foot.setObjectName("detFoot")
        flo = _hbox(foot, m=10, s=8)
        close = self._btn("Close", lambda: wm.close("details"))
        flo.addWidget(close)
        flo.addStretch(1)
        self.msg_btn = self._btn("Message", lambda: wm.open("communication"))
        self.friend_btn = self._btn("Add Friend", self._add_friend)
        flo.addWidget(self.msg_btn)
        flo.addWidget(self.friend_btn)
        col.addWidget(foot)

        bus.select.connect(self._load)

    def _btn(self, text, cb):
        b = QPushButton(text)
        skin.tag(b, "detBtn")
        b.setCursor(QCursor(PT))
        b.clicked.connect(cb)
        return b

    def _load(self, handle):
        api_get("/odigo/person/" + quote(handle), None, self._render)

    def _render(self, p: dict):
        self.current = p
        self.title.setText(p["display_name"] + "'s Details")
        rows = [
            ("⏳", "My age is (6-120)", "Age", f'{p["ageRange"]}  ({p["age"]})'),
            ("♀", "Please indicate", "Gender", p["gender"]),
            ("\U0001f30e", "Where are you from?", "Region", p["region"]),
            ("\U0001f4ac", "Language", "Speaks", p["language"]),
            ("⭐", "Zodiac", "Sign", p["zodiac"]),
            ("\U0001f4bc", "Occupation", "Works as", p["occupation"]),
            ("♪", "Interested in", "Topic", f'{p["topic"]} · {p["intention"]}'),
            ("\U0001f600", "Current mood", "Mood", f'{p["mood"]} · {p["status"]}'),
        ]
        # clear old cards
        while self.panel_lo.count():
            item = self.panel_lo.takeAt(0)
            if item.widget():
                item.widget().deleteLater()
        for ic, q, fl, fv in rows:
            self.panel_lo.addWidget(self._card(ic, q, fl, fv))
        self.panel_lo.addStretch(1)
        self.header.setText("“" + p["tagline"] + "”" if p.get("tagline")
                            else "Here's the profile — confidential, no one's getting personal.")
        self.friend_btn.setText("✓ Friend" if p.get("is_friend") else "Add Friend")

    def _card(self, ic, q, fl, fv):
        card = QFrame()
        skin.tag(card, "detCard")
        clo = _hbox(card, m=8, s=12)
        icon = skin.tag(QLabel(ic), "detIc")
        icon.setFixedSize(46, 46)
        icon.setAlignment(Qt.AlignmentFlag.AlignCenter)
        clo.addWidget(icon)
        fields = _vbox(s=5)
        fields.addWidget(skin.tag(QLabel(q), "detQ"))
        pill = QFrame()
        skin.tag(pill, "pillfield")
        pill.setFixedHeight(22)
        plo = _hbox(pill, m=12)
        plo.addWidget(skin.tag(QLabel(fl), "fl"))
        plo.addStretch(1)
        plo.addWidget(skin.tag(QLabel(fv), "fv"))
        fields.addWidget(pill)
        clo.addLayout(fields, 1)
        return card

    def _add_friend(self):
        if not self.current:
            return

        def done(_res):
            self.current["is_friend"] = True
            self.friend_btn.setText("✓ Friend")
            self.bus.stats_changed.emit()
        api_post("/odigo/friends", {"handle": self.current["handle"]}, done)


# ===========================================================================
# COMMUNICATION CENTER
# ===========================================================================
class CommunicationWindow(FramelessWindow):
    TABS = ["Message", "Chat request", "URL", "File"]

    def __init__(self, wm: WindowManager, bus: Bus):
        super().__init__("communication")
        self.wm, self.bus = wm, bus
        self.current: dict | None = None
        self.msg_type = "Message"
        self._tok = 0  # invalidates stale async person-fetches (out-of-order guard)

        w2k = QFrame()
        w2k.setObjectName("w2k")
        _root(self, w2k)
        col = _vbox(w2k, m=3, s=0)

        tb = Draggable()
        tb.setObjectName("blueTitlebar")
        tb.setFixedHeight(24)
        tlo = _hbox(tb, m=8, s=6)
        og = QLabel("Ø")
        og.setStyleSheet("color:#fff;font-style:italic;font-weight:bold;")
        tlo.addWidget(og)
        tlo.addWidget(QLabel("Communication Center"))
        tlo.addStretch(1)
        tlo.addWidget(win_button("×", lambda: wm.close("communication")))
        col.addWidget(tb)

        body = QWidget()
        body.setObjectName("ccBody")
        blo = _vbox(body, m=7, s=4)

        row = _hbox(s=8)
        self.to = skin.tag(QLabel("Send to: —"), "ccSendto")
        row.addWidget(self.to, 1)
        row.addWidget(self._w95("History…", self._history))
        row.addWidget(self._w95("Details…", lambda: wm.open("details")))
        blo.addLayout(row)

        row2 = _hbox()
        self.id = skin.tag(QLabel("ID: —"), "ccId")
        row2.addWidget(self.id)
        row2.addStretch(1)
        self.led = QLabel("●")
        self.led.setStyleSheet("color:#1f9b22;font-size:14px;")
        row2.addWidget(self.led)
        blo.addLayout(row2)

        tabs = _hbox(s=2)
        self.tab_labels = {}
        for t in self.TABS:
            lab = skin.tag(ClickLabel(t), "ccTab")
            if t == "Message":
                skin.flag(lab, "on", True)
            lab.clicked.connect(lambda tt=t: self._set_tab(tt))
            self.tab_labels[t] = lab
            tabs.addWidget(lab)
        tabs.addStretch(1)
        blo.addLayout(tabs)

        page = QFrame()
        page.setObjectName("ccPage")
        plo = _vbox(page, m=6, s=6)
        toolbar = _hbox(s=5)
        toolbar.addWidget(self._combo("Arial", 150))
        toolbar.addWidget(self._combo("10", 44))
        for label, bold, ital in (("B", True, False), ("I", False, True), ("U", False, False)):
            tg = QPushButton(label)
            skin.tag(tg, "tgl")
            tg.setCheckable(True)
            tg.setCursor(QCursor(PT))
            f = tg.font()
            f.setBold(bold)
            f.setItalic(ital)
            f.setUnderline(label == "U")
            tg.setFont(f)
            toolbar.addWidget(tg)
        toolbar.addStretch(1)
        plo.addLayout(toolbar)

        self.text = QTextEdit()
        skin.tag(self.text, "ccText")
        self.text.setPlaceholderText("Type a message…")
        plo.addWidget(self.text, 1)

        self.log = QTextEdit()
        self.log.setObjectName("ccLog")
        self.log.setReadOnly(True)
        self.log.hide()
        plo.addWidget(self.log, 1)

        foot = _hbox(s=7)
        self.compose_btn = self._w95("Compose", self._compose)
        self.compose_btn.hide()
        foot.addWidget(self.compose_btn)
        foot.addWidget(self._w95("Add Friend", self._add_friend))
        foot.addStretch(1)
        foot.addWidget(self._w95("Cancel", self._cancel))
        send = self._w95("Send", self._send)
        f = send.font()
        f.setBold(True)
        send.setFont(f)
        foot.addWidget(send)
        mascot = QLabel("\U0001f43b")
        mascot.setObjectName("mascot")
        mascot.setFixedSize(46, 42)
        mascot.setAlignment(Qt.AlignmentFlag.AlignCenter)
        foot.addWidget(mascot)
        plo.addLayout(foot)
        blo.addWidget(page, 1)

        self.status = QLabel("Pick someone in the People Finder")
        self.status.setObjectName("ccStatus")
        blo.addWidget(self.status)
        col.addWidget(body, 1)

        bus.select.connect(self._set_target)

    def _w95(self, text, cb):
        b = QPushButton(text)
        skin.tag(b, "w95btn")
        b.setCursor(QCursor(PT))
        b.clicked.connect(cb)
        return b

    def _combo(self, text, width):
        c = skin.tag(QLabel(text + "  ▼"), "combo")
        c.setFixedSize(width, 20)
        return c

    def _set_status(self, t):
        self.status.setText(t)

    def _set_tab(self, t):
        for name, lab in self.tab_labels.items():
            skin.flag(lab, "on", name == t)
        self.msg_type = t
        self._compose()

    def _set_target(self, handle):
        self._tok += 1
        my = self._tok

        def done(p):
            if my != self._tok:   # a newer select/send superseded this fetch
                return
            self.current = p
            self.to.setText("Send to: " + p["display_name"])
            self.id.setText("ID: " + p["odigo_id"])
            off = p["status"] in ("Invisible", "Away")
            self.led.setStyleSheet(f"color:{'#9b1f1f' if off else '#1f9b22'};font-size:14px;")
            self._set_status(f"Compose a {self.msg_type} to {p['display_name']}")
            if self.log.isVisible():
                self._history()
        api_get("/odigo/person/" + quote(handle), None, done)

    def _send(self):
        if not self.current:
            self._set_status("Pick someone first")
            return
        body = self.text.toPlainText().strip()
        if not body:
            self._set_status("Nothing to send")
            return
        self._tok += 1  # this send is the latest action — drop any pending target fetch

        def done(res):
            self.text.clear()
            self._set_status(res.get("status", "Sent"))
            self.bus.stats_changed.emit()
            if self.log.isVisible():
                self._history()
        api_post("/odigo/messages",
                 {"to": self.current["handle"], "body": body, "type": self.msg_type}, done)

    def _history(self):
        if not self.current:
            self._set_status("Pick someone first")
            return

        def done(data):
            msgs = data.get("messages", [])
            if msgs:
                html = ""
                for m in msgs:
                    who = ME["handle"] if m["direction"] == "out" else self.current["display_name"]
                    color = "#1a4d9e" if m["direction"] == "out" else "#a52020"
                    body = (m["body"].replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;"))
                    html += (f'<div><b style="color:{color}">{who}:</b> {body} '
                             f'<span style="color:#8a96a6">{m["time"]}</span></div>')
            else:
                html = '<div>No messages yet — say hi!</div>'
            self.log.setHtml(html)
            self.text.hide()
            self.log.show()
            self.compose_btn.show()
            self._set_status("History with " + self.current["display_name"])
        api_get("/odigo/messages/" + quote(self.current["handle"]), None, done)

    def _compose(self):
        self.log.hide()
        self.text.show()
        self.compose_btn.hide()
        self._set_status("Compose a " + self.msg_type)

    def _cancel(self):
        self.text.clear()
        self._set_status("Cancelled")

    def _add_friend(self):
        if not self.current:
            return

        def done(res):
            self.current["is_friend"] = True
            self._set_status(res.get("status", "Added"))
            self.bus.stats_changed.emit()
        api_post("/odigo/friends", {"handle": self.current["handle"]}, done)


# ===========================================================================
# STATUS (yahoo mini)
# ===========================================================================
class StatusWindow(FramelessWindow):
    def __init__(self, wm: WindowManager, bus: Bus):
        super().__init__("status")
        dev = QFrame()
        dev.setObjectName("device")
        _root(self, dev)
        col = _vbox(dev, s=4)
        col.setContentsMargins(7, 2, 7, 6)
        col.addWidget(_titlebar("status", wm, "status", "yh_bar"))

        inner = QFrame()
        inner.setObjectName("yhInner")
        ilo = _hbox(inner, m=9, s=0)
        self.cells = {}
        for key, head in (("people", "People"), ("notes", "Notes"), ("invisible", "Invisible")):
            c = _vbox(s=2)
            c.setAlignment(Qt.AlignmentFlag.AlignCenter)
            h = skin.tag(QLabel(head), "yhH")
            h.setAlignment(Qt.AlignmentFlag.AlignCenter)
            n = skin.tag(QLabel("0"), "yhN")
            n.setAlignment(Qt.AlignmentFlag.AlignCenter)
            self.cells[key] = n
            c.addWidget(h)
            c.addWidget(n)
            ilo.addLayout(c, 1)
        col.addWidget(inner, 1)

        bus.stats_changed.connect(self._load)
        self._timer = QTimer(self)
        self._timer.timeout.connect(self._load)
        self._timer.start(8000)
        self._load()

    def _load(self):
        def done(s):
            for key, lab in self.cells.items():
                lab.setText(str(s.get(key, 0)))
        api_get("/odigo/stats", None, done)


# ===========================================================================
# SEND widget
# ===========================================================================
class SendWindow(FramelessWindow):
    def __init__(self, wm: WindowManager, bus: Bus):
        super().__init__("send")
        self.online = True
        outer = Draggable()
        outer.setObjectName("sendOuter")
        _root(self, outer)
        olo = _hbox(outer, m=6)
        inner = QFrame()
        inner.setObjectName("sendInner")
        ilo = _hbox(inner, m=6, s=10)

        self.tab = QPushButton("online")
        skin.tag(self.tab, "onlineTab")
        self.tab.setCursor(QCursor(PT))
        self.tab.setFixedWidth(20)
        self.tab.setMinimumHeight(56)
        self.tab.clicked.connect(self._toggle)
        ilo.addWidget(self.tab)

        stack = _vbox(s=3)
        stack.setAlignment(Qt.AlignmentFlag.AlignCenter)
        word = QLabel("SEND")
        word.setObjectName("sendWord")
        word.setAlignment(Qt.AlignmentFlag.AlignCenter)
        env = QLabel("✉")
        env.setAlignment(Qt.AlignmentFlag.AlignCenter)
        env.setStyleSheet("color:#ffd84a;font-size:22px;")
        stack.addWidget(word)
        stack.addWidget(env)
        ilo.addLayout(stack, 1)

        logo = ClickLabel("odigo")
        logo.setObjectName("sendLogo")
        logo.setFixedSize(54, 54)
        logo.setAlignment(Qt.AlignmentFlag.AlignCenter)
        logo.setStyleSheet("color:#dbe8f8;font-style:italic;font-family:'Georgia',serif;")
        logo.setToolTip("Open Communication Center")
        logo.clicked.connect(lambda: wm.open("communication"))
        ilo.addWidget(logo)

        olo.addWidget(inner)

    def _toggle(self):
        self.online = not self.online
        self.tab.setText("online" if self.online else "offline")
        skin.flag(self.tab, "off", not self.online)
