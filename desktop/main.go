// Odigo desktop — Wails v3 multi-window app.
//
// Window control + cross-window sync do NOT use the Wails JS<->Go event transport
// (unreliable in this alpha / headless). Instead the frontend talks to the app's
// OWN asset server over SAME-ORIGIN fetch to /cmd/*, which a middleware here handles
// directly against the native windows. State for cross-window sync (selected person,
// active filters, stats revision) lives here and windows poll /cmd/state.
//
// Flow: launch shows ONLY the Login window; after Sign In the People Finder (hub) +
// ambient Status/Send appear; Filter/Details/Communication open on demand. Each panel
// is its own frameless native window, draggable anywhere.
package main

import (
	"embed"
	"encoding/json"
	"io"
	"net/http"
	"strings"
	"sync"

	"github.com/wailsapp/wails/v3/pkg/application"
)

//go:embed all:frontend/dist
var assets embed.FS

type winCfg struct {
	name, title string
	w, h, x, y  int
	resizable   bool
	hidden      bool
}

var windows = []winCfg{
	{"login", "Odigo", 340, 430, 520, 180, false, false},
	{"people-finder", "People Finder", 300, 640, 430, 60, false, true},
	{"filter", "People Finder Filter", 320, 500, 70, 120, false, true},
	{"details", "Details", 520, 440, 760, 60, true, true},
	{"communication", "Communication Center", 560, 380, 150, 470, true, true},
	{"status", "Status", 250, 120, 340, 720, false, true},
	{"send", "Send", 220, 96, 70, 720, false, true},
}

var afterLogin = []string{"people-finder", "status", "send"}

// Shared UI state for cross-window sync (polled by windows via /cmd/state).
type shared struct {
	mu        sync.Mutex
	rev       int    // bumps on any change
	selection string // selected person handle
	filters   string // raw JSON: {filters:{...}, search:""}
	statsRev  int    // bumps when stats should refresh
}

func main() {
	wins := map[string]*application.WebviewWindow{}
	st := &shared{}
	var app *application.App

	// /cmd/* middleware: same-origin control channel (no Wails event transport).
	cmd := func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			if !strings.HasPrefix(r.URL.Path, "/cmd/") {
				next.ServeHTTP(w, r)
				return
			}
			seg := strings.Split(strings.Trim(strings.TrimPrefix(r.URL.Path, "/cmd/"), "/"), "/")
			w.Header().Set("Content-Type", "application/json")
			// Window operations MUST run on the main thread (this handler runs on an
			// HTTP goroutine); InvokeSync dispatches to it.
			switch seg[0] {
			case "login":
				application.InvokeSync(func() {
					for _, n := range afterLogin {
						if wins[n] != nil {
							wins[n].Show()
						}
					}
					if wins["people-finder"] != nil {
						wins["people-finder"].Focus()
					}
					if wins["login"] != nil {
						wins["login"].Hide()
					}
				})
			case "open":
				if len(seg) > 1 {
					name := seg[1]
					application.InvokeSync(func() {
						if wins[name] != nil {
							wins[name].Show()
							wins[name].Focus()
						}
					})
				}
			case "close":
				if len(seg) > 1 {
					name := seg[1]
					application.InvokeSync(func() {
						switch name {
						case "people-finder", "login":
							if app != nil {
								app.Quit()
							}
						default:
							if wins[name] != nil {
								wins[name].Hide()
							}
						}
					})
				}
			case "select":
				if len(seg) > 1 {
					st.mu.Lock()
					st.selection = seg[1]
					st.rev++
					st.mu.Unlock()
				}
			case "filters":
				body, _ := io.ReadAll(io.LimitReader(r.Body, 8192))
				st.mu.Lock()
				st.filters = string(body)
				st.rev++
				st.mu.Unlock()
			case "stats":
				st.mu.Lock()
				st.statsRev++
				st.rev++
				st.mu.Unlock()
			case "state":
				st.mu.Lock()
				out := map[string]any{"rev": st.rev, "selection": st.selection, "filters": st.filters, "statsRev": st.statsRev}
				st.mu.Unlock()
				json.NewEncoder(w).Encode(out)
				return
			}
			w.Write([]byte(`{"ok":true}`))
		})
	}

	app = application.New(application.Options{
		Name:        "Odigo",
		Description: "Odigo Messenger",
		Assets: application.AssetOptions{
			Handler:    application.AssetFileServerFS(assets),
			Middleware: cmd,
		},
		Mac: application.MacOptions{
			ApplicationShouldTerminateAfterLastWindowClosed: false,
		},
	})

	for _, c := range windows {
		wins[c.name] = app.Window.NewWithOptions(application.WebviewWindowOptions{
			Name:             c.name,
			Title:            c.title,
			Width:            c.w,
			Height:           c.h,
			X:                c.x,
			Y:                c.y,
			Hidden:           c.hidden,
			Frameless:        true,
			DisableResize:    !c.resizable,
			BackgroundColour: application.NewRGB(85, 88, 92),
			URL:              "/w/" + c.name + ".html",
		})
	}

	if err := app.Run(); err != nil {
		panic(err)
	}
}
