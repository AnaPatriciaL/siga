<?php
include_once 'cors.php'; // debe ir antes de cualquier salida

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verificar si las variables de sesión están activas
$response = [
    'activoProspectosIE' => $_SESSION['activoProspectosIE'] ?? null,
    'id_usuario' => $_SESSION['id_usuario'] ?? null,
    'nombre_usuario' => $_SESSION['nombre_usuario'] ?? null,
    'nivel' => $_SESSION['nivel'] ?? null,
];

// Enviar respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);
?>

