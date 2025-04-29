<?php
header('Content-Type: application/json');

// Función para responder con JSON y detener la ejecución
function responder(int $code, string $msn, array $data = []): never {
    http_response_code($code);
    echo json_encode([
        'code' => $code,
        'msn'  => $msn,
        'data' => $data
    ]);
    exit;
}

// Verificar método
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

$key = hash('sha256', $secret_key, true);             // 32 bytes
$iv  = substr(hash('sha256', $secret_iv, true), 0, 16); // 16 bytes

// Verificación de clave
$es_valido = false;
$resultado = [];

foreach ({rs_data_sybase} as $row) {
    $clave_encriptada = $row[2];
    $clave_input_base64 = base64_encode(
        openssl_encrypt($password, $metodo, $key, OPENSSL_RAW_DATA, $iv)
    );

    if ($clave_input_base64 === $clave_encriptada) {
        $es_valido = true;
        $resultado[] = [
            'codigo' => $row[0],
            'datos'  => mb_convert_encoding($row[1], 'UTF-8', 'CP850')
        ];
    }
}

if (!$es_valido) {
    responder(401, 'Las claves no coinciden.');
}

responder(200, 'Datos encontrados.', $resultado);
