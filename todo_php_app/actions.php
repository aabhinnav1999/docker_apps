<?php
declare(strict_types=1);
require_once __DIR__ . "/db.php";

function redirect_keep_filter(): void {
    $filter = $_GET["filter"] ?? "all";
    if (!in_array($filter, ["all", "active", "completed"], true)) $filter = "all";
    header("Location: /?filter=" . urlencode($filter));
    exit;
}

function add_todo(string $text): void {
    $text = trim($text);
    if ($text === "") return;

    $pdo = db();
    $stmt = $pdo->prepare("INSERT INTO todos (text, completed, created_at) VALUES (:text, 0, :created_at)");
    $stmt->execute([
        ":text" => $text,
        ":created_at" => gmdate("c"),
    ]);
}

function toggle_todo(int $id): void {
    $pdo = db();
    // Flip completed (0->1, 1->0)
    $stmt = $pdo->prepare("UPDATE todos SET completed = CASE completed WHEN 1 THEN 0 ELSE 1 END WHERE id = :id");
    $stmt->execute([":id" => $id]);
}

function delete_todo(int $id): void {
    $pdo = db();
    $stmt = $pdo->prepare("DELETE FROM todos WHERE id = :id");
    $stmt->execute([":id" => $id]);
}

function clear_completed(): void {
    $pdo = db();
    $pdo->exec("DELETE FROM todos WHERE completed = 1");
}
