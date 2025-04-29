<?php
require_once __DIR__ . '/jwt-loader.php'; // ✅ Carga las clases JWT manualmente

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$jwt_key = '[asdfasd$ED8/*¡¡]'; // usa una secreta segura y privada
$payload = [
    'iss' => 'http://192.168.150.43:8092',
    'aud' => 'miappmovil',
    'iat' => time(),
    'nbf' => time(),           // <- aquí corregido
    'exp' => time() + 7200,    // 2 horas de duración
    'codigo' => '32227',
    'datos'  => 'Carlos'
];

$jwt = JWT::encode($payload, $jwt_key, 'HS256');
echo "JWT: $jwt\n";