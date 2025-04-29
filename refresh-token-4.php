<?php
require_once('../_lib/jwt/jwt-loader.php');
header('Content-Type: application/json');

// ✅ Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, 'Método no permitido. Solo se acepta POST.');
}

// ✅ Leer cuerpo JSON
$input = file_get_contents('php://input');
$param = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    responder(400, 'Cuerpo JSON inválido.');
}

// ✅ Extraer y validar token
$token = $param['token'] ?? null;
if (empty($token)) {
    responder(401, 'Token no proporcionado.');
}

try {
    // ✅ Verificar token original
    $decoded = verificar_token($token);

    // ✅ Construir nuevo payload, excluyendo campos temporales
    $payload = array_filter((array) $decoded, function ($key) {
        return !in_array($key, ['iat', 'nbf', 'exp', 'iss', 'aud']);
    }, ARRAY_FILTER_USE_KEY);

    // ✅ Firmar nuevo token
    $nuevo_token = firmar_token($payload);

    responder(200, 'Token renovado correctamente.', [
        'token' => $nuevo_token
    ]);

} catch (Exception $e) {
    responder(401, 'Token inválido o expirado: ' . $e->getMessage());
}