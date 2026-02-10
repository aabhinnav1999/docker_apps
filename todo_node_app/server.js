const express = require("express");
const fs = require("fs");
const path = require("path");

const app = express();
const PORT = process.env.PORT || 3000;

const DATA_DIR = path.join(__dirname, "data");
const DATA_FILE = path.join(DATA_DIR, "todos.json");

app.use(express.json());
app.use(express.static(path.join(__dirname, "public")));

function readTodos() {
  try {
    const raw = fs.readFileSync(DATA_FILE, "utf-8");
    return JSON.parse(raw);
  } catch (e) {
    return [];
  }
}

function writeTodos(todos) {
  if (!fs.existsSync(DATA_DIR)) fs.mkdirSync(DATA_DIR, { recursive: true });
  fs.writeFileSync(DATA_FILE, JSON.stringify(todos, null, 2), "utf-8");
}

function newId() {
  // simple unique id without extra libs
  return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

// --- API ---
app.get("/api/todos", (req, res) => {
  res.json(readTodos());
});

app.post("/api/todos", (req, res) => {
  const { text } = req.body || {};
  if (!text || !String(text).trim()) {
    return res.status(400).json({ error: "text is required" });
  }

  const todos = readTodos();
  const todo = {
    id: newId(),
    text: String(text).trim(),
    completed: false,
    createdAt: new Date().toISOString(),
  };

  todos.unshift(todo);
  writeTodos(todos);
  res.status(201).json(todo);
});

app.patch("/api/todos/:id", (req, res) => {
  const { id } = req.params;
  const { text, completed } = req.body || {};

  const todos = readTodos();
  const idx = todos.findIndex((t) => t.id === id);
  if (idx === -1) return res.status(404).json({ error: "not found" });

  if (typeof text === "string") todos[idx].text = text.trim();
  if (typeof completed === "boolean") todos[idx].completed = completed;

  writeTodos(todos);
  res.json(todos[idx]);
});

app.delete("/api/todos/:id", (req, res) => {
  const { id } = req.params;
  const todos = readTodos();
  const filtered = todos.filter((t) => t.id !== id);
  if (filtered.length === todos.length) return res.status(404).json({ error: "not found" });

  writeTodos(filtered);
  res.status(204).send();
});

app.delete("/api/todos", (req, res) => {
  // clear completed
  const todos = readTodos();
  const active = todos.filter((t) => !t.completed);
  writeTodos(active);
  res.json({ removed: todos.length - active.length });
});

// Serve UI
app.get("/", (req, res) => {
  res.sendFile(path.join(__dirname, "public", "index.html"));
});

app.listen(PORT, "0.0.0.0", () => {
  console.log(`To-Do app running at http://localhost:${PORT}`);
});
