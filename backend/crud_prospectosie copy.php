<?php
include_once 'cors.php';
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

$_GET = json_decode(file_get_contents("php://input"), true);

$opcion = (isset($_GET['opcion'])) ? $_GET['opcion'] : '';
$id = (isset($_GET['id'])) ? $_GET['id'] : '';

if ($opcion < 4) {
  $rfc = (isset($_GET['rfc'])) ? $_GET['rfc'] : '';
  $nombre = (isset($_GET['nombre'])) ? $_GET['nombre'] : '';
  $domicilio = (isset($_GET['domicilio'])) ? $_GET['domicilio'] : '';
  $colonia = (isset($_GET['colonia'])) ? $_GET['colonia'] : '';
  $cp = (isset($_GET['cp'])) ? $_GET['cp'] : '';
  $localidad = (isset($_GET['localidad'])) ? $_GET['localidad'] : '';
  $giro = (isset($_GET['giro'])) ? $_GET['giro'] : '';
  $oficina_id = (isset($_GET['oficina_id'])) ? $_GET['oficina_id'] : '';
  $periodos = (isset($_GET['periodos'])) ? $_GET['periodos'] : '';
  $antecedente_id = (isset($_GET['antecedente_id'])) ? $_GET['antecedente_id'] : '';
  $impuesto_id = (isset($_GET['impuesto_id'])) ? $_GET['impuesto_id'] : '';
  $determinado = (isset($_GET['determinado'])) ? $_GET['determinado'] : '';
  $programador_id = (isset($_GET['programador_id'])) ? $_GET['programador_id'] : '';
  $representante_legal = (isset($_GET['representante_legal'])) ? $_GET['representante_legal'] : '';
  $observaciones = (isset($_GET['observaciones'])) ? $_GET['observaciones'] : '';
}



switch ($opcion) {
  case 1:
    $consulta = "SELECT
                  a.*,
                  b.impuesto AS impuesto,
                  b.descripcion AS impuestos_descripcion,
                  d.usuario AS programador_descripcion,
                  d.nombre_completo AS programador_nombre_completo,
                  e.nombre AS oficina_descripcion,
                  CONCAT(
                      IF(a.domicilio IS NOT NULL AND a.domicilio != '', a.domicilio, ''),
                      IF(a.colonia IS NOT NULL AND a.colonia != '', CONCAT(', ', a.colonia), ''),
                      IF(a.cp IS NOT NULL AND a.cp != '', CONCAT(' C.P. ', a.cp), ''),
                      IF(a.localidad IS NOT NULL AND a.localidad != '', CONCAT(', ', a.localidad), '')
                  ) AS domicilio_completo,
                  CONCAT(
                      IF(a.cp IS NOT NULL AND a.cp != '', CONCAT('C.P. ', a.cp, ' '), ''),
                      IF(a.localidad IS NOT NULL AND a.localidad != '', a.localidad, '')
                  ) AS cp_municipio,
                  f.descripcion AS antecedente_descripcion 
                  -- CONCAT( a.domicilio, ', ', a.colonia, ' C.P. ', a.cp, ', ', a.localidad ) AS domicilio_completo 
                FROM
                  `prospectosie` AS a
                  LEFT OUTER JOIN prospectosie_impuestos AS b ON a.impuesto_id = b.id
                  LEFT OUTER JOIN prospectosie_programadores AS d ON a.programador_id = d.id
                  LEFT OUTER JOIN prospectosie_oficinas AS e ON a.oficina_id = e.id
                  LEFT OUTER JOIN prospectosie_antecedentes AS f ON a.antecedente_id=f.id
                WHERE a.estatus = 1";

    $resultado = $conexion->prepare($consulta);
    $resultado->execute();
    $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
    break;
  case 2: // Alta
    $consulta = "INSERT INTO    prospectosie 
                                    (rfc,
                                    nombre,
                                    domicilio,
                                    colonia,
                                    cp,
                                    localidad,
                                    oficina_id,
                                    giro,
                                    periodos,
                                    antecedente_id,
                                    impuesto_id,
                                    determinado,
                                    programador_id,
                                    representante_legal,
                                    observaciones) 
                            VALUES  ('$rfc',
                                    '$nombre',
                                    '$domicilio',
                                    '$colonia',
                                    '$cp',
                                    '$localidad',
                                    '$oficina_id',
                                    '$giro',
                                    '$periodos',
                                    '$antecedente_id',
                                    '$impuesto_id',
                                    '$determinado',
                                    '$programador_id',
                                    '$representante_legal',
                                    '$observaciones')";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute();
    // $ultimoId = $conexion->fetchColumn();
    // echo $ultimoID;
    $data = 'Nuevo';
    break;
  case 3: // Cambios
    $consulta = "UPDATE prospectosie 
                    SET nombre = '$nombre',
                        domicilio = '$domicilio',
                        colonia = '$colonia',
                        cp = '$cp',
                        localidad = '$localidad',
                        oficina_id = '$oficina_id',
                        giro = '$giro',
                        periodos = '$periodos',
                        antecedente_id = '$antecedente_id',
                        impuesto_id = '$impuesto_id',
                        determinado = '$determinado',
                        programador_id = '$programador_id', 
                        representante_legal = '$representante_legal',
                        observaciones = '$observaciones'
                        WHERE id='$id' ";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute();
    $data = $resultado->fetchAll(PDO::FETCH_ASSOC);
    break;
  case 4:
    // $consulta = "DELETE FROM prospectosie WHERE id='$id' ";		
    $consulta = "UPDATE prospectosie SET estatus = 0 WHERE id='$id' ";
    $resultado = $conexion->prepare($consulta);
    $resultado->execute();
    $data = 'Eliminado';
    break;
  default:
    $data = ['error' => 'Opción inválida o no enviada'];
    break;
}

echo json_encode($data ?? ['error' => 'Sin datos procesados'], JSON_UNESCAPED_UNICODE);
$conexion = NULL;
