<?php
require 'cors.php'; // Incluir el manejador de CORS
include_once 'conexion.php'; 
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $usuarioId = $data['usuario_id'];
    $opcionesIds = $data['opciones_id'];
    $pdo = Conexion::Conectar();    
    // Eliminar opciones existentes del usuario
    $stmt = $pdo->prepare("DELETE FROM siga_usuarios_opciones WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);

    // Insertar las nuevas opciones
    $stmt = $pdo->prepare("INSERT INTO siga_usuarios_opciones (usuario_id, opcion_id) VALUES (?, ?)");
    foreach ($opcionesIds as $opcionId) {
        $stmt->execute([$usuarioId, $opcionId]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Opciones asignadas correctamente']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Petición no válida']);
}
?>
