"""Shared infrastructure: config, API client, cross-window signal bus,
pixel-doll sprites, frameless-draggable window base, and the window manager."""
from __future__ import annotations

import requests
from PyQt6.QtCore import (
    Qt, QObject, QRunnable, QThreadPool, pyqtSignal, QPoint, QRectF,
)
from PyQt6.QtGui import QPixmap, QPainter, QColor, QCursor
from PyQt6.QtWidgets import QWidget, QPushButton, QApplication

# ---------------------------------------------------------------------------
# Config — mirrors bus.js API_BASE/ME and desktop/main.go window table.
# ---------------------------------------------------------------------------
API_BASE = "https://api-odigo.your.team"
ME = {"handle": "ventura", "id": "ventura@odigo.im"}

# name -> (title, w, h, x, y, resizable, hidden)
WINDOWS = {
    "login":         ("Odigo",                 340, 430, 520, 180, False, False),
    "people-finder": ("People Finder",         300, 640, 430,  60, False, True),
    "filter":        ("People Finder Filter",  320, 500,  70, 120, False, True),
    "details":       ("Details",               520, 440, 760,  60, True,  True),
    "communication": ("Communication Center",  560, 380, 150, 470, True,  True),
    "status":        ("Status",                250, 120, 340, 720, False, True),
    "send":          ("Send",                  220,  96,  70, 720, False, True),
}
AFTER_LOGIN = ["people-finder", "status", "send"]


# ---------------------------------------------------------------------------
# API client — synchronous requests wrapped in a QThreadPool so the GUI never
# blocks on the network. Results are delivered back on the GUI thread via a
# queued signal.
# ---------------------------------------------------------------------------
class Api:
    def __init__(self, base: str = API_BASE):
        self.base = base
        self.s = requests.Session()

    def _url(self, path: str) -> str:
        return path if path.startswith("http") else self.base + path

    def get(self, path: str, params: dict | None = None):
        r = self.s.get(self._url(path), params=params, timeout=15)
        r.raise_for_status()
        return r.json()

    def post(self, path: str, payload: dict):
        r = self.s.post(self._url(path), json=payload, timeout=15)
        r.raise_for_status()
        return r.json()


API = Api()
_POOL = QThreadPool.globalInstance()
_INFLIGHT: set = set()


class _TaskSignals(QObject):
    done = pyqtSignal(object)
    error = pyqtSignal(str)


class _Task(QRunnable):
    def __init__(self, fn, signals: _TaskSignals):
        super().__init__()
        self.fn = fn
        self.signals = signals

    def run(self):
        try:
            self.signals.done.emit(self.fn())
        except Exception as e:  # network / decode / HTTP errors
            self.signals.error.emit(str(e))


def call(fn, on_done=None, on_error=None):
    """Run `fn()` off the GUI thread; deliver result to `on_done` on the GUI thread."""
    sig = _TaskSignals()
    _INFLIGHT.add(sig)

    def _cleanup(*_):
        _INFLIGHT.discard(sig)

    if on_done:
        sig.done.connect(on_done)
    if on_error:
        sig.error.connect(on_error)
    sig.done.connect(_cleanup)
    sig.error.connect(_cleanup)
    _POOL.start(_Task(fn, sig))


def api_get(path, params=None, on_done=None, on_error=None):
    call(lambda: API.get(path, params), on_done, on_error)


def api_post(path, payload, on_done=None, on_error=None):
    call(lambda: API.post(path, payload), on_done, on_error)


# ---------------------------------------------------------------------------
# Cross-window signal bus — replaces bus.js emit/on + /cmd/state polling.
# ---------------------------------------------------------------------------
class Bus(QObject):
    select = pyqtSignal(str)          # person handle chosen anywhere
    filters = pyqtSignal(dict)        # {"filters": {...}, "search": "..."}
    stats_changed = pyqtSignal()      # a friend added / message sent


# ---------------------------------------------------------------------------
# Pixel-doll sprite — same 7x9 art + palette as bus.js sprite().
# ---------------------------------------------------------------------------
_SPR = ["..eee..", "e.eee.e", "..eee..", "...b...", "..bbb..",
        ".bbbbb.", "bbbbbbb", ".s...s.", ".s...s."]
_PAL = {
    "gold":   ("#f2b81e", "#b07a08"), "orange": ("#f09a28", "#a85e0a"),
    "blue":   ("#7db0e6", "#2f5f9c"), "green":  ("#7ee08a", "#1f8f3a"),
    "pink":   ("#f28bb8", "#b04a7a"),
}


def sprite_pixmap(px: float, key: str = "gold") -> QPixmap:
    body, dark = _PAL.get(key, _PAL["gold"])
    body_c, dark_c = QColor(body), QColor(dark)
    w, h = round(7 * px), round(9 * px)
    pm = QPixmap(w, h)
    pm.fill(Qt.GlobalColor.transparent)
    p = QPainter(pm)
    for y, row in enumerate(_SPR):
        for x, c in enumerate(row):
            if c == ".":
                continue
            p.fillRect(QRectF(x * px, y * px, px, px), dark_c if c == "s" else body_c)
    p.end()
    return pm


# ---------------------------------------------------------------------------
# Frameless, draggable window base + a draggable strip that moves the window.
# ---------------------------------------------------------------------------
class Draggable(QWidget):
    """A widget that drags its top-level window when pressed on empty area.
    Child buttons keep their own clicks (they consume the press first)."""
    def mousePressEvent(self, e):
        if e.button() == Qt.MouseButton.LeftButton:
            self._start = e.globalPosition().toPoint()
            self._winpos = self.window().frameGeometry().topLeft()
        else:
            self._start = None

    def mouseMoveEvent(self, e):
        if getattr(self, "_start", None) is not None:
            delta = e.globalPosition().toPoint() - self._start
            self.window().move(self._winpos + delta)

    def mouseReleaseEvent(self, e):
        self._start = None


class FramelessWindow(QWidget):
    def __init__(self, name: str):
        super().__init__(None, Qt.WindowType.FramelessWindowHint)
        title, w, h, x, y, resizable, hidden = WINDOWS[name]
        self.win_name = name
        self.setWindowTitle(title)
        self.setGeometry(x, y, w, h)
        if not resizable:
            self.setFixedSize(w, h)
        self.setAttribute(Qt.WidgetAttribute.WA_TranslucentBackground)
        self._start_hidden = hidden

    def show_at_start(self):
        if not self._start_hidden:
            self.show()


def win_button(text: str, on_click, color: str = "#eaf2fc") -> QPushButton:
    b = QPushButton(text)
    b.setObjectName("winbtn")
    b.setCursor(QCursor(Qt.CursorShape.PointingHandCursor))
    b.setFixedSize(18, 18)
    b.setStyleSheet(
        "QPushButton#winbtn{background:transparent;border:0;font-weight:bold;"
        f"font-size:13px;color:{color};}}QPushButton#winbtn:hover{{color:#fff;}}")
    b.clicked.connect(on_click)
    return b


# ---------------------------------------------------------------------------
# Window manager — mirrors the /cmd open/close/login semantics in main.go.
# ---------------------------------------------------------------------------
class WindowManager:
    def __init__(self):
        self.windows: dict[str, FramelessWindow] = {}

    def register(self, name: str, w: FramelessWindow):
        self.windows[name] = w

    def open(self, name: str):
        w = self.windows.get(name)
        if w:
            w.show()
            w.raise_()
            w.activateWindow()

    def close(self, name: str):
        # Closing the hub or the login window quits the whole app (as in main.go).
        if name in ("people-finder", "login"):
            QApplication.instance().quit()
        else:
            w = self.windows.get(name)
            if w:
                w.hide()

    def login(self):
        for n in AFTER_LOGIN:
            self.open(n)
        self.open("people-finder")
        w = self.windows.get("login")
        if w:
            w.hide()
