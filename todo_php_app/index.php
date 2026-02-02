<?php
declare(strict_types=1);

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/actions.php";

// Handle POST actions (simple routing)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // Basic CSRF protection (lightweight)
    session_start();
    if (!isset($_SESSION["csrf"])) $_SESSION["csrf"] = bin2hex(random_bytes(16));
    $csrf = $_POST["csrf"] ?? "";
    if (!hash_equals($_SESSION["csrf"], $csrf)) {
        http_response_code(403);
        die("Invalid CSRF token");
    }

    if ($action === "add") {
        add_todo($_POST["text"] ?? "");
        redirect_keep_filter();
    }
    if ($action === "toggle") {
        toggle_todo((int)($_POST["id"] ?? 0));
        redirect_keep_filter();
    }
    if ($action === "delete") {
        delete_todo((int)($_POST["id"] ?? 0));
        redirect_keep_filter();
    }
    if ($action === "clear_completed") {
        clear_completed();
        redirect_keep_filter();
    }

    redirect_keep_filter();
}

// GET: Render page
$filter = $_GET["filter"] ?? "all";
if (!in_array($filter, ["all", "active", "completed"], true)) $filter = "all";

$pdo = db();

$where = "";
$params = [];
if ($filter === "active") {
    $where = "WHERE completed = 0";
} elseif ($filter === "completed") {
    $where = "WHERE completed = 1";
}

$todos = $pdo->query("SELECT id, text, completed, created_at FROM todos $where ORDER BY datetime(created_at) DESC")->fetchAll();

$activeCount = (int)$pdo->query("SELECT COUNT(*) AS c FROM todos WHERE completed = 0")->fetch()["c"];
$completedCount = (int)$pdo->query("SELECT COUNT(*) AS c FROM todos WHERE completed = 1")->fetch()["c"];

// CSRF token for forms
session_start();
if (!isset($_SESSION["csrf"])) $_SESSION["csrf"] = bin2hex(random_bytes(16));
$csrf = $_SESSION["csrf"];

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>To-Do (PHP)</title>

    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/app.css" rel="stylesheet">
  </head>

  <body class="bg-dark text-white">
    <main class="container py-5" style="max-width: 860px;">
      <header class="mb-4">
        <h1 class="fw-bold">To-Do</h1>
        <div class="text-white-50">PHP + SQLite • simple, persistent.</div>
      </header>

      <section class="card card-soft rounded-4 shadow-lg">
        <div class="p-4 border-bottom border-soft">
          <form class="d-flex gap-2" method="post">
            <input type="hidden" name="action" value="add" />
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>" />
            <input
              class="form-control form-control-lg bg-black border-0 text-white"
              name="text"
              maxlength="140"
              placeholder="Add a task… (Enter)"
              autofocus
              required
            />
            <button class="btn btn-light btn-lg fw-semibold" type="submit">Add</button>
          </form>

          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
            <div class="text-white-50">
              <span class="fw-semibold text-white"><?= $activeCount ?></span> active •
              <span class="fw-semibold text-white"><?= $completedCount ?></span> completed
            </div>

            <div class="btn-group" role="group" aria-label="filters">
              <a class="btn btn-outline-light btn-filter <?= $filter==="all" ? "active" : "" ?>" href="/?filter=all">All</a>
              <a class="btn btn-outline-light btn-filter <?= $filter==="active" ? "active" : "" ?>" href="/?filter=active">Active</a>
              <a class="btn btn-outline-light btn-filter <?= $filter==="completed" ? "active" : "" ?>" href="/?filter=completed">Completed</a>
            </div>
          </div>
        </div>

        <ul class="list-group list-group-flush">
          <?php if (count($todos) > 0): ?>
            <?php foreach ($todos as $t): ?>
              <li class="list-group-item bg-transparent text-white d-flex align-items-center justify-content-between py-3 border-soft">
                <div class="d-flex align-items-center gap-3">
                  <form method="post" class="m-0">
                    <input type="hidden" name="action" value="toggle" />
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>" />
                    <input type="hidden" name="id" value="<?= (int)$t["id"] ?>" />
                    <button class="btn btn-sm <?= ((int)$t["completed"]===1) ? "btn-success" : "btn-outline-light" ?>" type="submit">
                      <?= ((int)$t["completed"]===1) ? "Done" : "Mark" ?>
                    </button>
                  </form>

                  <div class="todo-text <?= ((int)$t["completed"]===1) ? "completed" : "" ?>">
                    <?= h((string)$t["text"]) ?>
                  </div>
                </div>

                <form method="post" class="m-0">
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="csrf" value="<?= h($csrf) ?>" />
                  <input type="hidden" name="id" value="<?= (int)$t["id"] ?>" />
                  <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                </form>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="list-group-item bg-transparent text-white-50 py-4 border-soft">
              No tasks here. Add one above 👆
            </li>
          <?php endif; ?>
        </ul>

        <div class="p-4 d-flex justify-content-between align-items-center gap-3">
          <form method="post" class="m-0">
            <input type="hidden" name="action" value="clear_completed" />
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>" />
            <button class="btn btn-link text-decoration-none text-white-50 p-0" type="submit">
              Clear completed
            </button>
          </form>
          <div class="text-white-50 small">Stored in SQLite: <code class="text-white-50">todo.sqlite</code></div>
        </div>
      </section>
    </main>
  </body>
</html>
