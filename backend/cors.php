<?php
// cors.php - Versión controlada para pruebas con credentials

$allowed_origins = [
    'http://localhost:8080',
    'http://10.10.120.228:8080',
    'http://10.10.120.173:8080',
    'http://10.10.120.173:8081',
    'http://10.10.120.3:8080'
];

// Registrar el origen para depuración
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
file_put_contents('cors.log', "ORIGIN: $origin\n", FILE_APPEND);

// Verifica si el origin está en la lista permitida
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
}

// Manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No Content
    exit;
}
?>

