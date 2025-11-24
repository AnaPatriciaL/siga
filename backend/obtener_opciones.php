<?php
require 'cors.php'; // Incluir el manejador de CORS
header("Content-Type: application/json; charset=UTF-8");
include_once 'conexion.php'; // Incluye tu archivo de conexión a la base de datos

// Crear una nueva conexión
$objeto = new Conexion();
$conexion = $objeto->Conectar();

// Verificar si se ha proporcionado el `usuario_id`
if (!isset($_GET['usuario_id'])) {
    die("Error: usuario_id no proporcionado");
}
$usuario_id = $_GET['usuario_id'];

// Consulta para obtener las opciones del usuario
$sql = "SELECT siga_opciones.id, siga_opciones.nombre_opcion, siga_opciones.ruta, siga_opciones.icono, siga_opciones.color, siga_opciones.orden 
        FROM siga_opciones 
        INNER JOIN siga_usuarios_opciones ON siga_opciones.id = siga_usuarios_opciones.opcion_id 
        WHERE siga_usuarios_opciones.usuario_id = :usuario_id";

// Preparar y ejecutar la consulta
$stmt = $conexion->prepare($sql);
$stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt->execute();

// Obtener los resultados
$opciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Devolver los resultados en formato JSON
// echo json_encode($opciones);

echo json_encode([
    "success" => true,
    "data" => $opciones  // aquí va tu array de resultados
]);

?>
