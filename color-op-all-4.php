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
if (empty($op)) {
    responder(422, 'Se requiere el parámetro "op".');
}

try {
    $conn = conectar_sybase();
    $sql = "SELECT DISTINCT cclrcl, tclrcl FROM altopd WHERE nnope = :op ORDER BY tclrcl";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':op', $op);
    $stmt->execute();

    $colores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$colores) {
        responder(404, 'No se encontraron colores para la OP ingresada.');
    }

    $rta = array_map(function ($row) {
        return [
            'codigo' => $row['cclrcl'],
            'datos'  => mb_convert_encoding($row['tclrcl'], 'UTF-8', 'CP850')
        ];
    }, $colores);

    responder(200, 'Colores obtenidos correctamente.', $rta);

} catch (PDOException $e) {
    responder(500, 'Error al consultar la base de datos: ' . $e->getMessage());
} finally {
    $conn = null; // ✅ Cierre de conexión
}