<?php
require_once __DIR__ . '/jwt-loader.php'; // ✅ Carga las clases JWT manualmente

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
$jwt_key = '[asdfasd$ED8/*¡¡]';
$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTkyLjE2OC4xNTAuNDM6ODA5MiIsImF1ZCI6Im1pYXBwbW92aWwiLCJpYXQiOjE3NDM3ODkxMzAsIm5iZiI6MTc0Mzc4OTEzMCwiZXhwIjoxNzQzNzk2MzMwLCJjb2RpZ28iOiIzMjIyNyIsImRhdG9zIjoiQ2FybG9zIn0.FuTOcVsuyTuUz4jw8-oyDmV7velbH9b2AmwJwq1o5mw';
try {
    $decoded = JWT::decode($token, new Key($jwt_key, 'HS256'));

    if ($decoded->iss !== 'http://192.168.150.43:8092') {
        throw new Exception("Emisor inválido");
    }

    if ($decoded->aud !== 'miappmovil') {
        throw new Exception("Audiencia no autorizada");
    }
    echo "Token válido\n";
    echo "Código: " . $decoded->codigo . "\n";  // Acceso a los datos decodificados
    echo "Datos: " . $decoded->datos . "\n";    // Acceso a los datos decodificados        
    // Token válido 
    // Puedes usar $decoded->codigo, $decoded->datos, etc.

} catch (Exception $e) {
    responder(401, 'Token inválido: ' . $e->getMessage());
}
