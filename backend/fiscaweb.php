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

$conexion = getConexion();
$checkHistorial = $conexion->prepare("SELECT COUNT(*) FROM siga_historial_fiscaweb WHERE num_orden = :num_orden AND YEAR(fecha_orden) = :anio");
$insertHistorial = $conexion->prepare("INSERT INTO siga_historial_fiscaweb (num_orden, fecha_orden, fecha_envio, rfc, modificado_cancelado) VALUES (:num_orden, :fecha_orden, NOW(), :rfc, :modificado_cancelado)");
$updateEnvio = $conexion->prepare("UPDATE siga_prospectos_ordenes SET envio_fiscaweb = :nuevo_estatus WHERE num_orden = :num_orden AND anio = :anio");
$data = json_decode(file_get_contents("php://input"), true);
$opcion = isset($data['opcion']) ? (int)$data['opcion'] : 0;

switch ($opcion) {
    case 1: // CONSULTAR 
        $conexion->beginTransaction();

        try {
            $consulta = "SELECT o.num_orden AS ORDEN, o.num_orden AS EXPEDIENTE, ' ' AS OFICIO, DATE_FORMAT(o.fecha_orden, '%d/%m/%Y') AS FECORDEN, o.fecha_orden AS FECORDEN_DB, '' AS NUMOFICIO, p.rfc AS RFC, p.nombre AS NOMBRE, CONCAT(IFNULL(p.calle, ''), IF(p.num_exterior <> '', CONCAT(', ', p.num_exterior), ''),
            IF(p.num_interior <> '', CONCAT(', ', p.num_interior), ''), IF(p.colonia <> '', CONCAT(', ', p.colonia), ''), IF(p.cp <> '', CONCAT(' C.P. ', p.cp), ''),
            CASE
                WHEN p.localidad <> ''
                    AND m.nombre <> ''
                    AND TRIM(p.localidad) = TRIM(m.nombre)
                THEN CONCAT(', ', m.nombre)
                WHEN p.localidad <> ''
                    AND m.nombre <> ''
                THEN CONCAT(', ', p.localidad, ', ', m.nombre)
                WHEN p.localidad <> ''
                THEN CONCAT(', ', p.localidad)
                WHEN m.nombre <> ''
                THEN CONCAT(', ', m.nombre)
                ELSE ''
            END,', SINALOA') AS DIRECCION, of.nombre AS OFICINA_OFICINA, 'LOCAL' AS FUENTE_FUENTE,'OTRO' AS SUBPROG_SUBPROG, o.anio AS EJERCICIO_EJERCICIO, m.nombre AS MUNICIPIO_MUNICIPIO, met.nombre AS METODO_METODO, jefes.nombre AS JEFEDEPTO_RFC,'' AS PRESUNTIVA, STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(o.periodos, '-', 1), ',', 1), '%d/%m/%Y') AS fecha_inicio,
            STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(o.periodos, '-', -1), ',', -1), '%d/%m/%Y') AS fecha_fin,
            CASE
            WHEN MONTH(STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(o.periodos, '-', 1), ',', 1), '%d/%m/%Y')) = 12 THEN 'DIC'
            ELSE CONCAT(ELT(MONTH(
                        STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(o.periodos, '-', 1), ',', 1), '%d/%m/%Y')), 'ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'),'-DIC')
            END AS PER1,
            YEAR(STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(o.periodos, '-', 1), ',', 1), '%d/%m/%Y')) AS EJE1_EJERCICIO,
            CASE
                WHEN YEAR(
                    STR_TO_DATE(
                        SUBSTRING_INDEX(SUBSTRING_INDEX(o.periodos, '-', 1), ',', 1),'%d/%m/%Y')) <> YEAR(STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(o.periodos, '-', -1), ',', -1), '%d/%m/%Y'))
                THEN
                    CASE
                        WHEN MONTH(STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(o.periodos, '-', -1), ',', -1), '%d/%m/%Y')) = 1 THEN 'ENE'
                        ELSE CONCAT('ENE-', ELT(
                                MONTH(
                                    STR_TO_DATE(
                                        SUBSTRING_INDEX(SUBSTRING_INDEX(o.periodos, '-', -1), ',', -1),'%d/%m/%Y')),'ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'))
                    END
                ELSE ''
            END AS PER2,
            CASE
                WHEN YEAR(STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(o.periodos, '-', 1), ',', 1),'%d/%m/%Y')) <> YEAR(STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(o.periodos, '-', -1), ',', -1),'%d/%m/%Y'))
                THEN YEAR(STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(o.periodos, '-', -1), ',', -1),'%d/%m/%Y'))
                ELSE ''
            END AS EJE2_EJERCICIO,UPPER(pr.nombre_completo) AS PROGRAMADOR_RFC, '' AS PORTALUSER, '' AS FECALTASIS, '' AS ESTATUS_ESTATUS,o.periodos AS PERIODO, o.envio_fiscaweb AS ENVIO_FISCAWEB
            FROM siga_prospectos_ordenes o LEFT JOIN siga_prospectos p ON p.id = o.id_prospecto 
            LEFT JOIN siga_prospectos_oficinas of ON p.oficina_id = of.id
            LEFT JOIN siga_prospectos_municipios m ON p.municipio_id = m.municipio_id
            LEFT JOIN siga_prospectos_programadores pr ON p.programador_id = pr.id
            LEFT JOIN siga_metodos_fiscaweb met ON p.impuesto_id = met.impuesto_id
            LEFT JOIN siga_jefesdepto_fiscaweb jefes ON p.oficina_id = jefes.oficina_id
            WHERE o.estatus = 1 AND (o.envio_fiscaweb = 0 OR ISNULL(o.envio_fiscaweb) OR o.envio_fiscaweb = 2) ORDER BY o.num_orden";
            $resultado = $conexion->prepare($consulta);
            $resultado->execute();
            $data=$resultado->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as &$row) {
                $numOrden = $row['ORDEN'];
                $fechaOrden = $row['FECORDEN_DB'];
                $rfc = $row['RFC'];
                $anio = $row['EJERCICIO_EJERCICIO'];
                $checkHistorial->execute([':num_orden' => $numOrden, ':anio' => $anio]);
                $existe = $checkHistorial->fetchColumn();
                $modificado = ($existe > 0) ? 1 : 0;
                $row['MODIFICADO'] = $modificado;
                $insertHistorial->execute([':num_orden' => $numOrden,':fecha_orden' => $fechaOrden,':rfc' => $rfc,':modificado_cancelado' => $modificado]); 
                $estatusActual = $row['ENVIO_FISCAWEB']; 
                if (is_null($estatusActual) || $estatusActual == 0) { 
                    $nuevoEstatus = 1; 
                } elseif ($estatusActual == 2) { 
                    $nuevoEstatus = 3; 
                } else { continue; } 
                $updateEnvio->execute([':nuevo_estatus' => $nuevoEstatus, ':num_orden' => $numOrden, ':anio' => $anio]); 
            }
            unset($row);
            $conexion->commit();
            echo json_encode($data, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            $conexion->rollBack();
            throw $e;
        }
        break;
    default:
        echo json_encode([
            'status' => false,
            'msg' => 'Opción no válida'
        ]);
        break;
}
