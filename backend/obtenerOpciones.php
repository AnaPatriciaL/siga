<?php
require 'cors.php'; // Incluir el manejador de CORS
include_once 'conexion.php'; 
header("Content-Type: application/json; charset=UTF-8");

// Crear la conexión correctamente
$pdo = Conexion::Conectar();

// Obtener todas las opciones
$stmt = $pdo->prepare("SELECT id, nombre_opcion, ruta, orden FROM siga_opciones");
$stmt->execute();
$options = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['status' => 'success', 'opciones' => $options]);
