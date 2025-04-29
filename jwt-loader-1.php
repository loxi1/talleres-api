<?php
// Cargador manual para Scriptcase sin Composer

require_once(__DIR__ . '/JWTExceptionWithPayloadInterface.php');
require_once(__DIR__ . '/SignatureInvalidException.php');
require_once(__DIR__ . '/ExpiredException.php');
require_once(__DIR__ . '/BeforeValidException.php');
require_once(__DIR__ . '/Key.php');
require_once(__DIR__ . '/JWT.php');

// 🔐 Configuración
define('ENCRYPT_METHOD', '');
define('SECRET_KEY',     '');
define('SECRET_IV',      '');
define('JWT',            '');

// 🔒 Funciones de encriptación
function decrypt($string) {
    $key = hash('sha256', SECRET_KEY, true);
    $iv  = substr(hash('sha256', SECRET_IV, true), 0, 16);
    return rtrim(openssl_decrypt(base64_decode($string), ENCRYPT_METHOD, $key, OPENSSL_RAW_DATA, $iv), "\0");
}

function encrypt($string) {
    $key = hash('sha256', SECRET_KEY, true);
    $iv  = substr(hash('sha256', SECRET_IV, true), 0, 16);
    return base64_encode(openssl_encrypt($string, ENCRYPT_METHOD, $key, OPENSSL_RAW_DATA, $iv));
}

// 🪪 Generar JWT
function getToken($payload) {
    return \Firebase\JWT\JWT::encode($payload, JWT, 'HS256');
}

// ✅ Verificar JWT
function verificar_token(string $jwt): object {
    \Firebase\JWT\JWT::$leeway = 60;
    return \Firebase\JWT\JWT::decode($jwt, new \Firebase\JWT\Key(JWT, 'HS256'));
}