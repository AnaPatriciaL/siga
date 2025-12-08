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
        case 'POST': // Crear Programador
            if (!isset($data['programador'], $data['nombre_completo'], $data['estatus'], $data['tipo_programador'])) {
                throw new Exception('Faltan datos para crear el Programador');
            }

            $programador = $data['programador'];
            $nombre_completo = $data['nombre_completo'];
            $estatus = $data['estatus'];
            $tipo_programador = $data['tipo_programador'];

            $consulta = "INSERT INTO siga_prospectosie_programadores (usuario, nombre_completo, estatus, tipo_programador) VALUES (:programador, :nombre_completo, :estatus, :tipo_programador)";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute([
                ':programador' => $programador,
                ':nombre_completo' => $nombre_completo,
                ':estatus' => $estatus,
                ':tipo_programador' => $tipo_programador
            ]);

            $response['mensaje'] = 'Programnador registrado correctamente';
            break;

        case 'GET': // Leer Programadores
            $consulta = "SELECT * FROM siga_prospectosie_programadores";
            $resultado = $conexion->query($consulta);
            $response = $resultado->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'PUT': // Actualizar Programador
            if (!isset($data['id'], $data['programador'], $data['nombre_completo'],$data['estatus'], $data['tipo_programador'])) {
                throw new Exception('Faltan datos para actualizar el programador');
            }

            $consulta = "UPDATE siga_prospectosie_programadores SET usuario = :programador, nombre_completo = :nombre_completo, estatus = :estatus, tipo_programador = :tipo_programador WHERE id = :id";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute([
                ':id' => $data['id'],
                ':usuario' => $data['programador'],
                ':nombre_completo' => $data['nombre_completo'],
                ':estatus' => $data['estatus'],
                ':tipo_programador' => $data['tipo_programador'],
            ]);

            $response['mensaje'] = 'Programador actualizado correctamente';
            break;

        case 'DELETE': // Eliminar Programador (marcar como inactivo)
            if (!isset($data['id'])) {
                throw new Exception('Faltan datos para eliminar al programador');
            }

            $consulta = "UPDATE siga_prospectosie_programadores SET estatus = 0 WHERE id = :id";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute([':id' => $data['id']]);

            $response['mensaje'] = 'Programador eliminado correctamente';
            break;

        default:
            throw new Exception('Método no permitido');
    }
} catch (Exception $e) {
    $response['mensaje'] = $e->getMessage();
}

echo json_encode($response);

?>
