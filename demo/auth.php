<?php
// auth.php
require_once __DIR__ . '/db.php';

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function login_user(int $userId): void {
    $pdo = db();

    // Utente
    $stmt = $pdo->prepare("SELECT id, email, username, is_active FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $u = $stmt->fetch();
    if (!$u || (int)$u['is_active'] !== 1) {
        throw new RuntimeException("Utente non attivo.");
    }

    // Ruoli
    $stmt = $pdo->prepare("
        SELECT r.name
        FROM roles r
        JOIN user_roles ur ON ur.role_id = r.id
        WHERE ur.user_id = ?
    ");
    $stmt->execute([$userId]);
    $roles = array_map(fn($row) => $row['name'], $stmt->fetchAll());

    // Permessi (derivati dai ruoli)
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.name
        FROM permissions p
        JOIN role_permissions rp ON rp.permission_id = p.id
        JOIN user_roles ur ON ur.role_id = rp.role_id
        WHERE ur.user_id = ?
    ");
    $stmt->execute([$userId]);
    $perms = array_map(fn($row) => $row['name'], $stmt->fetchAll());

    $_SESSION['user'] = [
        'id' => (int)$u['id'],
        'email' => $u['email'],
        'username' => $u['username'],
        'roles' => $roles,
        'permissions' => $perms,
    ];
}

function logout_user(): void {
    $_SESSION = [];
    session_destroy();
}

function require_login(): void {
    if (!current_user()) {
        header("Location: login.php");
        exit;
    }
}

function has_role(string $role): bool {
    $u = current_user();
    return $u && in_array($role, $u['roles'], true);
}

function can(string $permission): bool {
    $u = current_user();
    return $u && in_array($permission, $u['permissions'], true);
}

function require_permission(string $permission): void {
    require_login();
    if (!can($permission)) {
        http_response_code(403);
        echo "<h1>403 - Accesso negato</h1>";
        echo "<p>Permesso richiesto: <b>" . htmlspecialchars($permission) . "</b></p>";
        exit;
    }
}

function redirect_dashboard(): void {
    // priorità: ADMIN > PREMIUM > FREE
    if (has_role('ADMIN')) {
        header("Location: dashboard_admin.php"); exit;
    }
    if (has_role('PREMIUM')) {
        header("Location: dashboard_premium.php"); exit;
    }
    header("Location: dashboard_free.php"); exit;
}
