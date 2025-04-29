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
$hm = $param['hm'] ?? null;
// Si $hm no es null, verificar si tiene menos de 3 dígitos y completar con ceros
if ($hm !== null) {
    $hm = str_pad(substr($hm, 0, 3), 3, '0', STR_PAD_LEFT);
}

if (empty($op) || strlen($op) != 10) {
    responder(422, 'Se requiere el parámetro "op".');
}

if( empty($hm) || strlen($hm) != 3) {
    responder(422, 'Se requiere el parámetro "hm".');
}

try {
    $conn = conectar_sybase();

    // ✅ Importante: concatenar el comodín en PHP, no en el SQL directamente
    $sql = "SELECT nhjmr FROM althmc WHERE norpd=:op AND nhjmr=:hm";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':op', $op);
    $stmt->bindParam(':hm', $hm);
    $stmt->execute();

    $rtaop = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rtaop) {
        responder(404, 'No se encontró ninguna OP que coincida.');
    }

    $rta = [
        'hm' => $rtaop[0]['nhjmr'] ?? null
    ];

    responder(200, 'OP encontrada correctamente.', $rta);

} catch (PDOException $e) {
    responder(500, 'Error al consultar la OP: ' . $e->getMessage());
} finally {
    $conn = null;
}