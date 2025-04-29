<?php
require_once __DIR__ . '/jwt-loader.php'; // ✅ Carga las clases JWT manualmente

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$key = '$C!4@2017'; // Clave secreta para la firma del JWT
$payload = [
    'iss' => 'http://192.168.150.43:8092',
    'aud' => 'miappmovil',
    'iat' => time(),
    'nbf' => time(),           // <- aquí corregido
    'exp' => time() + 7200,    // 2 horas de duración
    'codigo' => '32227',
    'datos'  => 'Carlos'
];


/**
* IMPORTANTE:
* Debe especificar los algoritmos compatibles con su aplicación. Consulte
* https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
* para obtener una lista de algoritmos que cumplen con las especificaciones.
*/
$jwt = JWT::encode($payload, $key, 'HS256');
$decoded = JWT::decode($jwt, new Key($key, 'HS256'));
print_r($decoded);

// Pase una stdClass como tercer parámetro para obtener los valores del encabezado decodificados
$headers = new stdClass();
$decoded = JWT::decode($jwt, new Key($key, 'HS256'), $headers);
print_r($headers);

/*
 NOTA: Esto ahora será un objeto en lugar de un array asociativo. Para obtener un array asociativo, deberá convertirlo a:
*/

$decoded_array = (array) $decoded;

/**
* Puede añadir un margen de tiempo para tener en cuenta los desfases de reloj entre los servidores de firma y verificación. Se recomienda que este margen no supere unos pocos minutos.
*
* Fuente: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
 */
JWT::$leeway = 60; // $leeway en sgundos
$decoded = JWT::decode($jwt, new Key($key, 'HS256'));
