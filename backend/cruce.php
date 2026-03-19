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
    case 1: // HISTORIAL EMITIDAS
        $consulta = "SELECT e.id, e.orden AS num_orden, e.fecha_orden, e.rfc, e.nombre, e.periodo AS periodos, e.impuestos,
                sa.fecha AS seguimiento, CONCAT(IFNULL(scs.descripcion, 'Sin datos'),CASE scs.estado
            WHEN 1 THEN '/Sin iniciar' WHEN 2 THEN '/En proceso'  WHEN 3 THEN '/Terminada' WHEN 4 THEN '/Cancelada' ELSE '' END) AS estatus FROM vi_emitidas e
            LEFT JOIN (SELECT orden_id, MAX(id) AS ultimo_id FROM sia_avances GROUP BY orden_id) ult ON ult.orden_id = e.id
            LEFT JOIN sia_avances sa ON sa.id = ult.ultimo_id
            LEFT JOIN sia_cat_seguimientos scs ON scs.cve = sa.seguimiento_cve
            LEFT JOIN siga_prospectos_oficinas spo ON spo.id = e.oficina
            WHERE e.tipo = 'E' ORDER BY e.rfc,e.fecha_orden DESC";
        $stmt = $conexion->prepare($consulta);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
        exit;
    default:
        echo json_encode([
            'status' => false,
            'msg' => 'Opción no válida'
        ]);
        break;
}
