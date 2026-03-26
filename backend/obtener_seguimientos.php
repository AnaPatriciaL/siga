<?php
// header('Content-Type: application/json');
header("Content-Type: application/json; charset=UTF-8");
include_once 'cors.php';
include_once 'conexion.php'; // Incluye tu archivo de conexión a la base de datos

// Crear una nueva conexión
$objeto = new Conexion();
$conexion = $objeto->Conectar();

// Consulta para obtener las opciones del usuario
$sql = "SELECT a.cve,a.descripcion,b.descripcion as estado 
        FROM sia_cat_seguimientos as a
        INNER JOIN sia_cat_estados_seguimientos as b ON a.estado = b.cve";

// Preparar y ejecutar la consulta
$stmt = $conexion->prepare($sql);
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
