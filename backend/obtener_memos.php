<?php
require 'cors.php'; 
header("Content-Type: application/json; charset=UTF-8");
include_once 'conexion.php'; 

$objeto = new Conexion();
$conexion = $objeto->Conectar();



?>
