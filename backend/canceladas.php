<?php
ob_start(); // Inicia el buffer de salida para capturar cualquier salida no deseada.

include_once 'cors.php';
include_once 'conexion.php';

function getConexion() {
    $objeto = new Conexion();
    return $objeto->Conectar();
}

$_POST = json_decode(file_get_contents("php://input"), true);
$opcion = (isset($_POST['opcion'])) ? $_POST['opcion'] : '';

switch ($opcion) {
    case 1:
        try{
            $num_orden = (isset($_POST['num_orden'])) ? $_POST['num_orden'] : '';
            $anio = (isset($_POST['anio'])) ? $_POST['anio'] : '';
            $conexion = getConexion();
            $consulta = "SELECT a.id_orden, a.num_orden, a.num_oficio, b.rfc, b.nombre, b.id AS id_prospecto
                        FROM siga_prospectos_ordenes a
                        LEFT JOIN siga_prospectos b ON a.id_prospecto = b.id 
                        WHERE a.num_orden = ? AND a.estatus = 1 AND a.anio = ?";
            $sentencia = $conexion->prepare($consulta);
            $sentencia->execute([$num_orden, $anio]);
            $data = $sentencia->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se encontró la orden.']);
            }
        }
        catch (Exception $e) {
            $conexion->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error al consultar la orden: ' . $e->getMessage()]);
        }
         $conexion = null;
        break;

    case 2:
        $id_orden = (isset($_POST['id_orden'])) ? $_POST['id_orden'] : '';
        $num_oficio = (isset($_POST['num_oficio'])) ? $_POST['num_oficio'] : '';
        $num_orden = (isset($_POST['num_orden'])) ? $_POST['num_orden'] : '';
        $observaciones = (isset($_POST['observaciones'])) ? $_POST['observaciones'] : '';
        $id_prospecto = (isset($_POST['id_prospecto'])) ? $_POST['id_prospecto'] : '';
        $conexion = getConexion();
        $conexion->beginTransaction();
        try {

            // Insertar la orden en ordenes disponibles
            $insert_orden = "INSERT INTO siga_prospectos_ordenes_disponibles (num_orden, anio) VALUES (?, ?)";
            $stmt_insert_orden = $conexion->prepare($insert_orden);
            $stmt_insert_orden->execute([$num_orden, date('Y')]);

            // Actualizar estatus de la orden a cancelada (estatus = 2) y limpiar num_orden
            $consulta = "UPDATE siga_prospectos_ordenes SET estatus = 2, num_orden = NULL, observaciones = ?, fecha_cancelacion = CURDATE() WHERE id_orden = ?";
            $sentencia = $conexion->prepare($consulta);
            $sentencia->execute([$observaciones, $id_orden]);

            // Liberar el folio para que esté disponible (estatus = 0)
            $consulta_liberar_folio = "UPDATE siga_prospectos_folios_oficios SET estatus = 0 WHERE num_folio = ?";
            $sentencia_liberar_folio = $conexion->prepare($consulta_liberar_folio);
            $sentencia_liberar_folio->execute([$num_oficio]);

            // Actualizar estatus del prospecto a pendiente (estatus = 7) y colocar la observación de cancelada
            $consulta_prospecto = "UPDATE siga_prospectos SET estatus = 7, observaciones = ? WHERE id = ?";
            $sentencia_prospecto = $conexion->prepare($consulta_prospecto);
            $sentencia_prospecto->execute([$observaciones, $id_prospecto]);

            $conexion->commit();
            echo json_encode(['success' => true, 'message' => 'Orden cancelada correctamente.']);

        } catch (Exception $e) {
            $conexion->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error al cancelar la orden: ' . $e->getMessage()]);
        }
        $conexion = null;
        break;
}
?>