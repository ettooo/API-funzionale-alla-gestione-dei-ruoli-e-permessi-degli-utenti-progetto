<?php
// ============================================
// api/index.php — Router API REST
//
// Base URL:  /api/
// Endpoint:
//   POST   /api/auth/login         → ottieni access + refresh token
//   POST   /api/auth/refresh       → rinnova access token con refresh token
//   POST   /api/auth/logout        → revoca refresh token
//   GET    /api/me/permissions     → permessi dell'utente autenticato
//   GET    /api/permissions        → lista tutti i permessi [admin]
//   POST   /api/permissions        → crea permesso           [admin]
//   PUT    /api/permissions/{id}   → aggiorna permesso       [admin]
//   DELETE /api/permissions/{id}   → elimina permesso        [admin]
//   GET    /api/users/{id}/permissions   → permessi di un utente [admin]
//   POST   /api/users/{id}/permissions   → aggiungi permesso ad utente [admin]
//   DELETE /api/users/{id}/permissions/{pid} → rimuovi permesso da utente [admin]
// ============================================

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';

// ─── CORS & Headers ─────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── Helpers ────────────────────────────────
function respond(int $code, array $data): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function respondOk(array $data): never {
    respond(200, $data);
}

function respondCreated(array $data): never {
    respond(201, $data);
}

function respondError(int $code, string $message, array $extra = []): never {
    respond($code, array_merge(['error' => $message], $extra));
}

function getBody(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** Richiede JWT valido, ritorna payload */
function requireJwt(): array {
    try {
        return jwtFromRequest();
    } catch (Throwable $e) {
        respondError(401, 'Non autenticato: ' . $e->getMessage());
    }
}

/** Richiede permesso specifico nel JWT, dopo aver caricato i perms da DB */
function requireApiPermission(int $userId, string $permission): void {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT p.name
        FROM users u
        JOIN role_permissions rp ON u.role_id = rp.role_id
        JOIN permissions p       ON rp.permission_id = p.id
        WHERE u.id = ? AND u.is_active = 1
    ");
    $stmt->execute([$userId]);
    $perms = array_column($stmt->fetchAll(), 'name');

    if (!in_array($permission, $perms, true)) {
        respondError(403, "Accesso negato: permesso '$permission' richiesto.");
    }
}

// ─── Routing ────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// --- CORREZIONE ROUTER ---
// Rimuove la cartella del progetto, /api/ e index.php dall'URI per gestire ambienti XAMPP
$uri = str_replace(['/progetto', '/api', 'index.php'], '', $uri);
$uri = '/' . trim($uri, '/');
// -------------------------

// ─────────────────────────────────────────────
//  ROUTE: POST /auth/login
// ─────────────────────────────────────────────
if ($method === 'POST' && $uri === '/auth/login') {
    $body     = getBody();
    $email    = trim($body['email']    ?? '');
    $password = $body['password']      ?? '';

    if (!$email || !$password) {
        respondError(422, 'email e password sono obbligatori.');
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.password_hash, u.is_active, r.name AS role
        FROM users u JOIN roles r ON u.role_id = r.id
        WHERE u.email = ? LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        respondError(401, 'Credenziali non valide.');
    }
    if (!$user['is_active']) {
        respondError(403, 'Account disabilitato.');
    }

    // Carica permessi
    $pStmt = $pdo->prepare("
        SELECT p.name FROM users u
        JOIN role_permissions rp ON u.role_id = rp.role_id
        JOIN permissions p       ON rp.permission_id = p.id
        WHERE u.id = ?
    ");
    $pStmt->execute([$user['id']]);
    $permissions = array_column($pStmt->fetchAll(), 'name');

    // Genera JWT Access Token
    $accessToken = jwtCreate([
        'sub'  => $user['id'],
        'name' => $user['username'],
        'role' => $user['role'],
    ]);

    // Genera Refresh Token
    $ua           = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip           = $_SERVER['REMOTE_ADDR']     ?? '';
    $refreshToken = refreshTokenCreate($user['id'], $ua, $ip);

    respondOk([
        'message'       => 'Login effettuato con successo.',
        'token_type'    => 'Bearer',
        'expires_in'    => JWT_ACCESS_TTL,
        'access_token'  => $accessToken,
        'refresh_token' => $refreshToken,
        'user' => [
            'id'          => $user['id'],
            'username'    => $user['username'],
            'email'       => $user['email'],
            'role'        => $user['role'],
            'permissions' => $permissions,
        ],
    ]);
}

// ─────────────────────────────────────────────
//  ROUTE: POST /auth/refresh
// ─────────────────────────────────────────────
if ($method === 'POST' && $uri === '/auth/refresh') {
    $body  = getBody();
    $token = $body['refresh_token'] ?? '';

    if (!$token) {
        respondError(422, 'refresh_token obbligatorio.');
    }

    try {
        $result = refreshTokenRotate($token);
    } catch (Throwable $e) {
        respondError(401, $e->getMessage());
    }

    $u = $result['user'];

    // Carica permessi aggiornati
    $pdo   = getDB();
    $pStmt = $pdo->prepare("
        SELECT p.name FROM users u
        JOIN role_permissions rp ON u.role_id = rp.role_id
        JOIN permissions p       ON rp.permission_id = p.id
        WHERE u.id = ?
    ");
    $pStmt->execute([$u['id']]);
    $permissions = array_column($pStmt->fetchAll(), 'name');

    $accessToken = jwtCreate([
        'sub'  => $u['id'],
        'name' => $u['username'],
        'role' => $u['role'],
    ]);

    respondOk([
        'message'       => 'Token rinnovato.',
        'token_type'    => 'Bearer',
        'expires_in'    => JWT_ACCESS_TTL,
        'access_token'  => $accessToken,
        'refresh_token' => $result['new_refresh_token'],
        'user' => [
            'id'          => $u['id'],
            'username'    => $u['username'],
            'email'       => $u['email'],
            'role'        => $u['role'],
            'permissions' => $permissions,
        ],
    ]);
}

// ─────────────────────────────────────────────
//  ROUTE: POST /auth/logout
// ─────────────────────────────────────────────
if ($method === 'POST' && $uri === '/auth/logout') {
    $body  = getBody();
    $token = $body['refresh_token'] ?? '';

    if ($token) {
        refreshTokenRevoke($token);
    }

    respondOk(['message' => 'Logout effettuato.']);
}

// ─────────────────────────────────────────────
//  ROUTE: GET /me/permissions
//  Ritorna i permessi dell'utente autenticato via JWT
// ─────────────────────────────────────────────
if ($method === 'GET' && $uri === '/me/permissions') {
    $payload = requireJwt();
    $userId  = (int) ($payload['sub'] ?? 0);

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.description
        FROM users u
        JOIN role_permissions rp ON u.role_id = rp.role_id
        JOIN permissions p       ON rp.permission_id = p.id
        WHERE u.id = ? AND u.is_active = 1
        ORDER BY p.name
    ");
    $stmt->execute([$userId]);
    $permissions = $stmt->fetchAll();

    // Info utente
    $uStmt = $pdo->prepare("SELECT u.id, u.username, u.email, r.name AS role FROM users u JOIN roles r ON u.role_id=r.id WHERE u.id=?");
    $uStmt->execute([$userId]);
    $user = $uStmt->fetch();

    respondOk([
        'user' => [
            'id'       => $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'role'     => $user['role'],
        ],
        'permissions' => $permissions,
        'token_info' => [
            'issued_at'  => date('c', $payload['iat']),
            'expires_at' => date('c', $payload['exp']),
            'expires_in' => $payload['exp'] - time(),
        ],
    ]);
}

// ═════════════════════════════════════════════
//  ROUTES: /permissions  (CRUD — solo admin)
// ═════════════════════════════════════════════

// GET /permissions — lista tutti i permessi
if ($method === 'GET' && $uri === '/permissions') {
    $payload = requireJwt();
    requireApiPermission((int)$payload['sub'], 'manage_permissions');

    $pdo  = getDB();
    $rows = $pdo->query("SELECT id, name, description, created_at FROM permissions ORDER BY name")->fetchAll();

    respondOk(['permissions' => $rows, 'total' => count($rows)]);
}

// POST /permissions — crea nuovo permesso
if ($method === 'POST' && $uri === '/permissions') {
    $payload = requireJwt();
    requireApiPermission((int)$payload['sub'], 'manage_permissions');

    $body        = getBody();
    $name        = trim($body['name']        ?? '');
    $description = trim($body['description'] ?? '');

    if (!preg_match('/^[a-z_]{3,100}$/', $name)) {
        respondError(422, 'name deve contenere solo lettere minuscole e underscore (3-100 chars).');
    }

    try {
        $pdo = getDB();
        $pdo->prepare("INSERT INTO permissions (name, description) VALUES (?, ?)")
            ->execute([$name, $description]);
        $id = (int) $pdo->lastInsertId();

        respondCreated([
            'message'    => 'Permesso creato.',
            'permission' => ['id' => $id, 'name' => $name, 'description' => $description],
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            respondError(409, "Il permesso '$name' esiste già.");
        }
        respondError(500, 'Errore database.');
    }
}

// PUT /permissions/{id} — aggiorna permesso
if ($method === 'PUT' && preg_match('#^/permissions/(\d+)$#', $uri, $m)) {
    $payload = requireJwt();
    requireApiPermission((int)$payload['sub'], 'manage_permissions');

    $permId      = (int) $m[1];
    $body        = getBody();
    $name        = trim($body['name']        ?? '');
    $description = trim($body['description'] ?? '');

    if ($name && !preg_match('/^[a-z_]{3,100}$/', $name)) {
        respondError(422, 'name deve contenere solo lettere minuscole e underscore.');
    }

    $pdo  = getDB();
    $perm = $pdo->prepare("SELECT id, name, description FROM permissions WHERE id = ?")->execute([$permId]);
    $row  = $pdo->query("SELECT id, name, description FROM permissions WHERE id = $permId")->fetch();

    if (!$row) respondError(404, 'Permesso non trovato.');

    $newName = $name        ?: $row['name'];
    $newDesc = $description ?: $row['description'];

    try {
        $pdo->prepare("UPDATE permissions SET name=?, description=? WHERE id=?")
            ->execute([$newName, $newDesc, $permId]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') respondError(409, 'Nome già in uso.');
        respondError(500, 'Errore database.');
    }

    respondOk([
        'message'    => 'Permesso aggiornato.',
        'permission' => ['id' => $permId, 'name' => $newName, 'description' => $newDesc],
    ]);
}

// DELETE /permissions/{id} — elimina permesso
if ($method === 'DELETE' && preg_match('#^/permissions/(\d+)$#', $uri, $m)) {
    $payload = requireJwt();
    requireApiPermission((int)$payload['sub'], 'manage_permissions');

    $permId = (int) $m[1];
    $pdo    = getDB();
    $row    = $pdo->prepare("SELECT id, name FROM permissions WHERE id = ?");
    $row->execute([$permId]);
    $perm = $row->fetch();

    if (!$perm) respondError(404, 'Permesso non trovato.');

    $pdo->prepare("DELETE FROM permissions WHERE id = ?")->execute([$permId]);

    respondOk(['message' => "Permesso '{$perm['name']}' eliminato."]);
}

// ═════════════════════════════════════════════
//  ROUTES: /users/{id}/permissions  (CRUD — solo admin)
// ═════════════════════════════════════════════

// GET /users/{id}/permissions — permessi di un utente
if ($method === 'GET' && preg_match('#^/users/(\d+)/permissions$#', $uri, $m)) {
    $payload = requireJwt();
    $caller  = (int) $payload['sub'];
    $targetId = (int) $m[1];

    // L'utente può vedere i propri permessi; l'admin può vedere quelli di tutti
    if ($caller !== $targetId) {
        requireApiPermission($caller, 'manage_permissions');
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.description, 'role' AS source
        FROM users u
        JOIN role_permissions rp ON u.role_id = rp.role_id
        JOIN permissions p       ON rp.permission_id = p.id
        WHERE u.id = ?
        ORDER BY p.name
    ");
    $stmt->execute([$targetId]);
    $permissions = $stmt->fetchAll();

    $uStmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
    $uStmt->execute([$targetId]);
    $user = $uStmt->fetch();
    if (!$user) respondError(404, 'Utente non trovato.');

    respondOk(['user' => $user, 'permissions' => $permissions]);
}

// POST /users/{id}/permissions — aggiungi permesso diretto a un utente
// Nota: qui aggiungiamo il permesso al RUOLO dell'utente
// Per un sistema più granulare si userebbe una tabella user_permissions separata
if ($method === 'POST' && preg_match('#^/users/(\d+)/permissions$#', $uri, $m)) {
    $payload = requireJwt();
    requireApiPermission((int)$payload['sub'], 'manage_permissions');

    $targetId    = (int) $m[1];
    $body        = getBody();
    $permissionId = (int) ($body['permission_id'] ?? 0);

    if (!$permissionId) respondError(422, 'permission_id obbligatorio.');

    $pdo = getDB();

    // Verifica utente
    $uStmt = $pdo->prepare("SELECT id, role_id, username FROM users WHERE id = ?");
    $uStmt->execute([$targetId]);
    $user = $uStmt->fetch();
    if (!$user) respondError(404, 'Utente non trovato.');

    // Verifica permesso
    $pStmt = $pdo->prepare("SELECT id, name FROM permissions WHERE id = ?");
    $pStmt->execute([$permissionId]);
    $perm = $pStmt->fetch();
    if (!$perm) respondError(404, 'Permesso non trovato.');

    try {
        $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)")
            ->execute([$user['role_id'], $permissionId]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            respondError(409, "L'utente ha già il permesso '{$perm['name']}'.");
        }
        respondError(500, 'Errore database.');
    }

    respondCreated([
        'message' => "Permesso '{$perm['name']}' assegnato all'utente '{$user['username']}'.",
    ]);
}

// DELETE /users/{id}/permissions/{pid} — rimuovi permesso da utente
if ($method === 'DELETE' && preg_match('#^/users/(\d+)/permissions/(\d+)$#', $uri, $m)) {
    $payload = requireJwt();
    requireApiPermission((int)$payload['sub'], 'manage_permissions');

    $targetId    = (int) $m[1];
    $permissionId = (int) $m[2];

    $pdo = getDB();

    $uStmt = $pdo->prepare("SELECT id, role_id, username FROM users WHERE id = ?");
    $uStmt->execute([$targetId]);
    $user = $uStmt->fetch();
    if (!$user) respondError(404, 'Utente non trovato.');

    $pStmt = $pdo->prepare("SELECT id, name FROM permissions WHERE id = ?");
    $pStmt->execute([$permissionId]);
    $perm = $pStmt->fetch();
    if (!$perm) respondError(404, 'Permesso non trovato.');

    $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?")
        ->execute([$user['role_id'], $permissionId]);

    respondOk([
        'message' => "Permesso '{$perm['name']}' rimosso dall'utente '{$user['username']}'.",
    ]);
}

// ─── 404 fallback ────────────────────────────
respondError(404, "Endpoint non trovato: $method $uri");
