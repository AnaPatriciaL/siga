<?php
ob_start();

include_once 'cors.php';
include_once 'conexion.php';
require __DIR__ . '/vendor/autoload.php'; 
require_once __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';

\PhpOffice\PhpWord\Settings::setPdfRendererPath(__DIR__ . '/vendor/tecnickcom/tcpdf');
\PhpOffice\PhpWord\Settings::setPdfRendererName('TCPDF');
use PhpOffice\PhpWord\TemplateProcessor;

function getConexion() {
    $objeto = new Conexion();
    return $objeto->Conectar();
}
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return $protocol . $host . $path;
}

function fechaFrontendToDB(?string $fecha): string
{
    if (empty($fecha)) {
        return date('Y-m-d');
    }

    $dt = DateTime::createFromFormat('d/m/Y', $fecha);

    if ($dt === false) {
        return date('Y-m-d');
    }

    return $dt->format('Y-m-d');
}
function formatDateToSpanish($fecha_orden_str) {
    if ($fecha_orden_str) {
        try {
            $date = new DateTime($fecha_orden_str);
            $day = $date->format('d');
            $year = $date->format('Y');
            $month_num = $date->format('n');
            $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            $month_name = $months[$month_num - 1];
            return "$day de $month_name de $year";
        } catch (Exception $e) {
            return $fecha_orden_str;
        }
    }
    return '';
}
function getCatalogoOficinas(PDO $conexion): array
{
    $stmt = $conexion->prepare("SELECT id, nombre FROM siga_prospectos_oficinas");
    $stmt->execute();

    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[(int)$row['id']] = strtoupper($row['nombre']);
    }

    return $map;
}

$conexion = getConexion();
$data = json_decode(file_get_contents("php://input"), true);
$opcion = isset($data['opcion']) ? (int)$data['opcion'] : 0;



switch ($opcion) {
    case 1: // CONSULTAR AÑOS
        $consulta = "SELECT DISTINCT(o.anio) FROM siga_prospectos p INNER JOIN siga_prospectos_ordenes o ON o.id_prospecto = p.id 
        WHERE p.estatus = 6 AND o.estatus = 1 ORDER BY p.id DESC";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data=$resultado->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    case 2: // CONSULTAR REGISTROS
        $anio = isset($data['anio']) ? (int)$data['anio'] : 0;
        $mes = isset($data['mes']) ? (int)$data['mes'] : 0;

        if ($mes === 0) {
            $consulta = "SELECT COUNT(o.id_orden) AS total, p.oficina_id, p.impuesto_id, o.anio, of.nombre, pi.impuesto FROM siga_prospectos p 
            INNER JOIN siga_prospectos_ordenes o ON o.id_prospecto = p.id INNER JOIN siga_prospectos_oficinas of ON of.id = p.oficina_id 
            INNER JOIN siga_prospectos_impuestos pi ON pi.id = p.impuesto_id  WHERE p.estatus = 6 AND o.estatus = 1 AND o.anio = :anio
            GROUP BY p.oficina_id, p.impuesto_id ORDER BY p.oficina_id";
            $resultado = $conexion->prepare($consulta);
            $resultado->bindParam(':anio', $anio, PDO::PARAM_INT);
            $resultado->execute();
            $data=$resultado->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } else{
            $consulta = "SELECT COUNT(o.id_orden) AS total, p.oficina_id, p.impuesto_id, o.anio, of.nombre, pi.impuesto FROM siga_prospectos p 
            INNER JOIN siga_prospectos_ordenes o ON o.id_prospecto = p.id INNER JOIN siga_prospectos_oficinas of ON of.id = p.oficina_id 
            INNER JOIN siga_prospectos_impuestos pi ON pi.id = p.impuesto_id  WHERE p.estatus = 6 AND o.estatus = 1 AND o.anio = :anio AND MONTH(o.fecha_orden) = :mes 
            GROUP BY p.oficina_id, p.impuesto_id ORDER BY p.oficina_id";
            $resultado = $conexion->prepare($consulta);
            $resultado->bindParam(':anio', $anio, PDO::PARAM_INT);
            $resultado->bindParam(':mes', $mes, PDO::PARAM_INT);
            $resultado->execute();
            $data=$resultado->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        break;
    case 3: // CONSULTAR EMITIDAS PARA GRAFICA
        $consulta = "SELECT anio, MONTH(fecha_orden) AS mes, COUNT(orden) AS total FROM emitidas WHERE tipo = 'E' 
        AND (anio = YEAR(NOW()) OR anio = YEAR(NOW())-1) GROUP BY anio, MONTH(fecha_orden) ORDER BY anio DESC, MONTH(fecha_orden) ASC";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data=$resultado->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    case 4: // CONSULTAR DETALLE DE ORDENES POR MES
        $anio = isset($data['anio']) ? (int)$data['anio'] : 0;
        $mes = isset($data['mes']) ? (int)$data['mes'] : 0;

        $consulta = "SELECT orden AS num_orden, fecha_orden, rfc, nombre,periodo AS periodos FROM emitidas WHERE tipo = 'E' 
        AND anio = :anio AND MONTH(fecha_orden) = :mes ORDER BY fecha_orden ASC";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindParam(':anio', $anio, PDO::PARAM_INT);
        $resultado->bindParam(':mes', $mes, PDO::PARAM_INT);
        $resultado->execute();
        $data=$resultado->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    case 5: // CONSULTAR ORDENES POR PROGRAMADOR POR AÑO
        $anio = isset($data['anio']) ? (int)$data['anio'] : 0;

        $consulta = "SELECT pr.usuario,po.anio, count(*) AS total FROM siga_prospectos_ordenes po LEFT JOIN siga_prospectos p ON po.id_prospecto = p.id 
        LEFT JOIN siga_prospectos_programadores pr ON p.programador_id = pr.id WHERE po.estatus = 1 AND anio = :anio GROUP BY pr.id, po.anio ORDER BY total DESC";
        $resultado = $conexion->prepare($consulta);
        $resultado->bindParam(':anio', $anio, PDO::PARAM_INT);
        $resultado->execute();
        $data=$resultado->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    default:
        echo json_encode([
            'status' => false,
            'msg' => 'Opción no válida'
        ]);
        break;
}
