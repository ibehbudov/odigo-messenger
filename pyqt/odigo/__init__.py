"""Odigo desktop — native PyQt6 port of the retro multi-window messenger.

Faithful reimplementation of the Wails/Go desktop app: each panel is its own
frameless, draggable native window. Talks to the SAME Go backend the web/Wails
builds use (https://api-odigo.your.team, /odigo/* JSON API).

Cross-window sync (selection / filters / stats) uses native Qt signals via a
shared `Bus` — no HTTP `/cmd/state` polling like the Wails build needed.
"""
