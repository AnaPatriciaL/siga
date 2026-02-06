<?php
ob_start();

include_once 'cors.php';
include_once 'conexion.php';

function getConexion() {
    $objeto = new Conexion();
    return $objeto->Conectar();
}

$conexion = getConexion();
$data = json_decode(file_get_contents("php://input"), true);

$opcion = isset($data['opcion']) ? (int)$data['opcion'] : 0;

switch ($opcion) {
    case 1: // ENVIAR ORDEN A EMITIDAS

        $prospecto_id = $data['prospecto_id'] ?? 0;
        if ($prospecto_id <= 0) {
            echo json_encode(['status' => false, 'msg' => 'ID de prospecto inválido']);
            exit;
        }
        // Obtener orden activa del prospecto
        $stmtOrden = $conexion->prepare("SELECT o.id_orden, o.num_orden, o.fecha_orden, o.anio, o.periodos FROM siga_prospectos_ordenes o WHERE o.id_prospecto = ? AND o.estatus = 1 ORDER BY o.id_orden DESC LIMIT 1");
        $stmtOrden->execute([$prospecto_id]);
        $orden = $stmtOrden->fetch(PDO::FETCH_ASSOC);

        if (!$orden) {
            echo json_encode(['status' => false,'msg' => 'El prospecto no tiene una orden activa']);
            exit;
        }   

        $stmtEmitida = $conexion->prepare("SELECT id FROM emitidas WHERE orden = ? AND anio = ?");
        $stmtEmitida->execute([$orden['num_orden'], $orden['anio']]);

        if ($stmtEmitida->fetch()) {
            echo json_encode(['status' => false,'msg' => 'La orden ya fue emitida']);
            exit;
        }
        $idOrden = $orden['id_orden'];
        $stmtPros = $conexion->prepare("SELECT estatus FROM siga_prospectos WHERE id = ?");
        $stmtPros->execute([$prospecto_id]);
        $pros = $stmtPros->fetch(PDO::FETCH_ASSOC);

        if (!$pros || (int)$pros['estatus'] !== 5) {
            echo json_encode(['status' => false,'msg' => 'El prospecto no está autorizado para emitir']);
            exit;
        }
        try {
            $conexion->beginTransaction();
            $sql = "INSERT INTO emitidas (orden, fecha_orden, anio, rfc, nombre, domicilio, oficina, periodo, impuestos, tipo, observaciones)
                SELECT o.num_orden, o.fecha_orden, o.anio, TRIM(p.rfc) AS rfc, p.nombre, CONCAT(IFNULL(p.calle, ''),
                              IF(p.num_exterior != '', CONCAT(', ', p.num_exterior), ''),
                              IF(p.num_interior != '', CONCAT(', ', p.num_interior), ''),
                              IF(p.colonia != '', CONCAT(', ', p.colonia), ''),
                              IF(p.cp != '', CONCAT(' C.P. ', p.cp), ''),
                              IF(p.localidad != '', CONCAT(', ', p.localidad), ''),
                              IF(m.nombre != '', CONCAT(', ', m.nombre), ''),CONCAT(', SINALOA')) AS domicilio_completo,
                    p.oficina_id, o.periodos, i.impuesto AS impuesto_id, 'E', 'Emitida desde SIGA'
                FROM siga_prospectos_ordenes o
                INNER JOIN siga_prospectos p ON p.id = o.id_prospecto
                INNER JOIN siga_prospectos_municipios m ON m.municipio_id = p.municipio_id
                INNER JOIN siga_prospectos_impuestos i ON i.id = p.impuesto_id
                WHERE o.id_orden = :id_orden";

            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':id_orden', $idOrden, PDO::PARAM_INT);
            $stmt->execute();

            // Actualizar estatus del prospecto
            $stmtUpd = $conexion->prepare("UPDATE siga_prospectos SET estatus = 6, fecha_emision = NOW() WHERE id = ?");
            $stmtUpd->execute([$prospecto_id]);
            $conexion->commit();
            echo json_encode(['status' => true,'msg' => 'Orden emitida correctamente']);

        } catch (Exception $e) {

            $conexion->rollBack();
            echo json_encode([
                'status' => false,
                'msg' => 'Error al emitir la orden'
            ]);
        }
        break;
    default:
        echo json_encode([
            'status' => false,
            'msg' => 'Opción no válida'
        ]);
        break;
}
