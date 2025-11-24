<?php
require 'cors.php'; // Incluir el manejador de CORS
include_once 'conexion.php'; 
header("Content-Type: application/json; charset=UTF-8");

try {
     $pdo = Conexion::Conectar();
    // Obtener todos los usuarios
    $stmt = $pdo->prepare("SELECT id, usuario, nombre FROM siga_usuarios");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recorrer cada usuario y obtener sus opciones
    foreach ($users as &$user) {
        $usuario_id = $user['id'];

        // Obtener las opciones asignadas al usuario
        $stmtOpciones = $pdo->prepare("
            SELECT eo.nombre_opcion 
            FROM siga_opciones eo
            INNER JOIN siga_usuarios_opciones euo ON eo.id = euo.opcion_id
            WHERE euo.usuario_id = ?
        ");
        $stmtOpciones->execute([$usuario_id]);
        $opciones = $stmtOpciones->fetchAll(PDO::FETCH_COLUMN);

        // Formatear las opciones como un string separado por comas
        $user['opciones'] = implode(', ', $opciones);
    }

    // Respuesta con los usuarios y sus opciones
    echo json_encode(['status' => 'success', 'usuarios' => $users]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
