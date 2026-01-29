<?php
// db.php - SQLITE (a prova di errore)
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    // Percorso assoluto del file SQLite
    $path = __DIR__ . '/database.sqlite';
    $dsn  = 'sqlite:' . $path;

    // DEBUG (se vuoi vedere il DSN nei log, scommenta)
    // error_log("Using DSN: " . $dsn);

    $pdo = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
