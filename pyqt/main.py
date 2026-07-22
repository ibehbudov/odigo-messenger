#!/usr/bin/env python3
"""Odigo desktop — native PyQt6 entry point.

Creates the seven frameless windows, wires them to a shared signal Bus and the
WindowManager, and shows only the Login window at launch (Sign In reveals the
hub + ambient panels — same flow as the Wails build).

Run:  python3 main.py
"""
import sys

from PyQt6.QtWidgets import QApplication

from odigo.core import Bus, WindowManager
from odigo import skin
from odigo.panels import (
    LoginWindow, PeopleFinderWindow, FilterWindow, DetailsWindow,
    CommunicationWindow, StatusWindow, SendWindow,
)

PANELS = {
    "login": LoginWindow,
    "people-finder": PeopleFinderWindow,
    "filter": FilterWindow,
    "details": DetailsWindow,
    "communication": CommunicationWindow,
    "status": StatusWindow,
    "send": SendWindow,
}


def main():
    app = QApplication(sys.argv)
    app.setApplicationName("Odigo")
    app.setStyleSheet(skin.GLOBAL_QSS)

    bus = Bus()
    wm = WindowManager()
    for name, cls in PANELS.items():
        win = cls(wm, bus)
        wm.register(name, win)

    # Only the login window is visible at start; the rest are shown after Sign In.
    for name, win in wm.windows.items():
        win.show_at_start()

    sys.exit(app.exec())


if __name__ == "__main__":
    main()
