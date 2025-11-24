<?php
	include_once 'cors.php';
	include_once 'conexion.php';
	$objeto = new Conexion();
	$conexion = $objeto->Conectar();

	$_POST = json_decode(file_get_contents("php://input"), true);

	// Login
	$user   =   (isset($_POST['usuario']))  ?   $_POST['usuario'] : '';
	$pass   =   (isset($_POST['pass']))     ?   $_POST['pass'] : '';

	$data=null;

	$consulta = "SELECT id,usuario,pass,nombre,nivel
                FROM siga_prospectosie_usuarios 
                WHERE usuario = '$user' AND pass = '$pass' AND estatus = 1";		
	$resultado = $conexion->prepare($consulta);
	$resultado->execute();     
	$data	=	$resultado->fetch(PDO::FETCH_ASSOC);

	if($data){
		session_start();
		$_SESSION['activoProspectosIE']   =   true;
        $_SESSION['id_usuario']     =   $data['usuario'];
        // $_SESSION['rfc']            =   $data['pass'];
        $_SESSION['nombre_usuario'] =   $data['nombre'];
		$_SESSION['nivel'] =   $data['nivel'];
        // $_SESSION['activo']         =   $data['activo'];
        print json_encode(["exito" => true, "datos" => $data], JSON_UNESCAPED_UNICODE);
	}
	else{
		print json_encode(["exito" => false], JSON_UNESCAPED_UNICODE);
	}

	$conexion = NULL;