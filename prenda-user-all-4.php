<?php
require_once('../_lib/jwt/jwt-loader.php');
header('Content-Type: application/json');

// ✅ Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, 'Método no permitido. Solo se acepta POST.');
}

// ✅ Verificar token
$token = obtener_token();
if (empty($token)) responder(401, 'Token no proporcionado.');

try {
    $decoded = verificar_token($token);
} catch (Exception $e) {
    responder(401, 'Token inválido: ' . $e->getMessage());
}

// ✅ Leer entrada
$input = file_get_contents('php://input');
$param = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    responder(400, 'JSON inválido.');
}

// ✅ Validar parámetros
$co              = $param['compania']        ?? null;
$cod_trabajador  = $param['cod_trabajador']  ?? null;
$op              = $param['op']              ?? null;
$hm              = $param['hoja_marcacion']  ?? null;
$id              = $param['rfids']           ?? null;
$col_tal_cant    = $param['col_tal_cant']    ?? null;
$token_ref       = date('YmdHis');

if (empty($co)) responder(422, 'Se requiere "compania".');
if (empty($cod_trabajador)) responder(422, 'Se requiere "cod_trabajador".');
if (empty($op)) responder(422, 'Se requiere "op".');
if (empty($hm)) responder(422, 'Se requiere "hoja_marcacion".');
if (empty($id)) responder(422, 'Se requiere "rfids".');
if (empty($col_tal_cant)) responder(422, 'Se requiere "col_tal_cant".');

try {
    // ✅ Conexiones
    $connSybase = conectar_sybase();
    $connMysql  = conectar_mysql();

    // ✅ Ejecutar procedimiento en Sybase
    $exec_sp = "EXEC USP_SAL_EMB_CON_RFID_DATA_ ?, ?, ?, ?, ?, ?, ?";
    $stmt_sp = $connSybase->prepare($exec_sp);
    $stmt_sp->execute([$co, $id, $op, $hm, $cod_trabajador, $col_tal_cant, $token_ref]);

    // ✅ Consultar datos generados
    $select_sql_sybase = "
        SELECT id_rfid, id_barras, cod_trabajador, op, hoja_marcacion, corte,
               subcorte, cod_talla, id_talla, talla, cod_combinacion, color
        FROM ordenserviciostallasvincular
        WHERE cod_trabajador = ? AND p_token = ?
    ";

    $stmt_sybase = $connSybase->prepare($select_sql_sybase);
    $stmt_sybase->execute([$cod_trabajador, $token_ref]);
    $result = $stmt_sybase->fetchAll(PDO::FETCH_ASSOC);

    if (!$result) {
        responder(404, 'No se encontraron datos a transferir.');
    }

    // ✅ Insertar en MySQL
    $insert_sql_mysql = "
        INSERT INTO prenda
        (id_rfid, id_barras, cod_trabajador, op, hoja_marcacion, corte, subcorte,
         cod_talla, id_talla, talla, cod_combinacion, color, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 5)
    ";

    $stmt_mysql = $connMysql->prepare($insert_sql_mysql);
    $insertados = 0;

    foreach ($result as $row) {
        $stmt_mysql->execute([
            $row['id_rfid'], $row['id_barras'], $row['cod_trabajador'],
            $row['op'], $row['hoja_marcacion'], $row['corte'], $row['subcorte'],
            $row['cod_talla'], $row['id_talla'], $row['talla'],
            $row['cod_combinacion'], $row['color']
        ]);
        $insertados++;
    }

    responder(200, 'Datos transferidos exitosamente.', [
        'insertados' => $insertados
    ]);

} catch (PDOException $e) {
    responder(500, 'Error en procesamiento: ' . $e->getMessage());
} finally {
    $connSybase = null;
    $connMysql  = null;
}
