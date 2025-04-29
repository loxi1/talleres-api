<?php
require_once('../_lib/jwt/jwt-loader.php');
header('Content-Type: application/json');

// ✅ Validar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responder(405, 'Método no permitido. Solo se acepta GET.');
}

// ✅ Obtener token desde header o parámetros
$token = obtener_token();

if (empty($token)) {
    responder(401, 'Token no proporcionado.');
}

// ✅ Verificar y decodificar el token
try {
    $decoded = verificar_token($token);
} catch (Exception $e) {
    responder(401, 'Token inválido: ' . $e->getMessage());
}

// ✅ Leer cuerpo JSON y validar parámetros
$input = file_get_contents('php://input');
$param = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    responder(400, 'JSON inválido.');
}

$op = $param['op'] ?? null;
if (empty($op) || strlen($op) != 5) {
    responder(422, 'Se requiere el parámetro "op".');
}

// ✅ Consulta a base de datos Sybase
$select_sql_sybase = "
    select norpd from althmc 
    WHERE norpd like '%{$op}'
    group by norpd
";
sc_lookup(rs_data_sybase, $select_sql_sybase);

if (!isset({rs_data_sybase}) || !is_array({rs_data_sybase})) {
    responder(500, 'Error al ejecutar la consulta.');
}

if (count({rs_data_sybase}) === 0) {
    responder(404, 'No se encontraron datos para la OP ingresada.');
}

// ✅ Armar respuesta
$rta = [];
foreach ({rs_data_sybase} as $row) {
    $rta = [
        'norpd' => $row[0]
    ];
}

// ✅ Enviar respuesta JSON
responder(200, 'OP correcta.', $rta);