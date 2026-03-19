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
$data = json_decode(file_get_contents("php://input"), true);
$opcion = isset($data['opcion']) ? (int)$data['opcion'] : 0;



switch ($opcion) {
    case 1: // CONSULTAR INFORMACION PERSONAL
        $nombre = isset($data['nombre']) ? trim($data['nombre']) : '';
        $rfc   = isset($data['rfc']) ? trim($data['rfc']) : '';
        $where = [];
        $params = [];
        if ($nombre !== '') {
            $where[] = "p.nombre LIKE :nombre";
            $params[':nombre'] = "%$nombre%";
        }
        if ($rfc !== '') {
            $where[] = "p.rfc LIKE :rfc";
            $params[':rfc'] = "%$rfc%";
        }
        if (!$nombre && !$rfc) {
            echo json_encode(['status' => false, 'msg' => 'Debe proporcionar un nombre o un RFC para buscar.']);
            break;
        }
        if (empty($where)) {
            echo json_encode([]);
            exit;
        }
        $consulta = "SELECT p.rfc, p.nombre, CONCAT(IFNULL(p.calle, ''), IF(p.num_exterior <> '', CONCAT(', ', p.num_exterior), ''),
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
            END,', SINALOA') AS domicilio FROM siga_prospectos p 
            LEFT JOIN siga_prospectos_oficinas of ON p.oficina_id = of.id
            LEFT JOIN siga_prospectos_municipios m ON p.municipio_id = m.municipio_id
            LEFT JOIN siga_prospectos_programadores pr ON p.programador_id = pr.id " . (!empty($where) ? " WHERE " . implode(" OR ", $where) : "") . " ORDER BY p.id";
        $stmt = $conexion->prepare($consulta);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
        exit;
    default:
        echo json_encode([
            'status' => false,
            'msg' => 'Opción no válida'
        ]);
        break;
}
