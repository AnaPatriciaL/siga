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
    case 2: // MOSTRAR EMITIDAS
        $anio = isset($data['anio']) ? (int)$data['anio'] : 0;
        $consulta = "SELECT
                  a.*,
                  b.impuesto AS impuesto,
                  b.descripcion AS impuestos_descripcion,
                  d.usuario AS programador_descripcion,
                  d.nombre_completo AS programador_nombre_completo,
                  e.nombre AS oficina_descripcion,
                  g.nombre AS fuente_descripcion,
                  h.nombre AS estatus_descripcion,
                  j.nombre AS municipio_nombre,
                  CONCAT( 
                    IFNULL(a.calle, ''),
                    IF(a.num_exterior != '', CONCAT(' No. ', a.num_exterior), ''),
                    IF(a.num_interior != '', CONCAT(' INTERIOR ', a.num_interior), ''),
                    IF(a.colonia != '', CONCAT(', ', a.colonia), ''),
                    IF(a.cp != '', CONCAT(' C.P. ', a.cp), ''),
                    IF(
                        a.localidad != '' 
                        AND (j.nombre IS NULL OR TRIM(a.localidad) != TRIM(j.nombre)),
                        CONCAT(' ', a.localidad, ', '),
                        ''
                    ),
                    IF(j.nombre != '', CONCAT(' ', j.nombre, ', '), ''),
                    ' SINALOA'
                  ) AS domicilio_completo,
                  CONCAT(
                      IFNULL(a.calle, ''),
                      IF(a.num_exterior != '', CONCAT(' No. ', a.num_exterior), ''),
                      IF(a.num_interior != '', CONCAT(', INTERIOR ', a.num_interior), '')
                  ) AS calle_numero,
                  CONCAT(
                      IF(a.cp != '', CONCAT('C.P. ', a.cp, ' '), ''),
                      IF(a.colonia != '', CONCAT(' ', a.colonia), ''),
                      IFNULL(a.localidad, '')
                  ) AS cp_municipio,
                  CONCAT(
                      IF(a.localidad != '', CONCAT(a.localidad), ''),
                      IF(j.nombre != '', CONCAT(', ', j.nombre), ''),
                      ' SINALOA'
                  ) AS ciudad_estado,
                  CONCAT(
                      IF(
                        a.localidad != '' 
                        AND (j.nombre IS NULL OR TRIM(a.localidad) != TRIM(j.nombre)),
                        CONCAT(' ', a.localidad, ', '),
                        ''
                    ),
                    IF(j.nombre != '', CONCAT(' ', j.nombre, ', '), ''),
                    ' SINALOA'
                  ) AS municipio_estado,
                  f.descripcion AS antecedente_descripcion,
                  o.num_oficio,
                  o.num_orden,
                  CONCAT(o.num_orden, '/', RIGHT(o.anio, 2)) AS orden_anio,
                  o.fecha_orden
                FROM siga_prospectos AS a
                LEFT JOIN siga_prospectos_impuestos AS b ON a.impuesto_id = b.id
                LEFT JOIN siga_prospectos_programadores AS d ON a.programador_id = d.id
                LEFT JOIN siga_prospectos_oficinas AS e ON a.oficina_id = e.id
                LEFT JOIN siga_prospectos_antecedentes AS f ON a.antecedente_id = f.id
                LEFT JOIN siga_prospectos_fuentes AS g ON a.fuente_id = g.id
                LEFT JOIN siga_prospectos_estatus_prospectos AS h ON a.estatus = h.id
                LEFT JOIN siga_prospectos_municipios AS j ON a.municipio_id = j.municipio_id
                LEFT JOIN siga_prospectos_ordenes AS o ON o.id_prospecto = a.id
                WHERE a.estatus = 6 AND YEAR(o.fecha_orden) = :anio";

        $stmt = $conexion->prepare($consulta);
        $stmt->bindParam(':anio', $anio, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($data);
        exit;
    default:
        echo json_encode([
            'status' => false,
            'msg' => 'Opción no válida'
        ]);
        break;
}
