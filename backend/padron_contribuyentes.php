<?php
include_once 'cors.php';
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

$_POST = json_decode(file_get_contents("php://input"), true);

$rfc = (isset($_POST['rfc'])) ? $_POST['rfc'] : '';

$consulta1 = "SELECT rfc, nombre, calle, num_exterior, num_interior, colonia, localidad, cp, municipio_id, giro, oficina_id
			FROM siga_prospectos 
			WHERE rfc = :rfc";
$stmt = $conexion->prepare($consulta1);
$stmt->bindParam(':rfc', $rfc);
$stmt->execute();

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($data) === 0) {
	$consulta2 = "SELECT ps.rfc, ps.nombre_comercial AS nombre, ps.calle, ps.numero_exterior AS num_exterior, ps.numero_interior AS num_interior, 
		ps.colonia, ps.localidad, ps.codigo_postal AS cp, ps.municipio, m.municipio_id, ps.actividad AS giro, m.oficina_id
				FROM pdg_sin ps 
				LEFT JOIN siga_prospectos_municipios m ON ps.MUNICIPIO = m.nombre  
				WHERE rfc = :rfc";
	$stmt = $conexion->prepare($consulta2);
    $stmt->bindParam(':rfc', $rfc);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);

$conexion = null;