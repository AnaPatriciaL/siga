<?php
// Configuración de la base de datos
require 'cors.php'; // Incluir el manejador de CORS
include_once 'conexion.php';
header("Content-Type: application/json; charset=UTF-8");

try {
    // Obtener la conexión PDO
    $pdo = Conexion::Conectar();
    // Obtener el ID del usuario desde la solicitud
    $usuario_id = isset($_GET['usuario_id']) ? $_GET['usuario_id'] : null;

    if (!$usuario_id) {
        // Si el usuario_id no está proporcionado, devolver un error
        echo json_encode(["error" => "Se requiere el usuario_id"]);
        exit;
    }

    // Consulta para obtener las opciones del usuario
    $sql = "SELECT opcion_id FROM siga_usuarios_opciones WHERE usuario_id = :usuario_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();

    // Obtener los resultados
    $opciones = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Devolver las opciones en formato JSON
    echo json_encode($opciones);

} catch (PDOException $e) {
    // Manejo de errores
    echo json_encode(["error" => "Error en la conexión: " . $e->getMessage()]);
}

// Cerrar la conexión (PDO la cierra automáticamente al final del script)
?>
