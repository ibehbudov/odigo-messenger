# Odigo desktop — PyQt6 port

A native **PyQt6** reimplementation of the retro multi-window Odigo messenger
(originally Wails v3 / Go). Each panel is its own frameless, draggable OS window.
It talks to the **same Go backend** the web/Wails builds use — no local database.

```
main.py            entry — creates the 7 windows, shows only Login at launch
odigo/core.py      config, async API client, cross-window signal Bus,
                   pixel-doll sprites, frameless/draggable base, WindowManager
odigo/skin.py      the retro skin as Qt Style Sheets (translation of skin.css)
odigo/panels.py    the 7 windows + the radar widget (QPainter sweep)
```

## Panels & flow

`login` → Sign In reveals the hub (`people-finder` radar) plus the ambient
`status` and `send` widgets. `filter`, `details` and `communication` open on
demand. Click a dot on the radar → Details; double-click → Communication Center.

Cross-window sync (selected person, active filters, stats refresh) uses native
Qt signals (`odigo/core.py::Bus`) instead of the Wails build's `/cmd/state`
HTTP polling.

## Backend

Uses `https://api-odigo.your.team` (`/odigo/*`) by default — set in
`odigo/core.py::API_BASE`. Point it at a local backend if you run one:

```bash
# in the Go monorepo: backend/ on :8080
API_BASE=http://localhost:8080  # then edit core.py, or export & read it there
```

The `/odigo/*` endpoints used: `filters`, `people` (filters + `search` + `page`),
`person/{handle}`, `stats`, `messages/{handle}`, `POST messages`, `POST friends`.

## Run

```bash
cd pyqt
python3 -m venv .venv && . .venv/bin/activate
pip install -r requirements.txt
python3 main.py
```

Needs a desktop session (X11/Wayland or macOS/Windows). On a headless box use an
X server or `xvfb-run python3 main.py` for a smoke test.

## Notes / fidelity

- Qt Style Sheets are not CSS: no box-shadow / conic-gradient. Drop shadows are
  dropped; the radar sweep is painted by hand (`Radar.paintEvent`). Colours and
  the plastic/navy/grey gradients match the originals.
- The pixel doll sprite reuses the exact 7×9 art + palette from `bus.js`.
- Login is a demo (any password); Sign In just reveals the panels.
