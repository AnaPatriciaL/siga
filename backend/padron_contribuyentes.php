<?php
include_once 'cors.php';
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

$_POST = json_decode(file_get_contents("php://input"), true);

// function permisos() {  
// 	if (isset($_SERVER['HTTP_ORIGIN'])){
// 		header("Access-Control-Allow-Origin: *");
// 		header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
// 		header("Access-Control-Allow-Headers: Origin, Authorization, X-Requested-With, Content-Type, Accept");
// 		header('Access-Control-Allow-Credentials: true');      
// 	}  
// 	if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
// 	  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))          
// 		  header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
// 	  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
// 		  header("Access-Control-Allow-Headers: Origin, Authorization, X-Requested-With, Content-Type, Accept");
// 	  exit(0);
// 	}
//   }
//   permisos();


$rfc = (isset($_POST['rfc'])) ? $_POST['rfc'] : '';



$consulta = "select a.rfc_vig as rfc,";
							if (strlen($rfc)>12) {
								$consulta	=	$consulta."CONCAT(a.nombre,' ',A.ap_paterno,' ',A.ap_materno) as nombre,"; 
							}else{
								$consulta	=	$consulta."IF(ISNULL(a.nombre_comercial),a.razon_social,a.nombre_comercial) as nombre,"; 
							}
$consulta=$consulta."CONCAT(RTRIM(a.calle),' No. ',A.no_exterior,IF(ISNULL(a.no_interior), '',CONCAT(' INT. ',no_interior))) as domicilio ,
								IF(ISNULL(b.nombre),'SIN DATO',b.nombre) as colonia, 
								a.cv_cp as cp, 
								D.nombre AS localidad,
								CONCAT(C.nombre,', SINALOA') as ciudad";
								if (strlen($rfc)>12) {
									$consulta	=	$consulta." from 24pf1021_pgpf as A ";
								}else{
									$consulta	=	$consulta." from 24pm1021_pgpm as A ";
								}
                    // $consulta=$consulta." from 24pm1021_pgpm as A";
$consulta=$consulta."LEFT JOIN cat_colonia as B ON A.cv_colonia=B.cv_colonia
								LEFT JOIN cat_municipio as C ON A.cv_municipio=C.cv_municipio
								LEFT JOIN cat_localidad as D ON A.cv_localidad=D.cv_localidad
								WHERE a.rfc_vig ='".$rfc."'";
$resultado = $conexion->prepare($consulta);
$resultado->execute();
$data=$resultado->fetchAll(PDO::FETCH_ASSOC);

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;