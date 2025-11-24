<?php
include_once 'cors.php';
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

$_POST = json_decode(file_get_contents("php://input"), true);

$opcion = (isset($_POST['opcion'])) ? $_POST['opcion'] : '';
$data = null;

switch($opcion){
    case 1: // Consulta de folios
        $consulta = "SELECT * FROM siga_prospectosie_folios_oficios ORDER BY num_folio";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data=$resultado->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 2: // Inserción de nuevos folios
        // Se reciben el folio inicial y la cantidad de folios a insertar
        $folio_inicial = (isset($_POST['folio_inicial'])) ? $_POST['folio_inicial'] : 0;
        $cantidad = (isset($_POST['cantidad'])) ? $_POST['cantidad'] : 0;
        $anio = date('Y'); // Obtener el año actual

        if ($folio_inicial > 0 && $cantidad > 0) {
            $conexion->beginTransaction();
            try {
                $consulta = "INSERT INTO siga_prospectosie_folios_oficios (num_folio, anio, estatus) VALUES (:num_folio, :anio, 0)";
                $resultado = $conexion->prepare($consulta);
                
                for ($i = 0; $i < $cantidad; $i++) {
                    $num_folio_actual = $folio_inicial + $i;
                    $resultado->bindParam(':num_folio', $num_folio_actual, PDO::PARAM_INT);
                    $resultado->bindValue(':anio', $anio, PDO::PARAM_INT);
                    $resultado->execute();
                }
                $conexion->commit();
                $data = ['mensaje' => 'Folios insertados correctamente'];
            } catch (Exception $e) {
                $conexion->rollBack();
                $data = ['error' => 'Error en la inserción: ' . $e->getMessage()];
            }
        } else {
            $data = ['error' => 'Folio inicial y cantidad deben ser mayores a 0'];
        }
        break;

    case 3: // Obtener y marcar el siguiente folio disponible
        $conexion->beginTransaction();
        try {
            // 1. Encontrar el primer folio disponible (estatus 0 con el menor num_folio)
            $consulta_folio = "SELECT * FROM siga_prospectosie_folios_oficios WHERE estatus = 0 ORDER BY num_folio ASC LIMIT 1 FOR UPDATE";
            $resultado_folio = $conexion->prepare($consulta_folio);
            $resultado_folio->execute();
            $folio_disponible = $resultado_folio->fetch(PDO::FETCH_ASSOC);

            if ($folio_disponible) {
                // 2. Si se encontró un folio, actualizar su estatus a 1 (utilizado)
                $consulta_update = "UPDATE siga_prospectosie_folios_oficios SET estatus = 1 WHERE id = :id";
                $resultado_update = $conexion->prepare($consulta_update);
                $resultado_update->bindParam(':id', $folio_disponible['id'], PDO::PARAM_INT);
                $resultado_update->execute();
                $data = $folio_disponible; // Devolver el folio que se acaba de marcar
            } else {
                $data = ['error' => 'No hay folios disponibles'];
            }
            $conexion->commit();
        } catch (Exception $e) {
            $conexion->rollBack();
            $data = ['error' => 'Error en la transacción: ' . $e->getMessage()];
        }
        break;
    default:
        $data = ['error' => 'Opción no válida'];
        break;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = NULL;
