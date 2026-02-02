<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dbPath = __DIR__ . DIRECTORY_SEPARATOR . "todo.sqlite";
    $pdo = new PDO("sqlite:" . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Initialize schema (safe to run every time)
    $schema = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "schema.sql");
    $pdo->exec($schema);

    return $pdo;
}
