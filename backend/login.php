<?php
require 'cors.php';
require 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

$data = json_decode(file_get_contents("php://input"), true);
$response = ["success" => false, "message" => "Operación fallida"];

// Verificar que se proporcionen ambos campos
if (!isset($data['usuario'], $data['contrasena'])) {
    $response["message"] = "Usuario y contraseña son requeridos";
    echo json_encode($response);
    exit();
}

$usuario = $data['usuario'];
$contrasena = $data['contrasena'];

try {
    // Consultar si el usuario existe y obtener la contraseña hasheada
    $query = "SELECT id, contrasena, nombre FROM siga_usuarios WHERE usuario = :usuario LIMIT 1";
    $stmt = $conexion->prepare($query);
    $stmt->execute([':usuario' => $usuario]);

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar la contraseña usando password_verify
        if (password_verify($contrasena, $user['contrasena'])) {
            // Generar un token de sesión (ejemplo simple; en producción, usa JWT)
            $token = bin2hex(random_bytes(16));
            
            $response = [
                "success"   =>  true,
                "token"     =>  $token,
                "nombre"    =>  $user['nombre'],
                "id"        =>  $user['id'],
                "message"   =>  "Inicio de sesión exitoso"
            ];
        } else {
            $response["message"] = "Usuario o contraseña incorrectos";
        }
    } else {
        $response["message"] = "Usuario no encontrado";
    }
} catch (Exception $e) {
    $response["message"] = "Error de servidor: " . $e->getMessage();
}

// Cerrar la conexión
$conexion = null;

echo json_encode($response);

?>
