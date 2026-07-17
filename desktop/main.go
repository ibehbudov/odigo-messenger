// Odigo desktop — Wails v3 multi-window app.
// Flow: launch shows ONLY the Login window. After sign-in, the People Finder
// (hub) opens together with the ambient Status + Send widgets; Filter, Details
// and Communication open on demand (People Finder buttons / clicking a person).
// Each panel is its own frameless native window, draggable anywhere. Panels talk
// to the Go backend at https://api-odigo.your.team and sync via the Wails event bus.
package main

import (
	"embed"
	"fmt"
	"log"

	"github.com/wailsapp/wails/v3/pkg/application"
)

//go:embed all:frontend/dist
var assets embed.FS

type winCfg struct {
	name, title string
	w, h, x, y  int
	resizable   bool
	hidden      bool // hidden until sign-in / opened on demand
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

// Windows revealed right after sign-in (the rest open on demand).
var afterLogin = []string{"people-finder", "status", "send"}

func main() {
	app := application.New(application.Options{
		Name:        "Odigo",
		Description: "Odigo Messenger",
		Assets: application.AssetOptions{
			Handler: application.AssetFileServerFS(assets),
		},
		Mac: application.MacOptions{
			ApplicationShouldTerminateAfterLastWindowClosed: false,
		},
	})

	wins := make(map[string]*application.WebviewWindow, len(windows))
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

	// Sign-in: reveal the messenger, dismiss the login window.
	app.Event.On("odigo:login", func(e *application.CustomEvent) {
		for _, name := range afterLogin {
			if win := wins[name]; win != nil {
				win.Show()
			}
		}
		if win := wins["people-finder"]; win != nil {
			win.Focus()
		}
		if lg := wins["login"]; lg != nil {
			lg.Hide()
		}
	})

	// Open (or re-focus) a panel window on demand.
	app.Event.On("odigo:win-open", func(e *application.CustomEvent) {
		if win := wins[fmt.Sprintf("%v", e.Data)]; win != nil {
			win.Show()
			win.Focus()
		}
	})

	// Close a window. The hub (People Finder) and the Login window quit the app;
	// every other panel just hides so it can be reopened.
	app.Event.On("odigo:win-close", func(e *application.CustomEvent) {
		name := fmt.Sprintf("%v", e.Data)
		switch name {
		case "people-finder", "login":
			app.Quit()
		default:
			if win := wins[name]; win != nil {
				win.Hide()
			}
		}
	})

	if err := app.Run(); err != nil {
		log.Fatal(err)
	}
}
