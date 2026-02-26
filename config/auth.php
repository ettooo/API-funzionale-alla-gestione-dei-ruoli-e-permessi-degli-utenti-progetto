<?php
// ============================================
// config/auth.php — Funzioni di autenticazione
// ============================================

require_once __DIR__ . '/db.php';

// Avvia sessione sicura
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,  // true in produzione con HTTPS
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

// Recupera i permessi di un utente dal DB
function getUserPermissions(int $userId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT p.name
        FROM users u
        JOIN role_permissions rp ON u.role_id = rp.role_id
        JOIN permissions p       ON rp.permission_id = p.id
        WHERE u.id = ? AND u.is_active = 1
    ");
    $stmt->execute([$userId]);
    return array_column($stmt->fetchAll(), 'name');
}

// Controlla se l'utente corrente ha un permesso
function can(string $permission): bool {
    startSession();
    if (empty($_SESSION['user_permissions'])) return false;
    return in_array($permission, $_SESSION['user_permissions'], true);
}

// Verifica che l'utente sia loggato, altrimenti redirect
function requireLogin(): void {
    startSession();
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Verifica permesso o redirect con errore
function requirePermission(string $permission): void {
    requireLogin();
    if (!can($permission)) {
        $_SESSION['flash_error'] = 'Non hai i permessi per accedere a questa sezione.';
        header('Location: dashboard.php');
        exit;
    }
}

// Login utente
function loginUser(string $email, string $password): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.password_hash, u.is_active,
               r.name AS role_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.email = ?
        LIMIT 1
    ");
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'Email o password non corretti.'];
    }
    if (!$user['is_active']) {
        return ['success' => false, 'message' => 'Account disabilitato. Contatta il supporto.'];
    }
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Email o password non corretti.'];
    }

    // Imposta sessione
    session_regenerate_id(true);
    $_SESSION['user_id']          = $user['id'];
    $_SESSION['username']         = $user['username'];
    $_SESSION['email']            = $user['email'];
    $_SESSION['role']             = $user['role_name'];
    $_SESSION['user_permissions'] = getUserPermissions($user['id']);

    return ['success' => true];
}

// Registrazione nuovo utente (ruolo free di default)
function registerUser(string $username, string $email, string $password): array {
    $pdo = getDB();

    // Validazione
    $username = trim($username);
    $email    = trim(strtolower($email));

    if (strlen($username) < 3 || strlen($username) > 50) {
        return ['success' => false, 'message' => 'Username deve essere tra 3 e 50 caratteri.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email non valida.'];
    }
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'La password deve avere almeno 8 caratteri.'];
    }
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return ['success' => false, 'message' => 'La password deve contenere almeno una maiuscola e un numero.'];
    }

    // Verifica unicità
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
    $check->execute([$email, $username]);
    if ($check->fetch()) {
        return ['success' => false, 'message' => 'Username o email già in uso.'];
    }

    // Recupera id ruolo free
    $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'free' LIMIT 1");
    $roleStmt->execute();
    $role = $roleStmt->fetch();

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $ins  = $pdo->prepare("INSERT INTO users (username, email, password_hash, role_id) VALUES (?, ?, ?, ?)");
    $ins->execute([$username, $email, $hash, $role['id']]);

    return ['success' => true];
}

// Logout
function logoutUser(): void {
    startSession();
    $_SESSION = [];
    session_destroy();
}
