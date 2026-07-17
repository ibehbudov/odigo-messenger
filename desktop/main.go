// Odigo desktop — Wails v3 multi-window app.
// Each Odigo panel is its OWN frameless native window that can be dragged
// anywhere on the desktop (titlebars use CSS --wails-non-client-region:caption).
// Panels talk to the Go backend at https://api-odigo.your.team over HTTP, and
// sync with each other via the Wails event bus. Window open/close is delegated
// here from JS via the 'odigo:win-open' / 'odigo:win-close' events.
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
	name, title          string
	w, h, x, y           int
	resizable            bool
}

// Same layout as the web shell / the old NativePHP windows.
var windows = []winCfg{
	{"people-finder", "People Finder", 300, 640, 430, 60, false},
	{"filter", "People Finder Filter", 320, 500, 70, 120, false},
	{"details", "Details", 520, 440, 760, 60, true},
	{"communication", "Communication Center", 560, 380, 150, 470, true},
	{"status", "Status", 250, 120, 340, 720, false},
	{"send", "Send", 220, 96, 70, 720, false},
}

func main() {
	app := application.New(application.Options{
		Name:        "Odigo",
		Description: "Odigo Messenger",
		Assets: application.AssetOptions{
			Handler: application.AssetFileServerFS(assets),
		},
		Mac: application.MacOptions{
			ApplicationShouldTerminateAfterLastWindowClosed: true,
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
			Frameless:        true,
			DisableResize:    !c.resizable,
			BackgroundColour: application.NewRGB(85, 88, 92),
			URL:              "/w/" + c.name + ".html",
		})
	}

	// JS asks to (re)open or close a panel window.
	app.Event.On("odigo:win-open", func(e *application.CustomEvent) {
		if win := wins[fmt.Sprintf("%v", e.Data)]; win != nil {
			win.Show()
			win.Focus()
		}
	})
	app.Event.On("odigo:win-close", func(e *application.CustomEvent) {
		if win := wins[fmt.Sprintf("%v", e.Data)]; win != nil {
			win.Hide()
		}
	})

	if err := app.Run(); err != nil {
		log.Fatal(err)
	}
}
