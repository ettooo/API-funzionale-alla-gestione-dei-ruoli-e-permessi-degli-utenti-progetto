<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

$JWT_SECRET = 'CAMBIA_QUESTA_CHIAVE_LUNGA_RANDOM'; // mettila lunga
$ACCESS_TTL = 600; // 10 min
$REFRESH_TTL = 7 * 24 * 3600; // 7 giorni (demo)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'POST only'], 405);
}

// input JSON: { "login": "...", "password": "..." }
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
$login = trim($body['login'] ?? '');
$pass  = $body['password'] ?? '';

if ($login === '' || $pass === '') {
    json_response(['error' => 'login e password obbligatori'], 400);
}

$pdo = db();
$stmt = $pdo->prepare("SELECT id, password_hash, is_active FROM users WHERE email = ? OR username = ?");
$stmt->execute([$login, $login]);
$u = $stmt->fetch();

if (!$u || (int)$u['is_active'] !== 1 || !password_verify($pass, $u['password_hash'])) {
    json_response(['error' => 'Credenziali non valide'], 401);
}

$userId = (int)$u['id'];

// access token (10 min)
$accessToken = jwt_sign(
    ['sub' => $userId, 'type' => 'access', 'uid' => $userId],
    $JWT_SECRET,
    $ACCESS_TTL
);

// refresh token (demo)
$refreshToken = jwt_sign(
    ['sub' => $userId, 'type' => 'refresh', 'uid' => $userId],
    $JWT_SECRET,
    $REFRESH_TTL
);

json_response([
    'access_token' => $accessToken,
    'expires_in' => $ACCESS_TTL,
    'refresh_token' => $refreshToken
]);
