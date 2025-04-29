<?php
require_once('../_lib/jwt/jwt-loader.php');
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once 'vendor/autoload.php'; // ajusta la ruta si estás fuera de composer

header('Content-Type: application/json');

// Función para responder con JSON
function responder(int $code, string $msn, array $data = []): never {
    http_response_code($code);
    echo json_encode([
        'code' => $code,
        'msn'  => $msn,
        'data' => $data
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, 'Método no permitido. Solo se acepta POST.');
}

// Leer y decodificar JSON
$input = file_get_contents('php://input');
$param = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    responder(400, 'JSON inválido.');
}

// Validar parámetros
$codigo   = $param['codigo'] ?? null;
$password = $param['password'] ?? null;

if (empty($codigo) || empty($password)) {
    responder(422, 'Se requiere código y contraseña.');
}

// Consulta en Sybase usando Scriptcase
$select_sql_sybase = "
    SELECT codigo, datos, clave
    FROM usuario_timbrado
    WHERE codigo = '{$codigo}' AND estado = 'ACTIVO'
";
sc_lookup(rs_data_sybase, $select_sql_sybase);

if (!isset({rs_data_sybase}) || !is_array({rs_data_sybase})) {
    responder(500, 'Error al ejecutar la consulta.');
}

if (count({rs_data_sybase}) === 0) {
    responder(404, 'No se encontraron datos.');
}

// Parámetros de encriptación
$metodo     = 'AES-256-CBC';
$secret_key = '$BP@2017';
$secret_iv  = '101712';

$key = hash('sha256', $secret_key, true);
$iv  = substr(hash('sha256', $secret_iv, true), 0, 16);

// Comparar clave cifrada y generar JWT si es válida
foreach ({rs_data_sybase} as $row) {
    $clave_encriptada = $row[2];
    $clave_input_base64 = base64_encode(
        openssl_encrypt($password, $metodo, $key, OPENSSL_RAW_DATA, $iv)
    );

    if ($clave_input_base64 === $clave_encriptada) {
        // ✅ Credenciales válidas → firmar JWT
        $jwt_key = '$C!4@2017'; // usa una secreta segura y privada
        $payload = [            
            'codigo' => $row[0],
            'datos'  => $row[1]
        ];

        $jwt = JWT::encode($payload, $jwt_key, 'HS256');

        responder(200, 'Autenticación exitosa.', [
            'token' => $jwt
        ]);
    }
}

// ❌ Si no pasó ninguna validación
responder(401, 'Las claves no coinciden.');
