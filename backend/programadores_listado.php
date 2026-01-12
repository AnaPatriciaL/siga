<?php
include_once 'cors.php';
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

$_POST = json_decode(file_get_contents("php://input"), true);

$opcion = (isset($_POST['opcion'])) ? $_POST['opcion'] : '';
$id = (isset($_POST['id'])) ? $_POST['id'] : '';
$data = []; 

switch($opcion){
    case 1:
        $consulta = "SELECT id,usuario,nombre_completo FROM siga_prospectos_programadores WHERE estatus=1";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data=$resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 2:
        // Se usa el ID enviado desde el frontend.
        
        $consulta_usuario = "SELECT programador_id FROM siga_usuarios WHERE id = :id";
        $resultado_usuario = $conexion->prepare($consulta_usuario);
        $resultado_usuario->bindParam(':id', $id, PDO::PARAM_INT);
        $resultado_usuario->execute();
        $usuario = $resultado_usuario->fetch(PDO::FETCH_ASSOC);

        if ($usuario['programador_id'] == 0) {
            $consulta = "SELECT id, usuario, nombre_completo FROM siga_prospectos_programadores WHERE estatus=1";
            $resultado = $conexion->prepare($consulta);
        } else {
            $consulta = "SELECT a.*,b.id, b.nivel, b.iniciales,b.programador_id FROM siga_prospectos_programadores AS a LEFT JOIN siga_usuarios AS b ON a.id = b.programador_id WHERE a.estatus = 1 and b.id = :id";
            $resultado = $conexion->prepare($consulta);
            $resultado->bindParam(':id', $id, PDO::PARAM_INT);
        }
        $resultado->execute();
        $data=$resultado->fetchAll(PDO::FETCH_ASSOC);
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;
