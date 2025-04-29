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

// ✅ Leer y validar JSON
$input = file_get_contents('php://input');
$param = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    responder(400, 'JSON inválido.');
}

$co = $param['compania'] ?? null;
$op = $param['op'] ?? null;
$cb = $param['cod_combinacion'] ?? null;

if (empty($co)) responder(422, 'Se requiere "compania".');
if (empty($op) || strlen($op) != 10) responder(422, 'Se requiere "op" de 10 dígitos.');
if (empty($cb)) responder(422, 'Se requiere "cod_combinacion".');

// ✅ Consulta en base Sybase con conexión explícita
$select_sql_sybase = "
    SELECT cod_talla, tdscr
    FROM ordenserviciostallasmov 
    LEFT JOIN almcad ON cod_talla = ccrct 
    WHERE ccmpn = '{$co}' 
      AND nnope = '{$op}'
      AND flgestado = 'INGRESO A ACABADO' 
      AND cod_combinacion = '{$cb}' 
      AND codQR IS NULL 
      AND ctpar = '10' 
      AND norden = '6' 
    GROUP BY cod_talla, tdscr
";

sc_lookup(rs_data_sybase, $select_sql_sybase, "conn_sybase");

if (!isset({rs_data_sybase}) || {rs_data_sybase} === false) {
    responder(500, 'Fallo conexión base Sybase: ' . $this->Db->ErrorMsg());
}

if (count({rs_data_sybase}) === 0) {
    responder(404, 'No se encontraron datos para la OP ingresada.');
}

// ✅ Formar respuesta
$rta = [];
foreach ({rs_data_sybase} as $row) {
    $rta[] = [
        'cod_talla' => $row[0],
        'talla'     => mb_convert_encoding($row[1], 'UTF-8', 'CP850')
    ];
}

responder(200, 'Listado de tallas correcto.', $rta);
