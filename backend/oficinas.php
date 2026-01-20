<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once 'conexion.php';

$data = json_decode(file_get_contents("php://input"), true);
$response = ['mensaje' => 'Operación no realizada'];

try {
    $objeto = new Conexion();
    $conexion = $objeto->Conectar();

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'OPTIONS': // Manejar la solicitud de pre-verificación CORS
            http_response_code(200);
            exit();
            break;

        case 'GET': // Obtener listado de oficinas
            $consulta = "SELECT id, nombre, grupo, domicilio, fraccion, telefono FROM siga_prospectos_oficinas";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute();
            $response = $resultado->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'PUT': // Actualizar Datos de Oficina
            if (!isset($data['id'])) {
                throw new Exception('Falta el ID de la oficina para actualizar');
            }
            $id = $data['id'];
            $grupo = isset($data['grupo']) ? trim($data['grupo']) : null;
            $domicilio = isset($data['domicilio']) ? trim($data['domicilio']) : null;
            $fraccion = isset($data['fraccion']) ? trim($data['fraccion']) : null;
            $telefono = isset($data['telefono']) ? trim($data['telefono']) : null;

            // --- INICIO: LÓGICA DE BASE DE DATOS ---
            // Consulta SQL para actualizar la tabla de oficinas.
            $consulta = "UPDATE siga_prospectos_oficinas SET grupo = :grupo, domicilio = :domicilio, fraccion= :fraccion, telefono= :telefono WHERE id = :id";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute([':grupo' => $grupo, ':domicilio' => $domicilio,':fraccion' => $fraccion,':telefono' => $telefono, ':id' => $id]);
            // --- FIN: LÓGICA DE BASE DE DATOS ---

            if ($resultado->rowCount() > 0) {
                $response['mensaje'] = 'Los datos de Oficina han sido actualizados correctamente';
            } else {
                $response['mensaje'] = 'No se realizaron cambios o la oficina no fue encontrada.';
            }
            break;

        case 'POST': // Crear una nueva oficina (ejemplo)
            // Aquí iría la lógica para crear una nueva oficina
            $response['mensaje'] = 'Lógica para crear oficina no implementada.';
            break;

        default:
            throw new Exception('Método no permitido');
    }
} catch (Exception $e) {
    http_response_code(400); // Bad Request
    $response['mensaje'] = $e->getMessage();
}

echo json_encode($response);
?>
