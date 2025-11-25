<?php
ob_start(); // Inicia el buffer de salida para capturar cualquier salida no deseada.

include_once 'cors.php';
include_once 'conexion.php';
require __DIR__ . '/vendor/autoload.php'; // Esta es la ruta correcta una vez instalado Composer en la carpeta backend.
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

function imprimirPDF($rutaPDF) {
    $sumatra = "C:\\Program Files\\SumatraPDF\\SumatraPDF.exe";
    $logFile = __DIR__ . "/impresion.log";
    file_put_contents($logFile,
        "------ INICIO DE PRUEBA ------\n" .
        date("Y-m-d H:i:s") . "\n" .
        "PDF recibido: $rutaPDF\n",
        FILE_APPEND
    );
    if (!file_exists($sumatra)) {
        file_put_contents($logFile, "Sumatra NO encontrado en: $sumatra\n\n", FILE_APPEND);
        return false;
    }
    $cmd = "\"$sumatra\" -print-to-default -silent \"$rutaPDF\"";
    file_put_contents($logFile, "Comando ejecutado:\n$cmd\n", FILE_APPEND);
    exec($cmd . " 2>&1", $salida, $resultado);
    if ($resultado === 0) {
        $impreso = true;
    }
    file_put_contents($logFile,
        "Salida:\n" . implode("\n", $salida) . "\nResultado: $resultado\n\n",
        FILE_APPEND
    );
    return $resultado === 0;
}

function getProspectoData($conexion, $prospecto_id) {
    $consulta = "SELECT
                          a.*, b.impuesto AS impuesto, b.descripcion AS impuestos_descripcion,
                          d.usuario AS programador_descripcion, d.nombre_completo AS programador_nombre_completo,
                          e.nombre AS oficina_descripcion, e.grupo AS oficina_grupo, e.fraccion AS oficina_fraccion,
                          e.domicilio AS oficina_domicilio, e.telefono AS oficina_telefono, g.nombre AS fuente_descripcion,
                          h.nombre AS estatus_descripcion, j.nombre AS municipio_nombre, k.art_retenedor, k.sujeto_retenedor,
                          CONCAT(IFNULL(a.calle, ''),
                              IF(a.num_exterior != '', CONCAT(', ', a.num_exterior), ''),
                              IF(a.num_interior != '', CONCAT(', ', a.num_interior), ''),
                              IF(a.colonia != '', CONCAT(', ', a.colonia), ''),
                              IF(a.cp != '', CONCAT(' C.P. ', a.cp), ''),
                              IF(a.localidad != '', CONCAT(', ', a.localidad), '')) AS domicilio_completo,
                          CONCAT(IFNULL(a.calle, ''),
                              IF(a.num_exterior != '', CONCAT(', ', a.num_exterior), ''),
                              IF(a.num_interior != '', CONCAT(', ', a.num_interior), '')) AS calle_numero,
                          CONCAT(IF(a.localidad != '', CONCAT( a.localidad), ''),
                              IF(j.nombre != '', CONCAT(', ', j.nombre), ''),
                              ' SINALOA') AS ciudad_estado,
                          f.descripcion AS antecedente_descripcion
                        FROM siga_prospectosie AS a
                        LEFT JOIN siga_prospectosie_impuestos AS b ON a.impuesto_id = b.id
                        LEFT JOIN siga_prospectosie_programadores AS d ON a.programador_id = d.id
                        LEFT JOIN siga_prospectosie_oficinas AS e ON a.oficina_id = e.id
                        LEFT JOIN siga_prospectosie_antecedentes AS f ON a.antecedente_id = f.id
                        LEFT JOIN siga_prospectosie_fuentes AS g ON a.fuente_id = g.id
                        LEFT JOIN siga_prospectosie_estatus_prospectos AS h ON a.estatus = h.id
                        LEFT JOIN siga_prospectosie_municipios AS j ON a.municipio_id = j.municipio_id 
                        LEFT JOIN siga_prospectosie_retenedores AS k ON a.retenedor = k.id_retenedor
                        WHERE a.id = ?";
    $stmt = $conexion->prepare($consulta);
    $stmt->execute([$prospecto_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getImpuestoInfo($conexion, $impuesto_id) {
    $sql = "SELECT impuesto FROM siga_prospectosie_impuestos WHERE id = ? LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$impuesto_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    return [
        'template_file' => $row['impuesto'] . ".docx",   // Ej: ISR.docx
        'prefix'        => $row['impuesto'] . "_"        // Ej: ISR_
    ];
}

function getFolioOrCreate(PDO $conexion, $prospecto_id, $create = false, $id_generador = null) {
    // Verificar orden existente
    $stmt_orden = $conexion->prepare("SELECT * FROM siga_prospectosie_ordenes WHERE id_prospecto = ?");
    $stmt_orden->execute([$prospecto_id]);
    $orden_existente = $stmt_orden->fetch(PDO::FETCH_ASSOC);
    if ($orden_existente) {
        $stmt_folio = $conexion->prepare("SELECT * FROM siga_prospectosie_folios_oficios WHERE num_folio = ?");
        $stmt_folio->execute([$orden_existente['num_oficio']]);
        $folio = $stmt_folio->fetch(PDO::FETCH_ASSOC);
        return $folio ?: ['num_folio' => $orden_existente['num_oficio'], 'anio' => date('Y')];
    }
    if (!$create) {
        return ['num_folio' => 'XXXX', 'anio' => date('Y')];
    }
    $stmt_folio_nuevo = $conexion->prepare("SELECT * FROM siga_prospectosie_folios_oficios WHERE estatus = 0 ORDER BY num_folio ASC LIMIT 1 FOR UPDATE");
    $stmt_folio_nuevo->execute();
    $folio_oficio = $stmt_folio_nuevo->fetch(PDO::FETCH_ASSOC);
    if (!$folio_oficio) {
        throw new Exception("No hay folios de oficio disponibles.");
    }
    $stmt_prosp = $conexion->prepare("SELECT oficina_id, programador_id, retenedor FROM siga_prospectosie WHERE id = ?");
    $stmt_prosp->execute([$prospecto_id]);
    $prospectoRow = $stmt_prosp->fetch(PDO::FETCH_ASSOC);
    if (!$prospectoRow) {
        throw new Exception("No se encontró el prospecto con id $prospecto_id");
    }
    $oficina_id = $prospectoRow['oficina_id'] ?? null;
    $programador_id = $prospectoRow['programador_id'] ?? null;
    $retenedor_id = $prospectoRow['retenedor'] ?? null; // referencia a siga_prospectosie_retenedores.id_retenedor
    $oficina_grupo = null;
    $oficina_fraccion = null;
    if ($oficina_id) {
        $stmt_oficina = $conexion->prepare("SELECT grupo AS oficina_grupo, fraccion AS oficina_fraccion FROM siga_prospectosie_oficinas WHERE id = ?");
        $stmt_oficina->execute([$oficina_id]);
        $oficinaRow = $stmt_oficina->fetch(PDO::FETCH_ASSOC);
        if ($oficinaRow) {
            $oficina_grupo = $oficinaRow['oficina_grupo'] ?? null;
            $oficina_fraccion = $oficinaRow['oficina_fraccion'] ?? null;
        }
    }
    $art_retenedor = null;
    $sujeto_retenedor = null;
    if ($retenedor_id) {
        $stmt_reten = $conexion->prepare("SELECT art_retenedor, sujeto_retenedor FROM siga_prospectosie_retenedores WHERE id_retenedor = ?");
        $stmt_reten->execute([$retenedor_id]);
        $retenRow = $stmt_reten->fetch(PDO::FETCH_ASSOC);
        if ($retenRow) {
            $art_retenedor = $retenRow['art_retenedor'] ?? null;
            $sujeto_retenedor = $retenRow['sujeto_retenedor'] ?? null;
        }
    }
    $update_folio = "UPDATE siga_prospectosie_folios_oficios SET estatus = 1 WHERE id = ?";
    $stmt_update = $conexion->prepare($update_folio);
    $stmt_update->execute([$folio_oficio['id']]);
    $insert_orden = "INSERT INTO siga_prospectosie_ordenes 
        (id_prospecto, num_oficio, num_orden, grupo, fraccion, id_programador, id_generador, fecha_orden, estatus, art_retenedor, sujeto_retenedor)
        VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?)";
    $stmt_insert_orden = $conexion->prepare($insert_orden);
    $stmt_insert_orden->execute([$prospecto_id, $folio_oficio['num_folio'], $folio_oficio['num_folio'],           // num_orden = num_folio (si esa es la regla)
        $oficina_grupo, $oficina_fraccion, $programador_id,                      // viene de siga_prospectosie.programador_id
        $id_generador, 1, // estatus inicial 'Generada'
        $art_retenedor, $sujeto_retenedor
    ]);
    return $folio_oficio;
}

function getFirmas(PDO $conexion) {
    $consulta_personal = "SELECT id_actuante, nombre AS nombre_actuante, cargo, iniciales, estatus FROM siga_prospectosie_personal_actuante";
    $stmt_personal = $conexion->prepare($consulta_personal);
    $stmt_personal->execute();
    $personal_actuante = $stmt_personal->fetchAll(PDO::FETCH_ASSOC);
    $iniciales = [];
    $cargo = '';
    $nombre_actuante = '';
    foreach ($personal_actuante as $persona) {
        if (intval($persona['estatus']) === 1) {
            if (intval($persona['id_actuante']) === 1) {
                $cargo = $persona['cargo'];
                $nombre_actuante = $persona['nombre_actuante'];
            } else {
                $iniciales[] = $persona['iniciales'];
            }
        }
    }
    return [
    'cargo' => $cargo,
    'nombre_actuante' => $nombre_actuante,
    'iniciales' => implode(' / ', $iniciales)
    ];
}

function fillTemplateFromData(array $prospecto, array $folio, array $firmas) {
    $templateProcessor = new TemplateProcessor(__DIR__ . '/formatos/ISN.docx');
    $templateProcessor->setValue('num_folio', $folio['num_folio'] ?? 'XXXX');
    $templateProcessor->setValue('anio', $folio['anio'] ?? date('Y'));
    $templateProcessor->setValue('representante_legal', $prospecto['representante_legal'] ?? '');
    $templateProcessor->setValue('rfc', $prospecto['rfc'] ?? '');
    $templateProcessor->setValue('nombre', $prospecto['nombre'] ?? '');
    $templateProcessor->setValue('domicilio_completo', $prospecto['domicilio_completo'] ?? '');
    $templateProcessor->setValue('calle_numero', $prospecto['calle_numero'] ?? '');
    $templateProcessor->setValue('colonia', $prospecto['colonia'] ?? '');
    $templateProcessor->setValue('ciudad_estado', $prospecto['ciudad_estado'] ?? '');
    $templateProcessor->setValue('periodos', $prospecto['periodos'] ?? '');
    $templateProcessor->setValue('impuesto', $prospecto['impuesto'] ?? '');
    $templateProcessor->setValue('determinado', isset($prospecto['determinado']) ? number_format($prospecto['determinado'], 2) : '0.00');
    $templateProcessor->setValue('oficina_descripcion', $prospecto['oficina_descripcion'] ?? '');
    $templateProcessor->setValue('oficina_grupo', $prospecto['oficina_grupo'] ?? '');
    $templateProcessor->setValue('oficina_fraccion', $prospecto['oficina_fraccion'] ?? '');
    $templateProcessor->setValue('oficina_domicilio', $prospecto['oficina_domicilio'] ?? '');
    $templateProcessor->setValue('oficina_telefono', $prospecto['oficina_telefono'] ?? '');
    $templateProcessor->setValue('art_retenedor', $prospecto['art_retenedor'] ?? '');
    $templateProcessor->setValue('sujeto_retenedor', $prospecto['sujeto_retenedor'] ?? '');
    $templateProcessor->setValue('cargo', $firmas['cargo'] ?? '');
    $templateProcessor->setValue('nombre_actuante', $firmas['nombre_actuante'] ?? '');
    $templateProcessor->setValue('iniciales', $firmas['iniciales'] ?? '');
    return $templateProcessor;
}

function convertDocxToPdf($docxPath, $outputDir)
{
    // Validar que exista el archivo origen
    if (!file_exists($docxPath)) {
        error_log("convertDocxToPdf: El archivo DOCX no existe: $docxPath");
        return false;
    }
    $outputDir = rtrim($outputDir, '/') . '/';
    // Ruta de LibreOffice en Windows
    $libreoffice = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
    if (!file_exists($libreoffice)) {
        $libreoffice = 'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe';
    }
    if (!file_exists($libreoffice)) {
        error_log("convertDocxToPdf: LibreOffice no encontrado en Windows.");
        return false;
    }
    $command = "\"$libreoffice\" --headless --nologo --nofirststartwizard --convert-to pdf " .
                "\"" . $docxPath . "\" --outdir \"" . $outputDir . "\"";
    exec($command . " 2>&1", $output, $returnCode);
    error_log("convertDocxToPdf CMD: $command");
    error_log("convertDocxToPdf OUTPUT: " . implode("\n", $output));
    error_log("convertDocxToPdf RETURN: $returnCode");
    $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
    if (!file_exists($pdfPath)) {
        error_log("convertDocxToPdf: PDF no generado: $pdfPath");
        return false;
    }

    return true;
}

function sendPdfInline($pdfFilePath, $filename = 'vista_previa.pdf') {
    if (!file_exists($pdfFilePath)) {
        header("HTTP/1.1 404 Not Found");
        echo "PDF no encontrado";
        exit;
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    readfile($pdfFilePath);
    exit;
}

// Recibir los datos desde la solicitud POST, manejando tanto JSON raw como form-urlencoded
$data = [];
if (isset($_POST['data'])) {
    // Si viene de una submission de formulario con un campo 'data'
    $data = json_decode($_POST['data'], true);
} else {
    // Si viene como un JSON raw (e.g., de Axios)
    $data = json_decode(file_get_contents("php://input"), true);
}
$opcion = isset($data['opcion']) ? $data['opcion'] : 0;

switch ($opcion) {
    case 2: // Opción para contar órdenes generadas y pendientes
        try {
            $prospecto_ids = isset($data['prospecto_ids']) ? $data['prospecto_ids'] : [];
            if (empty($prospecto_ids)) {
                echo json_encode(['ordenes_generadas_count' => 0, 'ordenes_pendientes_count' => 0]);
                exit;
            }
            $conexion = getConexion();
            $placeholders = implode(',', array_fill(0, count($prospecto_ids), '?'));
            $consulta = "SELECT DISTINCT id_prospecto FROM siga_prospectosie_ordenes WHERE id_prospecto IN ($placeholders)";
            $stmt = $conexion->prepare($consulta);
            $stmt->execute($prospecto_ids);
            $ids_con_orden = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            $ordenes_generadas_count = count($ids_con_orden);
            $total_prospectos = count($prospecto_ids);
            $ordenes_pendientes_count = $total_prospectos - $ordenes_generadas_count;
            header('Content-Type: application/json');
            echo json_encode([
            'ordenes_generadas_count' => $ordenes_generadas_count,
            'ordenes_pendientes_count' => $ordenes_pendientes_count,
            'ids_con_orden' => $ids_con_orden
            ]);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(['error' => 'Error al contar las órdenes: ' . $e->getMessage()]);
        }
        break;
    case 1: // VISTA PREVIA (no crea folio ni inserta orden)
        $prospecto_id = $data['prospecto']['id'] ?? null;
        try {
        if (!$prospecto_id) {
            throw new Exception('No se proporcionó prospecto.id');
        }
        $conexion = getConexion();
        $prospecto = getProspectoData($conexion, $prospecto_id);
        if (!$prospecto) {
            throw new Exception("No se encontró el prospecto con ID: " . $prospecto_id);
        }
        $folio = getFolioOrCreate($conexion, $prospecto_id, false);
        $firmas = getFirmas($conexion);
        $templateProcessor = fillTemplateFromData($prospecto, $folio, $firmas);
        $savePath = __DIR__ . '/ordenes_generadas/';
        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
        }
        $tmpDocx = $savePath . 'ISN_' . strtoupper($prospecto['rfc']) . '.docx';
        $templateProcessor->saveAs($tmpDocx);
        if (!convertDocxToPdf($tmpDocx, $savePath)) {
            throw new Exception("Error al convertir DOCX a PDF con LibreOffice.");
        }
        $pdfFilePath = $savePath . 'ISN_' . strtoupper($prospecto['rfc']) . '.pdf';
        sendPdfInline($pdfFilePath, 'vista_previa.pdf');
        } catch (Exception $e) {
            header("Content-Type: text/plain");
            echo "Error al generar PDF:\n" . $e->getMessage() . "\n\n";
            echo $e->getTraceAsString();
        }
        break;
    default: // Generar orden (crear folio si es necesario e insertar orden)
        $prospecto_id = $data['prospecto']['id'];
        $id_generador = $data['usuario_id'];
        try {
            $conexion = getConexion();
            $conexion->beginTransaction();
            $prospecto = getProspectoData($conexion, $prospecto_id);
            if (!$prospecto) {
                throw new Exception("No se encontró el prospecto con ID: " . $prospecto_id);
            }
            $impuesto_id = $prospecto['impuesto_id'];
            $impuestoInfo = getImpuestoInfo($conexion, $impuesto_id);
            if (!$impuestoInfo) {
                throw new Exception("No se encontró impuesto_id = $impuesto_id en la tabla.");
            }
            $templateFile = $impuestoInfo['template_file'];
            $prefix = $impuestoInfo['prefix'];
            $templatePath = __DIR__ . "/formatos/" . $templateFile;
            if (!file_exists($templatePath)) {
                throw new Exception("No existe el archivo base: $templateFile");
            }
            $folio = getFolioOrCreate($conexion, $prospecto_id, true, $id_generador);
            $firmas = getFirmas($conexion);
            $templateProcessor = fillTemplateFromData($prospecto, $folio, $firmas);
            $savePath = __DIR__ . '/ordenes_generadas/';
            if (!is_dir($savePath)) {
                mkdir($savePath, 0777, true);
            }
            $baseName = $prefix . strtoupper($prospecto['rfc']); // Ej: ISR_RFC1234
            $finalDocx = $savePath . $baseName . '.docx';
            $finalPdf  = $savePath . $baseName . '.pdf';
            $templateProcessor->saveAs($finalDocx);
            if (!convertDocxToPdf($finalDocx, $savePath)) {
                throw new Exception("Error al convertir DOCX a PDF con LibreOffice.");
            }
            file_put_contents(__DIR__ . "/impresion.log",
            "Backend está a punto de imprimir: $finalDocx\n",
            FILE_APPEND
            );
            $impreso = imprimirPDF($finalPdf);
            if (!$impreso) {
                error_log("No se pudo imprimir el PDF: $finalPdf");
            }
            $conexion->commit();
            $pdf_url = getBaseUrl() . "/ordenes_generadas/" . $baseName . ".pdf";
            header('Content-Type: application/json');
            echo json_encode(['success' => true,'pdf_url' => $pdf_url,"impreso" => $impreso]);
        } catch (Exception $e) {
            if ($conexion && $conexion->inTransaction()) {
                $conexion->rollBack();
            }
            ob_end_clean();
            error_log('Error al generar el documento: ' . $e->getMessage());
            header("HTTP/1.1 500 Internal Server Error", true, 500);
            echo json_encode(['error' => 'Error al generar el documento: ' . $e->getMessage()]);
        }
        break;
}
