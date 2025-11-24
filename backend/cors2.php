<?php
// cors.php
file_put_contents('cors.log', "ORIGIN: " . ($_SERVER['HTTP_ORIGIN'] ?? 'N/A') . "\n", FILE_APPEND);

// $allowed_origins = [
//     'http://localhost',
//     'http://10.10.120.180',
//     'http://10.10.120.173:8080',
//     'http://10.10.120.3:8080' // <--- Asegúrate que este esté presente
// ];
// Solo poner headers si el origin está permitido
// if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
//     header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
//     header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
//     header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
//     header("Access-Control-Allow-Credentials: true");
// }

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");



// Responder OPTIONS y salir inmediatamente
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

