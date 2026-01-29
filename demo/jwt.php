<?php
// demo/jwt.php

function b64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function b64url_decode(string $data): string {
    $remainder = strlen($data) % 4;
    if ($remainder) $data .= str_repeat('=', 4 - $remainder);
    return base64_decode(strtr($data, '-_', '+/')) ?: '';
}

function jwt_sign(array $payload, string $secret, int $ttlSeconds): string {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $now = time();
    $payload['iat'] = $now;
    $payload['exp'] = $now + $ttlSeconds;

    $h = b64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES));
    $p = b64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
    $sig = hash_hmac('sha256', "$h.$p", $secret, true);
    return "$h.$p." . b64url_encode($sig);
}

function jwt_verify(string $jwt, string $secret): array {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) throw new Exception("Token malformato");

    [$h, $p, $s] = $parts;
    $calc = b64url_encode(hash_hmac('sha256', "$h.$p", $secret, true));
    if (!hash_equals($calc, $s)) throw new Exception("Firma non valida");

    $payload = json_decode(b64url_decode($p), true);
    if (!is_array($payload)) throw new Exception("Payload non valido");

    if (!isset($payload['exp']) || time() > (int)$payload['exp']) {
        throw new Exception("Token scaduto");
    }
    return $payload;
}

function get_bearer_token(): ?string {
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $hdr, $m)) return trim($m[1]);
    return null;
}

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
