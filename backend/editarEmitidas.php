<?php
ob_start();

include_once 'cors.php';
include_once 'conexion.php';

function getConexion() {
    $objeto = new Conexion();
    return $objeto->Conectar();
}

function limpiarTexto($valor) {
    return strtoupper(trim($valor));
}

$conexion = getConexion();
$data = json_decode(file_get_contents("php://input"), true);
$anioActual = date('Y');
$opcion = isset($data['opcion']) ? (int)$data['opcion'] : 0;
$anio = isset($data['anio']) ? (int)$data['anio'] : $anioActual;
if ($anio != $anioActual) {
    echo json_encode([
        'status' => false,
        'msg' => 'Solo se permite consultar órdenes del año actual (' . $anioActual . ')'
    ]);
    exit;
}

switch ($opcion) {
    case 1: // MOSTRAR HISTORIAL EMITIDAS 
        $consulta = "SELECT e.id, e.orden AS num_orden, e.fecha_orden, e.anio, e.rfc, e.nombre, e.domicilio, spo.nombre AS oficina, e.periodo AS periodos, e.impuestos, sa.fecha AS seguimiento,
        IFNULL(scs.descripcion, 'Sin datos') AS estatus FROM emitidas e LEFT JOIN (SELECT orden_id, MAX(id) AS ultimo_id FROM sia_avances GROUP BY orden_id) ult ON ult.orden_id = e.id
        LEFT JOIN sia_avances sa ON sa.id = ult.ultimo_id LEFT JOIN sia_cat_seguimientos scs ON scs.cve = sa.seguimiento_cve
        LEFT JOIN siga_prospectos_oficinas spo ON spo.id = e.oficina WHERE e.tipo = 'E' AND e.anio = :anio ORDER BY e.fecha_orden DESC";
        $stmt = $conexion->prepare($consulta);
        $stmt->execute([':anio' => $anioActual]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    case 2: // HISTORIAL EMITIDAS FILTRADO POR ORDEN O RFC
        $orden = isset($data['orden']) ? trim($data['orden']) : '';
        $rfc   = isset($data['rfc']) ? trim($data['rfc']) : '';
        $where = [];
        $params = [];
        if ($orden !== '') {
            $where[] = "e.orden LIKE :orden";
            $params[':orden'] = "%$orden%";
        }
        if ($rfc !== '') {
            $where[] = "e.rfc LIKE :rfc";
            $params[':rfc'] = "%$rfc%";
        }
        // Si no hay filtros, no consultamos
        if (empty($where)) {
            echo json_encode([]);
            exit;
        }
        $consulta = "SELECT e.id, pr.id AS id_prospectos_siga, ord.id_orden  AS id_orden_siga, e.orden AS num_orden, e.fecha_orden, e.anio, e.rfc, e.nombre, e.domicilio, spo.nombre AS oficina, e.periodo AS periodos, e.impuestos,
                sa.fecha AS seguimiento, CONCAT(IFNULL(scs.descripcion, 'Sin datos'),CASE scs.estado WHEN 1 THEN '/Sin iniciar' WHEN 2 THEN '/En proceso'  WHEN 3 THEN '/Terminada' WHEN 4 THEN '/Cancelada' ELSE '' END) AS estatus FROM vi_emitidas e
            LEFT JOIN (SELECT orden_id, MAX(id) AS ultimo_id FROM sia_avances GROUP BY orden_id) ult ON ult.orden_id = e.id
            LEFT JOIN sia_avances sa ON sa.id = ult.ultimo_id
            LEFT JOIN sia_cat_seguimientos scs ON scs.cve = sa.seguimiento_cve
            LEFT JOIN siga_prospectos_oficinas spo ON spo.id = e.oficina
            LEFT JOIN siga_prospectos pr ON e.rfc = pr.rfc
            LEFT JOIN siga_prospectos_ordenes ord ON pr.id = ord.id_prospecto
            WHERE e.tipo = 'E' AND e.anio = :anio AND " . implode(" AND ", $where) . " ORDER BY e.fecha_orden DESC";

        $params[':anio'] = $anioActual;
        $stmt = $conexion->prepare($consulta);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
        exit;
    case 3: // OBTENER DETALLES DE ORDEN EMITIDA
        $id = $data['id'] ?? null;
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
                    LEFT JOIN siga_prospectos_ordenes AS o ON o.id_prospecto = a.id WHERE a.id = ?";
        $stmt = $conexion->prepare($consulta);
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    case 4: // ACTUALIZAR ORDEN EMITIDA
        try {$conexion->beginTransaction();
            $id = $data['id'] ?? null;
            $id_orden = $data['id_orden'] ?? null;
            $rfc = limpiarTexto($data['rfc'] ?? '');
            $nombre = limpiarTexto($data['nombre'] ?? '');
            $calle = limpiarTexto($data['calle'] ?? '');
            $num_exterior = limpiarTexto($data['num_exterior'] ?? '');
            $num_interior = limpiarTexto($data['num_interior'] ?? '');
            $colonia = limpiarTexto($data['colonia'] ?? '');
            $cp = trim($data['cp'] ?? '');
            $localidad = limpiarTexto($data['localidad'] ?? '');
            $municipio_id = $data['municipio_id'] ?? '';
            $oficina_id = $data['oficina_id'] ?? '';
            $fuente_id = $data['fuente_id'] ?? '';
            $giro = limpiarTexto($data['giro'] ?? '');
            $periodos = $data['periodos'] ?? '';
            $antecedente_id = $data['antecedente_id'] ?? '';
            $impuesto_id = $data['impuesto_id'] ?? '';
            $determinado = $data['determinado'] ?? '';
            $programador_id = $data['programador_id'] ?? '';
            $representante_legal = limpiarTexto($data['representante_legal'] ?? '');
            $retenedor = $data['retenedor'] ?? '';
            $origen_id = $data['origen_id'] ?? '';
            $observaciones = limpiarTexto($data['observaciones'] ?? '');
            $estatus = $data['estatus'] ?? '';
            $cambio_domicilio = $data['cambio_domicilio'] ?? '';
            $domicilio_anterior = limpiarTexto($data['domicilio_anterior'] ?? '');
            $notificador = limpiarTexto($data['notificador'] ?? '');
            $fecha_acta = $data['fecha_acta'] ?? null;
            $fecha_orden = $data['fecha_orden'] ?? null;
            if (empty($fecha_orden)) {
                echo json_encode([
                    'status' => false,
                    'msg' => 'Fecha de orden requerida'
                ]);
                exit;
            }
            if (!empty($fecha_orden)) {
                $dateObj = DateTime::createFromFormat('d/m/Y', $fecha_orden);
                if (!$dateObj) {
                    $dateObj = DateTime::createFromFormat('Y-m-d', $fecha_orden);
                }
                if ($dateObj) {
                    $fecha_orden = $dateObj->format('Y-m-d');
                }
            }
            if (!empty($fecha_acta)) {
                $dateObj = DateTime::createFromFormat('d/m/Y', $fecha_acta);
                if ($dateObj) {
                    $fecha_acta = $dateObj->format('Y-m-d');
                }
            }
            if (!$id || !$id_orden) {
                echo json_encode([
                    'status' => false,
                    'msg' => 'Faltan identificadores (id o id_orden)'
                ]);
                exit;
            }
            $sql1 = "UPDATE siga_prospectos SET
                rfc = ?, nombre = ?, calle = ?, num_exterior = ?, num_interior = ?, colonia = ?, cp = ?, localidad = ?, municipio_id = ?, oficina_id = ?, 
                fuente_id = ?, giro = ?, periodos = ?, antecedente_id = ?, impuesto_id = ?, determinado = ?, programador_id = ?, representante_legal = ?, 
                retenedor = ?, origen_id = ?, observaciones = ?, estatus = ?, cambio_domicilio = ?, domicilio_anterior = ?, notificador = ?, fecha_acta = ?
            WHERE id = ?";

            $stmt1 = $conexion->prepare($sql1);
            $stmt1->execute([
                $rfc, $nombre, $calle, $num_exterior, $num_interior, $colonia, $cp, $localidad, $municipio_id, $oficina_id,
                $fuente_id, $giro, $periodos, $antecedente_id, $impuesto_id, $determinado, $programador_id, $representante_legal,
                $retenedor, $origen_id, $observaciones, $estatus, $cambio_domicilio, $domicilio_anterior, $notificador, $fecha_acta, $id
            ]);
            $sql2 = "UPDATE siga_prospectos_ordenes SET
                fecha_orden = ?,
                periodos = ?,
                cambio_domicilio = ?,
                domicilio_anterior = ?,
                notificador = ?,
                fecha_acta = ?,
                envio_fiscaweb = 2
            WHERE id_orden = ?";

            $stmt2 = $conexion->prepare($sql2);
            $stmt2->execute([$fecha_orden, $periodos, $cambio_domicilio, $domicilio_anterior, $notificador, $fecha_acta, $id_orden]);
            $sql3 = "UPDATE emitidas e
                INNER JOIN siga_prospectos_ordenes o ON e.orden = o.num_orden AND e.anio = o.anio
                INNER JOIN siga_prospectos p ON p.id = o.id_prospecto
                INNER JOIN siga_prospectos_municipios m ON m.municipio_id = p.municipio_id
                INNER JOIN siga_prospectos_impuestos i ON i.id = p.impuesto_id
                SET e.fecha_orden = o.fecha_orden,
                    e.rfc = UPPER(TRIM(p.rfc)),
                    e.nombre = UPPER(TRIM(p.nombre)),
                    e.domicilio = CONCAT(
                        IFNULL(p.calle, ''),
                        IF(p.num_exterior != '', CONCAT(', ', p.num_exterior), ''),
                        IF(p.num_interior != '', CONCAT(', ', p.num_interior), ''),
                        IF(p.colonia != '', CONCAT(', ', p.colonia), ''),
                        IF(p.cp != '', CONCAT(' C.P. ', p.cp), ''),
                        IF(p.localidad != '', CONCAT(', ', p.localidad), ''),
                        IF(m.nombre != '', CONCAT(', ', m.nombre), ''),
                        ', SINALOA'
                    ),
                    e.oficina = p.oficina_id,
                    e.periodo = o.periodos,
                    e.impuestos = i.impuesto,
                    e.tipo = 'E',
                    e.observaciones = 'Emitida desde SIGA'
            WHERE o.id_orden = ? AND e.tipo = 'E' AND e.anio = ?";
            $stmt3 = $conexion->prepare($sql3);
            $stmt3->execute([$id_orden, $anioActual]);
            $conexion->commit();
            echo json_encode([
                'status' => true,
                'msg' => 'Actualizado correctamente'
            ]);

        } catch (Exception $e) {
            $conexion->rollBack();

            echo json_encode([
                'status' => false,
                'msg' => $e->getMessage()
            ]);
        }

        exit;
    default:
        echo json_encode([
            'status' => false,
            'msg' => 'Opción no válida'
        ]);
        break;
}
