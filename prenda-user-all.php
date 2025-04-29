<?php
require_once('../_lib/jwt/jwt-loader.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    responder(405, 'Método no permitido. Solo se acepta GET.');
}

$token = obtener_token();
if (empty($token)) {
    responder(401, 'Token no proporcionado.');
}

try {
    $decoded = verificar_token($token);
} catch (Exception $e) {
    responder(401, 'Token inválido: ' . $e->getMessage());
}

$input = file_get_contents('php://input');
$param = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    responder(400, 'JSON inválido.');
}

$co = $param['compania'] ?? null;
$cod_trabajador = $param['cod_trabajador'] ?? null;
$op = $param['op'] ?? null;
$hm = $param['hoja_marcacion'] ?? null;
$id = $param['rfids'] ?? null;
$col_tal_cant = $param['col_tal_cant'] ?? null;
$token_ref = date('YmdHis');

if (empty($co)) responder(422, 'Se requiere "compania".');
if (empty($cod_trabajador)) responder(422, 'Se requiere "cod_trabajador".');
if (empty($op)) responder(422, 'Se requiere "op".');
if (empty($hm)) responder(422, 'Se requiere "hoja_marcacion".');
if (empty($id)) responder(422, 'Se requiere "rfids".');
if (empty($col_tal_cant)) responder(422, 'Se requiere "col_tal_cant".');

$exec_sp = "EXEC USP_SAL_EMB_CON_RFID_DATA_ '{$co}', '{$id}', '{$op}', '{$hm}', '{$cod_trabajador}', '{$col_tal_cant}', '{$token_ref}'";
sc_exec_sql($exec_sp, "conn_sybase");

$select_sybase = "SELECT id_rfid, id_barras, cod_trabajador, op, hoja_marcacion, corte, subcorte, cod_talla, id_talla, talla, cod_combinacion, color
                  FROM ordenserviciostallasvincular
                  WHERE cod_trabajador = '{$cod_trabajador}' AND p_token = '{$token_ref}'";
sc_lookup(rs_data_sybase, $select_sybase, "conn_sybase");

if (!isset({rs_data_sybase}) || !is_array({rs_data_sybase})) {
    responder(500, 'Error al consultar datos de Sybase.');
}

if (count({rs_data_sybase}) === 0) {
    responder(404, 'No se encontraron datos a transferir.');
}

foreach ({rs_data_sybase} as $row) {
    $sql_insert_mysql = "INSERT INTO prenda
        (id_rfid, id_barras, cod_trabajador, op, hoja_marcacion, corte, subcorte, cod_talla, id_talla, talla, cod_combinacion, color, estado)
        VALUES (
            '{$row[0]}', '{$row[1]}', '{$row[2]}', '{$row[3]}', '{$row[4]}', '{$row[5]}', '{$row[6]}',
            '{$row[7]}', '{$row[8]}', '{$row[9]}', '{$row[10]}', '{$row[11]}', 5
        )";
    sc_exec_sql($sql_insert_mysql, "conn_mysql_35");
}

responder(200, 'Datos transferidos exitosamente.', ['insertados' => count({rs_data_sybase})]);
