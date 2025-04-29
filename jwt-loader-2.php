<?php
require_once(__DIR__ . '/JWTExceptionWithPayloadInterface.php');
require_once(__DIR__ . '/SignatureInvalidException.php');
require_once(__DIR__ . '/ExpiredException.php');
require_once(__DIR__ . '/BeforeValidException.php');
require_once(__DIR__ . '/Key.php');
require_once(__DIR__ . '/JWT.php');

// Alias locales
class_alias('Firebase\JWT\JWT', 'JWT');
class_alias('Firebase\JWT\Key', 'JWTKey');

// Configuración
define('ENCRYPT_METHOD', 'AES-256-CBC');
define('SECRET_KEY',     '$BP@2017');
define('SECRET_IV',      '101712');
define('JWT_SECRET_KEY', '$C!4@2017');
define('JWT_ISSUER',     'http://192.168.150.43:8092');
define('JWT_AUDIENCE',   'miappmovil');
define('JWT_LEEWAY',    60); // segundos de tolerancia para expiración
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 7200); // 2 horas de duración

// Encriptar
function encrypt($string): string {
    $key = hash('sha256', SECRET_KEY, true);
    $iv  = substr(hash('sha256', SECRET_IV, true), 0, 16);
    return base64_encode(openssl_encrypt($string, ENCRYPT_METHOD, $key, OPENSSL_RAW_DATA, $iv));
}

// Desencriptar
function decrypt($string): string {
    $key = hash('sha256', SECRET_KEY, true);
    $iv  = substr(hash('sha256', SECRET_IV, true), 0, 16);
    return rtrim(openssl_decrypt(base64_decode($string), ENCRYPT_METHOD, $key, OPENSSL_RAW_DATA, $iv), "\0");
}

// Obtener token JWT desde headers, GET o POST
function obtener_token(): string {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if (empty($authHeader) && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (str_starts_with($authHeader, 'Bearer ')) {
        return trim(str_replace('Bearer', '', $authHeader));
    }

    return $_GET['token'] ?? $_POST['token'] ?? '';
}

// Verificar token JWT
function verificar_token(string $token): object {
    JWT::$leeway = JWT_LEEWAY;
    $decoded = JWT::decode($token, new JWTKey(JWT_SECRET_KEY, JWT_ALGORITHM));

    if ($decoded->iss !== JWT_ISSUER) {
        throw new Exception('Emisor inválido.');
    }

    if ($decoded->aud !== JWT_AUDIENCE) {
        throw new Exception('Audiencia inválida.');
    }

    return $decoded;
}

// Generar nuevo token JWT
function firmar_token(array $payload): string {
    $payload['iss'] = 'http://192.168.150.43:8092';
    $payload['aud'] = 'miappmovil';
    $payload['iat'] = time();
    $payload['nbf'] = time();           // <- aquí corregido
    $payload['exp'] = time() + JWT_EXPIRATION;    // 2 horas de duración
    return JWT::encode($payload, JWT_SECRET_KEY, JWT_ALGORITHM);
}

// Función global para respuesta JSON
function responder(int $code, string $msn, array $data = []): never {
    http_response_code($code);
    echo json_encode([
        'code' => $code,
        'msn'  => $msn,
        'data' => $data
    ]);
    exit;
}

/**
 * Verifica si ocurrió un error grave en Sybase y responde si es necesario.
 * Ignora advertencias benignas como "Changed database context".
 */
function verificar_error_sybase($dbError, string $mensajePersonalizado = 'Fallo conexión base Sybase:') {
    if (
        stripos($dbError, 'DBPROCESS is dead') !== false ||
        stripos($dbError, 'not enabled') !== false ||
        stripos($dbError, '[20047]') !== false ||
        stripos($dbError, '[20018]') !== false
    ) {
        responder(500, "$mensajePersonalizado $dbError");
    }

    // Ignora errores benignos como cambio de base
    if (stripos($dbError, 'Changed database context') === false && !empty($dbError)) {
        responder(500, "$mensajePersonalizado $dbError");
    }
}