<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

$JWT_SECRET = 'CAMBIA_QUESTA_CHIAVE_LUNGA_RANDOM';

$token = get_bearer_token();
if (!$token) json_response(['error' => 'Missing Bearer token'], 401);

try {
    $payload = jwt_verify($token, $JWT_SECRET);
    if (($payload['type'] ?? '') !== 'access') throw new Exception("Tipo token non valido");
    $userId = (int)($payload['uid'] ?? 0);
    if ($userId <= 0) throw new Exception("uid mancante");
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 401);
}

$pdo = db();

// permessi dell'utente (in base ai tuoi ruoli)
$stmt = $pdo->prepare("
    SELECT DISTINCT p.name
    FROM permissions p
    JOIN role_permissions rp ON rp.permission_id = p.id
    JOIN user_roles ur ON ur.role_id = rp.role_id
    WHERE ur.user_id = ?
    ORDER BY p.name
");
$stmt->execute([$userId]);
$perms = $stmt->fetchAll(PDO::FETCH_COLUMN);

json_response([
    'user_id' => $userId,
    'permissions' => $perms
]);
