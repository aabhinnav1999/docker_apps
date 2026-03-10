package main

import (
	"database/sql"
	"embed"
	"html/template"
	"io/fs"
	"log"
	"net/http"
	"strconv"
	"strings"
	"time"

	_ "modernc.org/sqlite"
)

//go:embed templates/*
var templatesFS embed.FS

//go:embed static/*
var staticFS embed.FS

type App struct {
	db   *sql.DB
	tmpl *template.Template
}

type Todo struct {
	ID        int64
	Text      string
	Completed bool
	CreatedAt string
}

type PageData struct {
	Todos          []Todo
	Filter         string
	ActiveCount    int
	CompletedCount int
}

func main() {
	db, err := sql.Open("sqlite", "todo.sqlite")
	if err != nil {
		log.Fatal(err)
	}
	defer db.Close()

	if err := migrate(db); err != nil {
		log.Fatal(err)
	}

	tmpl := template.Must(template.ParseFS(templatesFS, "templates/index.html"))
	app := &App{db: db, tmpl: tmpl}

	mux := http.NewServeMux()

	// Serve embedded static files
	staticSub, err := fs.Sub(staticFS, "static")
	if err != nil {
		log.Fatal(err)
	}
	mux.Handle("/static/", http.StripPrefix("/static/", http.FileServer(http.FS(staticSub))))

	// Routes
	mux.HandleFunc("/", app.handleIndex)
	mux.HandleFunc("/add", app.handleAdd)
	mux.HandleFunc("/toggle", app.handleToggle)
	mux.HandleFunc("/delete", app.handleDelete)
	mux.HandleFunc("/clear-completed", app.handleClearCompleted)

	addr := "0.0.0.0:8080"
	log.Println("Go To-Do running at http://" + addr)
	log.Fatal(http.ListenAndServe(addr, logRequest(mux)))
}

func migrate(db *sql.DB) error {
	_, err := db.Exec(`
CREATE TABLE IF NOT EXISTS todos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  text TEXT NOT NULL,
  completed INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL
);
`)
	return err
}

func (a *App) handleIndex(w http.ResponseWriter, r *http.Request) {
	filter := r.URL.Query().Get("filter")
	if filter == "" {
		filter = "all"
	}
	if filter != "all" && filter != "active" && filter != "completed" {
		filter = "all"
	}

	todos, err := a.listTodos(filter)
	if err != nil {
		http.Error(w, err.Error(), 500)
		return
	}

	activeCount, completedCount, err := a.counts()
	if err != nil {
		http.Error(w, err.Error(), 500)
		return
	}

	data := PageData{
		Todos:          todos,
		Filter:         filter,
		ActiveCount:    activeCount,
		CompletedCount: completedCount,
	}

	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	if err := a.tmpl.Execute(w, data); err != nil {
		http.Error(w, err.Error(), 500)
		return
	}
}

func (a *App) handleAdd(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Redirect(w, r, "/", http.StatusSeeOther)
		return
	}
	if err := r.ParseForm(); err != nil {
		http.Error(w, "bad form", 400)
		return
	}

	text := strings.TrimSpace(r.FormValue("text"))
	if text != "" {
		if err := a.addTodo(text); err != nil {
			http.Error(w, err.Error(), 500)
			return
		}
	}
	redirectKeepFilter(w, r)
}

func (a *App) handleToggle(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Redirect(w, r, "/", http.StatusSeeOther)
		return
	}
	if err := r.ParseForm(); err != nil {
		http.Error(w, "bad form", 400)
		return
	}

	id, _ := strconv.ParseInt(r.FormValue("id"), 10, 64)
	if id > 0 {
		if err := a.toggleTodo(id); err != nil {
			http.Error(w, err.Error(), 500)
			return
		}
	}
	redirectKeepFilter(w, r)
}

func (a *App) handleDelete(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Redirect(w, r, "/", http.StatusSeeOther)
		return
	}
	if err := r.ParseForm(); err != nil {
		http.Error(w, "bad form", 400)
		return
	}

	id, _ := strconv.ParseInt(r.FormValue("id"), 10, 64)
	if id > 0 {
		if err := a.deleteTodo(id); err != nil {
			http.Error(w, err.Error(), 500)
			return
		}
	}
	redirectKeepFilter(w, r)
}

func (a *App) handleClearCompleted(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Redirect(w, r, "/", http.StatusSeeOther)
		return
	}
	if err := a.clearCompleted(); err != nil {
		http.Error(w, err.Error(), 500)
		return
	}
	redirectKeepFilter(w, r)
}

func (a *App) listTodos(filter string) ([]Todo, error) {
	query := `SELECT id, text, completed, created_at FROM todos`
	switch filter {
	case "active":
		query += ` WHERE completed = 0`
	case "completed":
		query += ` WHERE completed = 1`
	}
	query += ` ORDER BY datetime(created_at) DESC`

	rows, err := a.db.Query(query)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var todos []Todo
	for rows.Next() {
		var t Todo
		var completedInt int
		if err := rows.Scan(&t.ID, &t.Text, &completedInt, &t.CreatedAt); err != nil {
			return nil, err
		}
		t.Completed = completedInt == 1
		todos = append(todos, t)
	}
	return todos, rows.Err()
}

func (a *App) counts() (active int, completed int, err error) {
	err = a.db.QueryRow(`SELECT COUNT(*) FROM todos WHERE completed = 0`).Scan(&active)
	if err != nil {
		return
	}
	err = a.db.QueryRow(`SELECT COUNT(*) FROM todos WHERE completed = 1`).Scan(&completed)
	return
}

func (a *App) addTodo(text string) error {
	_, err := a.db.Exec(
		`INSERT INTO todos (text, completed, created_at) VALUES (?, 0, ?)`,
		text,
		time.Now().UTC().Format(time.RFC3339),
	)
	return err
}

func (a *App) toggleTodo(id int64) error {
	_, err := a.db.Exec(
		`UPDATE todos SET completed = CASE completed WHEN 1 THEN 0 ELSE 1 END WHERE id = ?`,
		id,
	)
	return err
}

func (a *App) deleteTodo(id int64) error {
	_, err := a.db.Exec(`DELETE FROM todos WHERE id = ?`, id)
	return err
}

func (a *App) clearCompleted() error {
	_, err := a.db.Exec(`DELETE FROM todos WHERE completed = 1`)
	return err
}

func redirectKeepFilter(w http.ResponseWriter, r *http.Request) {
	filter := r.URL.Query().Get("filter")
	if filter == "" {
		filter = "all"
	}
	http.Redirect(w, r, "/?filter="+filter, http.StatusSeeOther)
}

func logRequest(next http.Handler) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		start := time.Now()
		next.ServeHTTP(w, r)
		log.Printf("%s %s (%s)", r.Method, r.URL.Path, time.Since(start))
	}
}