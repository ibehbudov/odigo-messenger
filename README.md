# Odigo

Retro-styled multi-window messenger. **Go monorepo:**

```
backend/    Go API + web server (chi + pgx → PostgreSQL). Serves the JSON API
            and the web preview. Deployed at api-odigo.your.team + odigo.your.team.
frontend/   Shared retro UI (skin.css, bus.js, one HTML per panel + web shell).
            Used by the web preview AND embedded into the desktop app.
desktop/    Wails v3 (Go) desktop app — each panel is its own frameless native
            window, draggable anywhere. Talks to https://api-odigo.your.team.
deploy/     Docker Compose (Go backend container on www_appnet + Postgres).
```

## Backend

```bash
cd backend
DATABASE_URL='postgres://odigo:...@localhost:5432/odigo?sslmode=disable' \
  FRONTEND_DIR=../frontend go run .
```

Endpoints (`/odigo/*`): `filters`, `people` (filter + `search` + `page`, 10/pg),
`person/{handle}`, `stats`, `messages/{handle}`, `POST messages`, `POST friends`.
Schema + mock seed (60 people, sample history) run automatically on first start.

## Desktop (Wails v3)

Requires Go 1.25 and, on Linux, `gtk4` + `webkitgtk-6.0`. `wails3` CLI:
`go install github.com/wailsapp/wails/v3/cmd/wails3@v3.0.0-alpha2.117`.

```bash
# sync the shared frontend into the embed dir, then build
rm -rf desktop/frontend/dist && mkdir -p desktop/frontend/dist
cp -r frontend/odigo frontend/w frontend/index.html desktop/frontend/dist/
cd desktop && go build -o odigo .
```

CI (`.github/workflows/build.yml`) builds Linux/Windows/macOS on tag `v*`.
See [INSTALL.md](INSTALL.md) for the macOS "damaged"/unsigned note.

## Deploy

```bash
docker compose -f deploy/docker-compose.yml -p odigo up -d --build --remove-orphans
```
Backend joins the shared `www_appnet` and connects to `www-pgsql-1`. The ingress
routes `odigo.your.team` (web) and `api-odigo.your.team` (API) to `odigo-api:8080`.
