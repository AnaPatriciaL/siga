<?php
include_once 'cors.php';
include_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Crear conexión
    $objeto = new Conexion();
    $conexion = $objeto->Conectar();

    // Consulta
    $sql = "SELECT id, nombre FROM siga_prospectos_fuentes"; 
    $stmt = $conexion->prepare($sql);
    $stmt->execute();

    // Obtener datos
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    print json_encode($data, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // En caso de error
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "mensaje" => "Error en la base de datos: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    // Cerrar conexión
    $conexion = null;
}
