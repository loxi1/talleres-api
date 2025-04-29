<?php
require_once('../_lib/jwt/jwt-loader.php');
header('Content-Type: application/json');

// Validación de método
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responder(405, 'Método no permitido. Solo se acepta GET.');
}

// Obtener token desde headers o query
$token = obtener_token();
if (empty($token)) {
    responder(401, 'Token no proporcionado.');
}

try {
    $decoded = verificar_token($token);
} catch (Exception $e) {
    responder(401, 'Token inválido: ' . $e->getMessage());
}

// Consulta a base de datos
$select_sql_sybase = "SELECT codigo, datos, clave FROM usuario_timbrado WHERE estado = 'ACTIVO'";
sc_lookup(rs_data_sybase, $select_sql_sybase);

if (!isset({rs_data_sybase}) || !is_array({rs_data_sybase})) {
    responder(500, 'Error al ejecutar la consulta.');
}

if (count({rs_data_sybase}) === 0) {
    responder(404, 'No se encontraron datos.');
}

// Formatear respuesta
$rta = [];
foreach ({rs_data_sybase} as $row) {
    $rta[] = [
        'codigo' => $row[0],
        'datos'  => mb_convert_encoding($row[1], 'UTF-8', 'CP850'),
        'clave'  => encrypt($row[2])
    ];
}

responder(200, 'Datos obtenidos correctamente.', $rta);
