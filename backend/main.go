package main

import (
	"context"
	"encoding/json"
	"log"
	"net/http"
	"os"
	"path/filepath"
	"strconv"
	"time"

	"github.com/go-chi/chi/v5"
	"github.com/go-chi/chi/v5/middleware"
)

var panels = map[string]bool{
	"people-finder": true, "filter": true, "details": true,
	"communication": true, "status": true, "send": true,
}

func main() {
	port := env("PORT", "8080")
	dsn := os.Getenv("DATABASE_URL")
	if dsn == "" {
		log.Fatal("DATABASE_URL is required")
	}
	frontend := env("FRONTEND_DIR", "./frontend")

	ctx := context.Background()
	store, err := NewStore(ctx, dsn)
	if err != nil {
		log.Fatalf("store: %v", err)
	}
	log.Printf("odigo backend: db connected, frontend=%s", frontend)

	r := chi.NewRouter()
	r.Use(middleware.Logger)
	r.Use(middleware.Recoverer)
	r.Use(cors)

	api := &API{store: store}
	r.Route("/odigo", func(r chi.Router) {
		r.Get("/filters", api.filters)
		r.Get("/people", api.people)
		r.Get("/person/{handle}", api.person)
		r.Get("/stats", api.stats)
		r.Get("/messages/{handle}", api.history)
		r.Post("/messages", api.send)
		r.Post("/friends", api.addFriend)
		// static assets shared by the panels
		r.Get("/skin.css", serveFile(filepath.Join(frontend, "odigo", "skin.css")))
		r.Get("/bus.js", serveFile(filepath.Join(frontend, "odigo", "bus.js")))
	})

	// web shell + panels
	r.Get("/", serveFile(filepath.Join(frontend, "index.html")))
	r.Get("/w/{panel}", func(w http.ResponseWriter, r *http.Request) {
		p := chi.URLParam(r, "panel")
		if !panels[p] {
			http.NotFound(w, r)
			return
		}
		http.ServeFile(w, r, filepath.Join(frontend, "w", p+".html"))
	})
	r.Get("/health", func(w http.ResponseWriter, r *http.Request) { w.Write([]byte("ok")) })

	srv := &http.Server{
		Addr:              ":" + port,
		Handler:           r,
		ReadHeaderTimeout: 10 * time.Second,
	}
	log.Printf("listening on :%s", port)
	log.Fatal(srv.ListenAndServe())
}

func env(k, def string) string {
	if v := os.Getenv(k); v != "" {
		return v
	}
	return def
}

func serveFile(path string) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) { http.ServeFile(w, r, path) }
}

// cors allows the desktop webview + web preview to call the API.
func cors(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Access-Control-Allow-Origin", "*")
		w.Header().Set("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Accept")
		if r.Method == http.MethodOptions {
			w.WriteHeader(http.StatusNoContent)
			return
		}
		next.ServeHTTP(w, r)
	})
}

// ---- API handlers ----

type API struct{ store *Store }

func writeJSON(w http.ResponseWriter, v any) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(v)
}

func (a *API) filters(w http.ResponseWriter, r *http.Request) {
	f, err := a.store.Filters(r.Context())
	if err != nil {
		http.Error(w, err.Error(), 500)
		return
	}
	writeJSON(w, f)
}

func (a *API) people(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	page, _ := strconv.Atoi(q.Get("page"))
	cards, total, pg, pages, err := a.store.People(r.Context(), PeopleQuery{
		Topic: q.Get("topic"), AgeGroup: q.Get("ageGroup"), Gender: q.Get("gender"),
		Region: q.Get("region"), Language: q.Get("language"), Occupation: q.Get("occupation"),
		Status: q.Get("status"), Mood: q.Get("mood"), Intention: q.Get("intention"),
		Zodiac: q.Get("zodiac"), Search: q.Get("search"), Page: page,
	})
	if err != nil {
		http.Error(w, err.Error(), 500)
		return
	}
	writeJSON(w, map[string]any{"people": cards, "total": total, "page": pg, "pages": pages})
}

func (a *API) person(w http.ResponseWriter, r *http.Request) {
	p, err := a.store.Person(r.Context(), chi.URLParam(r, "handle"))
	if err != nil {
		http.NotFound(w, r)
		return
	}
	writeJSON(w, p)
}

func (a *API) stats(w http.ResponseWriter, r *http.Request) {
	s, err := a.store.Stats(r.Context())
	if err != nil {
		http.Error(w, err.Error(), 500)
		return
	}
	writeJSON(w, s)
}

func (a *API) history(w http.ResponseWriter, r *http.Request) {
	handle := chi.URLParam(r, "handle")
	msgs, err := a.store.History(r.Context(), handle)
	if err != nil {
		http.Error(w, err.Error(), 500)
		return
	}
	writeJSON(w, map[string]any{"peer": handle, "messages": msgs})
}

func (a *API) send(w http.ResponseWriter, r *http.Request) {
	var in struct{ To, Body, Type string }
	if err := json.NewDecoder(r.Body).Decode(&in); err != nil || in.To == "" || in.Body == "" {
		http.Error(w, "bad request", 400)
		return
	}
	if err := a.store.Send(r.Context(), in.To, in.Body, in.Type); err != nil {
		http.Error(w, err.Error(), 500)
		return
	}
	writeJSON(w, map[string]any{"ok": true, "status": "Message sent to " + in.To})
}

func (a *API) addFriend(w http.ResponseWriter, r *http.Request) {
	var in struct{ Handle string }
	if err := json.NewDecoder(r.Body).Decode(&in); err != nil || in.Handle == "" {
		http.Error(w, "bad request", 400)
		return
	}
	dn, err := a.store.AddFriend(r.Context(), in.Handle)
	if err != nil {
		http.Error(w, err.Error(), 500)
		return
	}
	writeJSON(w, map[string]any{"ok": true, "status": dn + " added to your friends"})
}
