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

// ✅ Leer y validar el JSON de entrada
$input = file_get_contents('php://input');
$param = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    responder(400, 'JSON inválido.');
}

$op = $param['op'] ?? null;
if (empty($op) || strlen($op) != 5) {
    responder(422, 'Se requiere el parámetro "op" con 5 caracteres.');
}

try {
    $conn = conectar_sybase();

    // ✅ Importante: concatenar el comodín en PHP, no en el SQL directamente
    $opLike = "%{$op}";
    $sql = "SELECT norpd FROM althmc WHERE norpd LIKE :op ORDER BY norpd";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':op', $opLike);
    $stmt->execute();

    $rtaop = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rtaop) {
        responder(404, 'No se encontró ninguna OP que coincida.');
    }

    $rta = [
        'op' => $rtaop[0]['norpd'] ?? null
    ];

    responder(200, 'OP encontrada correctamente.', $rta);

} catch (PDOException $e) {
    responder(500, 'Error al consultar la OP: ' . $e->getMessage());
} finally {
    $conn = null;
}