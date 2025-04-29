<?php
require_once('../_lib/jwt/env.php');
$clave = "1234";
$encrip = encrypt($clave);
$token = getJWT();
echo "<pre>";
print_r("La calve {$clave} encriptada es {$encrip} token->{$token}");
echo "</pre>";

header('Content-Type: application/json');
// Función para responder con JSON y detener la ejecución
function responder(int $code, string $msn, array $data = []): never {
    http_response_code($code);
    echo json_encode([
        'code' => $code,
        'msn'  => $msn,
        'data' => $data
    ]);
    exit;
}

// ✅ Verificar el metodo de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responder(405, 'Método no permitido. Solo se acepta GET.');
}


// Consulta en Sybase usando Scriptcase
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
    responder(404, 'No se encontraron datos.');
}


switch ($request_method) {
		case 'GET':
			// Consulta SQL
			$select_sql_sybase = "SELECT identificador, codigo, datos FROM usuario_timbrado WHERE estado = 'ACTIVO'";
			//echo "<pre>";
			//print_r($select_sql_sybase); // Muestra el contenido de la variable rs_data_sybase
			//echo "</pre>";
			// Ejecuta la consulta y guarda los resultados en la variable rs_data_sybase
			sc_lookup(rs_data_sybase, $select_sql_sybase);
			$respuesta = array('error' => 'No se encontraron datos.');
			// Verifica si se obtuvieron resultados
			if (isset({rs_data_sybase}) && count({rs_data_sybase}) > 0) {
				// Inicializa un array para almacenar los datos
				$respuesta = array();
				
				// Recorre los resultados
				foreach ({rs_data_sybase} as $row) {
					// Cada fila será agregada al array de respuesta
					$respuesta[] = array(
						'identificador' => $row[0], // primer campo
						'codigo' => $row[1],         // segundo campo
						'datos' => mb_convert_encoding($row[2], 'UTF-8', 'CP850')
					);
				}				
			}

			// Convierte el array a formato JSON y lo imprime
			echo json_encode($respuesta);

		break;
		
		case 'POST':
		break;
}