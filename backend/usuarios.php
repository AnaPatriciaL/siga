<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

$data = json_decode(file_get_contents("php://input"), true);
$response = ['mensaje' => 'Operación no realizada'];

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST': // Crear Usuario
            if (!isset($data['usuario'], $data['nombre'], $data['contrasena'], $data['nivel'], $data['programador_id'], $data['iniciales'])) {
                throw new Exception('Faltan datos para crear el usuario');
            }

            $usuario = $data['usuario'];
            $nombre = $data['nombre'];
            $contrasena = password_hash($data['contrasena'], PASSWORD_BCRYPT);
            $nivel = $data['nivel'];
            $programador_id = $data['programador_id'];
            $iniciales = $data['iniciales'];
            $activo = isset($data['activo']) ? $data['activo'] : 1;

            $consulta = "INSERT INTO siga_usuarios (nombre, usuario, contrasena, nivel, programador_id, iniciales, activo) VALUES (:nombre, :usuario, :contrasena, :nivel, :programador_id, :iniciales, :activo)";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute([
                ':usuario' => $usuario,
                ':nombre' => $nombre,
                ':contrasena' => $contrasena,
                ':nivel' => $nivel,
                ':programador_id' => $programador_id,
                ':iniciales' => $iniciales,
                ':activo' => $activo
            ]);

            $response['mensaje'] = 'Usuario registrado correctamente';
            break;

        case 'GET': // Leer Usuarios
            $consulta = "SELECT * FROM siga_usuarios";
            $resultado = $conexion->query($consulta);
            $response = $resultado->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'PUT': // Actualizar Usuario
            if (!isset($data['id'], $data['usuario'], $data['nombre'], $data['activo'])) {
                throw new Exception('Faltan datos para actualizar el usuario');
            }

            $params = [
                ':id' => $data['id'],
                ':usuario' => $data['usuario'],
                ':nombre' => $data['nombre'],
                ':activo' => $data['activo'],
                ':nivel' => $data['nivel'],
                ':programador_id' => $data['programador_id'],
                ':iniciales' => $data['iniciales']
            ];

            $sql_password = "";
            if (!empty($data['contrasena'])) {
                $sql_password = ", contrasena = :contrasena";
                $params[':contrasena'] = password_hash($data['contrasena'], PASSWORD_BCRYPT);
            }
            $consulta = "UPDATE siga_usuarios SET nombre = :nombre, usuario = :usuario, activo = :activo, nivel= :nivel, programador_id= :programador_id, iniciales= :iniciales $sql_password WHERE id = :id";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute($params);

            $response['mensaje'] = 'Usuario actualizado correctamente';
            break;

        case 'DELETE': // Eliminar Usuario (marcar como inactivo)
            if (!isset($data['id'])) {
                throw new Exception('Faltan datos para eliminar el usuario');
            }

            $consulta = "UPDATE siga_usuarios SET activo = 0 WHERE id = :id";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute([':id' => $data['id']]);

            $response['mensaje'] = 'Usuario eliminado correctamente';
            break;

        default:
            throw new Exception('Método no permitido');
    }
} catch (Exception $e) {
    $response['mensaje'] = $e->getMessage();
}

echo json_encode($response);

?>
