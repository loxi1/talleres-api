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

$comp = $param['compania'] ?? null;
$op = $param['op'] ?? null;
$comb = $param['cod_combinacion'] ?? null;

if (empty($comp)) responder(422, 'Se requiere "compania".');
if (empty($op) || strlen($op) != 10) responder(422, 'Se requiere "op" de 10 dígitos.');
if (empty($comb)) responder(422, 'Se requiere "cod_combinacion".');

try {
    $conn = conectar_sybase();

    // ✅ Importante: concatenar el comodín en PHP, no en el SQL directamente
    $sql = "SELECT cod_talla, tdscr FROM ordenserviciostallasmov LEFT JOIN almcad ON cod_talla = ccrct WHERE ccmpn = :comp AND nnope = :op AND flgestado = 'INGRESO A ACABADO' AND cod_combinacion = :comb AND codQR IS NULL AND ctpar = '10' AND norden = '6' GROUP BY cod_talla, tdscr";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':comp', $comp);
    $stmt->bindParam(':op', $op);
    $stmt->bindParam(':comb', $comb);
    $stmt->execute();
    print_r($sql);
    $tallas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$tallas) {
        responder(404, 'No se encontró ninguna OP que coincida.');
    }

    $rta = array_map(function ($row) {
        return [
            'codigo' => $row['cod_talla'],
            'talla'  => mb_convert_encoding($row['tdscr'], 'UTF-8', 'CP850')
        ];
    }, $tallas);

    responder(200, 'OP encontrada correctamente.', $rta);

} catch (PDOException $e) {
    responder(500, 'Error al consultar la OP: ' . $e->getMessage());
} finally {
    $conn = null;
}