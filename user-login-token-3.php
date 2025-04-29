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

// ✅ Consultar base de datos (Sybase) usando Scriptcase
$select_sql_sybase = "
    SELECT codigo, datos, clave
    FROM usuario_timbrado
    WHERE codigo = '{$codigo}' AND estado = 'ACTIVO'
";
sc_lookup(rs_data_sybase, $select_sql_sybase);

if (!isset({rs_data_sybase}) || !is_array({rs_data_sybase})) {
    responder(500, 'Error al ejecutar la consulta.');
}

if (count({rs_data_sybase}) === 0) {
    responder(404, 'No se encontraron datos para el código ingresado.');
}

// ✅ Verificar contraseña encriptada y generar JWT si es válida
foreach ({rs_data_sybase} as $row) {
    $clave_encriptada     = $row[2];
    $clave_input_base64   = encrypt($password); // 🔐 usa la función compartida

    if ($clave_input_base64 === $clave_encriptada) {
        // Armado del payload
        $payload = [
            'codigo' => $row[0],
            'datos'  => $row[1]
            // Las claves estándar (iss, aud, iat, exp) ya las incluye firmar_token()
        ];

        $jwt = firmar_token($payload); // ✅ JWT generado desde función central

        responder(200, 'Autenticación exitosa.', [
            'token' => $jwt
        ]);
    }
}

// ❌ Contraseña no coincide
responder(401, 'Las credenciales no son válidas.');
