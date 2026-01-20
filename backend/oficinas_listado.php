<?php
include_once 'cors.php';
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

$_POST = json_decode(file_get_contents("php://input"), true);


    $consulta = "SELECT * FROM siga_prospectos_oficinas";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute();
    $data=$resultado->fetchAll(PDO::FETCH_ASSOC);

    print json_encode($data, JSON_UNESCAPED_UNICODE);
    $conexion = NULL;

