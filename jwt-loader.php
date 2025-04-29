<?php
// 🔐 Cargar primero la interfaz (para evitar errores de dependencias)
require_once __DIR__ . '/src/JWTExceptionWithPayloadInterface.php';

// Luego las excepciones que la implementan
require_once __DIR__ . '/src/SignatureInvalidException.php';
require_once __DIR__ . '/src/ExpiredException.php';
require_once __DIR__ . '/src/BeforeValidException.php';

// Clases principales
require_once __DIR__ . '/src/Key.php';
require_once __DIR__ . '/src/JWT.php';
