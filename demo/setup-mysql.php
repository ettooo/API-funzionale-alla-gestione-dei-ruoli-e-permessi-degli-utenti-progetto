<?php
/**
 * Script di Setup per convertire da SQLite a MySQL
 * Crea il database e applica lo schema MySQL
 */

// Credenziali amministrator MySQL (usa root come default)
$admin_host = getenv('DB_ADMIN_HOST') ?: '127.0.0.1';
$admin_user = getenv('DB_ADMIN_USER') ?: 'root';
$admin_pass = getenv('DB_ADMIN_PASS') ?: '';

// Credenziali dal config.php
require_once __DIR__ . '/config.php';

echo "🔧 Setup Database MySQL\n";
echo "=======================\n\n";

try {
    // 1. Connessione come admin per creare database e utente
    echo "1️⃣  Connessione a MySQL come admin...\n";
    $admin_pdo = new PDO(
        'mysql:host=' . $admin_host,
        $admin_user,
        $admin_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connesso come $admin_user\n\n";

    // 2. Creare database se non esiste
    echo "2️⃣  Creazione database '" . DB_NAME . "'...\n";
    $admin_pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database creato/esistente\n\n";

    // 3. Creare utente se non esiste
    echo "3️⃣  Creazione utente '" . DB_USER . "'...\n";
    try {
        $admin_pdo->exec("CREATE USER IF NOT EXISTS '" . DB_USER . "'@'" . $admin_host . "' IDENTIFIED BY '" . DB_PASS . "'");
        echo "✅ Utente creato/esistente\n";
    } catch (Exception $e) {
        echo "⚠️  Utente potrebbe già esistere: " . $e->getMessage() . "\n";
    }
    
    // 4. Concedere privilegi
    echo "4️⃣  Concessione privilegi all'utente...\n";
    $admin_pdo->exec("GRANT ALL PRIVILEGES ON " . DB_NAME . ".* TO '" . DB_USER . "'@'" . $admin_host . "' WITH GRANT OPTION");
    $admin_pdo->exec("FLUSH PRIVILEGES");
    echo "✅ Privilegi concessi\n\n";

    // 5. Connettersi come utente app e applicare schema
    echo "5️⃣  Connessione come utente app...\n";
    $app_pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connesso come " . DB_USER . "\n\n";

    // 6. Applicare schema MySQL
    echo "6️⃣  Applicazione schema MySQL...\n";
    $schema = file_get_contents(__DIR__ . '/schema_mysql.sql');
    
    // Eseguire statements SQL riga per riga
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    $count = 0;
    foreach ($statements as $statement) {
        if (!empty($statement) && strpos($statement, '--') !== 0) {
            $app_pdo->exec($statement);
            $count++;
        }
    }
    echo "✅ Schema applicato ($count statement)\n\n";

    // 7. Verificazione
    echo "7️⃣  Verifica tabelle create...\n";
    $result = $app_pdo->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "   📊 $table\n";
    }
    
    echo "\n✅ SETUP COMPLETATO CON SUCCESSO!\n";
    echo "==================================\n\n";
    echo "Configurazione attuale:\n";
    echo "- Host: " . DB_HOST . "\n";
    echo "- Database: " . DB_NAME . "\n";
    echo "- Utente: " . DB_USER . "\n";
    echo "- Tabelle: " . count($tables) . "\n\n";
    echo "Credenziali admin:\n";
    echo "- Username: admin\n";
    echo "- Password: Admin123!\n";

} catch (Exception $e) {
    echo "❌ ERRORE: " . $e->getMessage() . "\n";
    echo "Traceback: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
