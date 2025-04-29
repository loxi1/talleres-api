<?php
require_once('../_lib/jwt/jwt-loader.php'); // Incluye funciones y configuración global

header('Content-Type: application/json');

// ✅ Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, 'Método no permitido. Solo se acepta POST.');
}

// ✅ Leer y decodificar el JSON de entrada
$input = file_get_contents('php://input');
$param = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    responder(400, 'JSON inválido.');
}

// ✅ Validar parámetros requeridos
$codigo   = $param['codigo'] ?? null;
$password = $param['password'] ?? null;

if (empty($codigo) || empty($password)) {
    responder(422, 'Se requiere código y contraseña.');
}

try {
    $conn = conectar_sybase();

    $stmt = $conn->prepare("SELECT codigo, datos, clave FROM usuario_timbrado WHERE codigo = :codigo AND estado = 'ACTIVO'");
    $stmt->bindParam(':codigo', $codigo);
    $stmt->execute();

    $usuario = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$usuario) {
        responder(404, 'No se encontraron datos para el código ingresado.');
    }

    foreach ($usuario as $row) {
        $clave_encriptada   = $row['clave'];
        $clave_input_base64 = encrypt($password);

        if ($clave_input_base64 === $clave_encriptada) {
            $payload = [
                'codigo' => $row['codigo'],
                'datos'  => $row['datos']
            ];

            $jwt = firmar_token($payload);

            responder(200, 'Autenticación exitosa.', [
                'token' => $jwt
            ]);
        }
    }

    responder(401, 'Las credenciales no son válidas.');
} catch (PDOException $e) {
    responder(500, 'Error de base de datos: ' . $e->getMessage());
} finally {
    $conn = null;
}
