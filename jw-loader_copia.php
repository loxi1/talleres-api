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
define('ENCRYPT_METHOD', '');
define('SECRET_KEY',     '');
define('SECRET_IV',      '');
define('JWT_SECRET_KEY', '');
define('JWT_ISSUER',     'http://192.168.150.43:8092');
define('JWT_AUDIENCE',   'miappmovil');
define('JWT_LEEWAY',    60);
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 7200);

// Accesos a base de datos
// Conexión Sybase
define('SERVER_NAME_SY', '');
define('DB_USER_SY', '');
define('DB_PASSWORD_SY', '');
define('PORT_SY', '6100');
define('DB_NAME_SY', 'nexus');

// Conexión MySQL
define('DB_SERVER_MY', '');
define('DB_PORT_MY', '');
define('DB_NAME_MY', '');
define('DB_USER_MY', '');
define('DB_PASSWORD_MY', '');

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

// Obtener token JWT
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
    $payload['iss'] = JWT_ISSUER;
    $payload['aud'] = JWT_AUDIENCE;
    $payload['iat'] = time();
    $payload['nbf'] = time();
    $payload['exp'] = time() + JWT_EXPIRATION;
    return JWT::encode($payload, JWT_SECRET_KEY, JWT_ALGORITHM);
}

// Función global para responder en JSON
function responder(int $code, string $msn, array $data = []): never {
    http_response_code($code);
    echo json_encode([
        'code' => $code,
        'msn'  => $msn,
        'data' => $data
    ]);
    exit;
}

// ✅ Conectar a base Sybase y devolver conexión activa
function conectar_sybase(): ?PDO {
    try {
        $dsn = "dblib:host=" . SERVER_NAME_SY . ":" . PORT_SY . ";dbname=" . DB_NAME_SY;
        $conn = new PDO($dsn, DB_USER_SY, DB_PASSWORD_SY);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        responder(500, 'Error de conexión Sybase: ' . $e->getMessage());
    }
}

// ✅ Conectar a base MySQL y devolver conexión activa
function conectar_mysql(): ?PDO {
    try {
        $dsn = "mysql:host=" . DB_SERVER_MY . ";port=" . DB_PORT_MY . ";dbname=" . DB_NAME_MY;
        $conn = new PDO($dsn, DB_USER_MY, DB_PASSWORD_MY);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("SET NAMES utf8");
        return $conn;
    } catch (PDOException $e) {
        responder(500, 'Error de conexión MySQL: ' . $e->getMessage());
    }
}
