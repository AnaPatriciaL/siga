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
    case 1: // AGREGAR POA
        $anio  = isset($data['anio']) ? (int)$data['anio'] : 0;
        $metas = isset($data['metas']) ? $data['metas'] : [];

        if (!$anio || !is_array($metas) || count($metas) !== 12) {
            echo json_encode(['status' => false, 'msg' => 'Información incompleta para guardar el POA.']);
            break;
        }
        try {
            $check = $conexion->prepare("SELECT COUNT(*) FROM poa_metas WHERE anio = :anio");
            $check->bindParam(':anio', $anio, PDO::PARAM_INT);
            $check->execute();

            if ($check->fetchColumn() > 0) {
                echo json_encode(['status' => false, 'msg' => "El Programa Operativo Anual del año $anio ya está capturado."]);
                break;
            }
            $conexion->beginTransaction();
            $insert = $conexion->prepare("INSERT INTO poa_metas (anio, mes, meta, fecha_alta) VALUES (:anio, :mes, :meta, NOW())");

            foreach ($metas as $m) {
                $mes  = (int)($m['mes'] ?? 0);
                $meta = (int)($m['meta'] ?? 0);

                if ($mes < 1 || $mes > 12) {
                    throw new Exception("Mes inválido: $mes");
                }

                $insert->execute([
                    ':anio' => $anio,
                    ':mes'  => $mes,
                    ':meta' => $meta
                ]);
            }

            $conexion->commit();

            echo json_encode([
                'status' => true,
                'msg' => "POA del año $anio guardado correctamente."
            ]);

        } catch (Exception $e) {

            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            echo json_encode([
                'status' => false,
                'msg' => 'Error al guardar el POA.',
                'error' => $e->getMessage()
            ]);
        }
        break;
    case 2: // VALIDAR SI EXISTE POA DEL AÑO
        $anio = isset($data['anio']) ? (int)$data['anio'] : 0;
        if (!$anio) {echo json_encode([
                'status' => false,
                'existe' => false,
                'msg' => 'Año no válido.']);
            break;
        }
        try {
            $consulta = $conexion->prepare("SELECT COUNT(*) FROM poa_metas WHERE anio = :anio");
            $consulta->bindParam(':anio', $anio, PDO::PARAM_INT);
            $consulta->execute();
            $existe = $consulta->fetchColumn() > 0;
            echo json_encode(['status' => true,
                'existe' => $existe,
                'msg' => $existe ? "El POA del año $anio ya está capturado." : "El año $anio está disponible para captura."]);

        } catch (Exception $e) {
            echo json_encode(['status' => false,
                'existe' => false,
                'msg' => 'Error al validar el año.',
                'error' => $e->getMessage()
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
