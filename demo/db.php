<?php
// db.php - MYSQL/MariaDB (PDO)
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Consigliato: emulazione off per avere errori più chiari su query
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        // Debug utile: mostrare il motivo (in dev)
        http_response_code(500);
        echo "DB connection failed: " . htmlspecialchars($e->getMessage());
        exit;
    }

    return $pdo;
}