<?php
define('ENCRYPT_METHOD', '');
define('SECRET_KEY',     '');
define('SECRET_IV',      '');
define('JWT',      '$C!4@2017');

function decrypt($string) {
    $key = hash('sha256', SECRET_KEY, true);             // 32 bytes
    $iv  = substr(hash('sha256', SECRET_IV, true), 0, 16); // 16 bytes

    $output = openssl_decrypt(base64_decode($string), ENCRYPT_METHOD, $key, OPENSSL_RAW_DATA, $iv);
    return rtrim($output, "\0");
}

function encrypt($string) {
    $key = hash('sha256', SECRET_KEY, true);             // 32 bytes
    $iv  = substr(hash('sha256', SECRET_IV, true), 0, 16); // 16 bytes

    $output = openssl_encrypt($string, ENCRYPT_METHOD, $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($output);
}

function getJWT() {
    return JWT;
}

function getToken() {
    $jwt = JWT::encode($payload, $jwt_key, 'HS256');
}