require "sinatra"
require "sqlite3"

set :bind, "0.0.0.0"
set :port, 4567

DB = SQLite3::Database.new "todo.sqlite"
DB.results_as_hash = true

# Create table if not exists
DB.execute <<-SQL
CREATE TABLE IF NOT EXISTS todos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  text TEXT NOT NULL,
  completed INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
SQL

helpers do
  def redirect_keep_filter
    filter = params[:filter] || "all"
    redirect "/?filter=#{filter}"
  end
end

get "/" do
  filter = params[:filter] || "all"

  query = "SELECT * FROM todos"
  if filter == "active"
    query += " WHERE completed = 0"
  elsif filter == "completed"
    query += " WHERE completed = 1"
  end

  query += " ORDER BY created_at DESC"

  todos = DB.execute(query)

  active_count = DB.get_first_value("SELECT COUNT(*) FROM todos WHERE completed = 0")
  completed_count = DB.get_first_value("SELECT COUNT(*) FROM todos WHERE completed = 1")

  erb :index, locals: {
    todos: todos,
    filter: filter,
    active_count: active_count,
    completed_count: completed_count
  }
end

post "/add" do
  text = params[:text].to_s.strip
  if !text.empty?
    DB.execute("INSERT INTO todos (text, completed) VALUES (?, 0)", [text])
  end
  redirect_keep_filter
end

post "/toggle" do
  id = params[:id].to_i
  DB.execute("UPDATE todos SET completed = CASE completed WHEN 1 THEN 0 ELSE 1 END WHERE id = ?", [id])
  redirect_keep_filter
end

post "/delete" do
  id = params[:id].to_i
  DB.execute("DELETE FROM todos WHERE id = ?", [id])
  redirect_keep_filter
end

post "/clear-completed" do
  DB.execute("DELETE FROM todos WHERE completed = 1")
  redirect_keep_filter
end
