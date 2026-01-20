<?php
include_once 'cors.php';
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

$_POST = json_decode(file_get_contents("php://input"), true);

$rfc = (isset($_POST['rfc'])) ? $_POST['rfc'] : '';

$consulta = "SELECT rfc, nombre_comercial AS nombre, calle, numero_exterior AS num_exterior, numero_interior AS num_interior, 
	colonia, localidad, codigo_postal AS cp, municipio, actividad AS giro
			FROM pdg_sin 
			WHERE rfc ='".$rfc."'";
$resultado = $conexion->prepare($consulta);
$resultado->execute();
$data=$resultado->fetchAll(PDO::FETCH_ASSOC);

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;