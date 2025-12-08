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

function imprimirPDF($rutaPDF, $copias = 1) {
    $sumatra = "C:\\Program Files\\SumatraPDF\\SumatraPDF.exe";
    $logFile = __DIR__ . "/impresion.log";
    file_put_contents($logFile,
        "------ INICIO DE PRUEBA ------\n" .
        date("Y-m-d H:i:s") . "\n" .
        "PDF recibido: $rutaPDF\n" .
        "Copias solicitadas: $copias\n",
        FILE_APPEND
    );
    if (!file_exists($sumatra)) {
        file_put_contents($logFile, "Sumatra NO encontrado en: $sumatra\n\n", FILE_APPEND);
        return false;
    }
    $printSettings = "";
    if ($copias > 1) {
        $printSettings = "-print-settings \"" . $copias . "x\"";
    }
    $cmd = "\"$sumatra\" -print-to-default -silent $printSettings \"$rutaPDF\"";
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
        'template_file' => $row['impuesto'] . ".docx",
        'prefix'        => $row['impuesto']
    ];
}
function getNumeroOrden(PDO $conexion, $impuesto_prefix) {
    $prefix = "D-" . $impuesto_prefix . "-";
    //Obtener la orden maxima que se ha generado en siga_prospectosie_ordenes
     $sql_ordenes = "SELECT MAX(CAST(SUBSTRING(num_orden, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) FROM siga_prospectosie_ordenes WHERE num_orden LIKE ?";
    $stmt_ordenes = $conexion->prepare($sql_ordenes);
    $stmt_ordenes->execute([$prefix . '%']);
    $max_ordenes = $stmt_ordenes->fetchColumn() ?: 0;
    //Obtener la orden maxima que se ha generado en emitidas
    $sql_emitidas = "SELECT MAX(CAST(SUBSTRING(orden, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) FROM emitidas WHERE orden LIKE ?";
    $stmt_emitidas = $conexion->prepare($sql_emitidas);
    $stmt_emitidas->execute([$prefix . '%']);
    $max_emitidas = $stmt_emitidas->fetchColumn() ?: 0;
    //Obtener el primer folio disponible en siga_prospectos_ordenes_disponibles 
    $sql_disponibles = "SELECT MIN(CAST(SUBSTRING(num_orden, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) FROM siga_prospectos_ordenes_disponibles WHERE num_orden LIKE ?";
    $stmt_disponibles = $conexion->prepare($sql_disponibles);
    $stmt_disponibles->execute([$prefix . '%']);
    $min_disponible = $stmt_disponibles->fetchColumn() ?: PHP_INT_MAX;

    $siguiente_consecutivo = max($max_ordenes, $max_emitidas) + 1;

    $final_consecutivo = min($siguiente_consecutivo, $min_disponible);

    return $prefix . str_pad($final_consecutivo, 4, '0', STR_PAD_LEFT);
}

function getFolioOrCreate(PDO $conexion, $prospecto_id, $create = false, $id_generador = null, $fecha_orden = null) {
    // Obtener periodos y año para una validación más robusta.
    $stmt_prosp_check = $conexion->prepare("SELECT periodos FROM siga_prospectosie WHERE id = ?");
    $stmt_prosp_check->execute([$prospecto_id]);
    $prospecto_check_data = $stmt_prosp_check->fetch(PDO::FETCH_ASSOC);
    $periodos_prospecto = $prospecto_check_data['periodos'] ?? null;

    $anio_orden = date('Y', strtotime($fecha_orden ?? 'now'));

    // Verificar si ya existe una orden para el mismo prospecto, con los mismos periodos y en el mismo año.
    $stmt_orden = $conexion->prepare(
        "SELECT * FROM siga_prospectosie_ordenes 
         WHERE id_prospecto = ? AND periodos = ? AND anio = ? AND estatus = 1"
    );
    $stmt_orden->execute([$prospecto_id, $periodos_prospecto, $anio_orden]);
    $orden_existente = $stmt_orden->fetch(PDO::FETCH_ASSOC);
    if ($orden_existente) {
        $stmt_folio = $conexion->prepare("SELECT * FROM siga_prospectosie_folios_oficios WHERE num_folio = ?");
        $stmt_folio->execute([$orden_existente['num_oficio']]);
        $folio = $stmt_folio->fetch(PDO::FETCH_ASSOC) ?: ['num_folio' => $orden_existente['num_oficio'], 'anio' => date('Y')];
        $folio['num_orden'] = $orden_existente['num_orden'];
        return $folio;
    }
    if (!$create) {
        // Para la vista previa, generamos un número de orden temporal sin guardarlo
        return ['num_folio' => 'XXXX', 'anio' => date('Y'), 'num_orden' => 'XXXX'];
    }
    $stmt_folio_nuevo = $conexion->prepare("SELECT * FROM siga_prospectosie_folios_oficios WHERE estatus = 0 ORDER BY num_folio ASC LIMIT 1 FOR UPDATE");
    $stmt_folio_nuevo->execute();
    $folio_oficio = $stmt_folio_nuevo->fetch(PDO::FETCH_ASSOC);
    if (!$folio_oficio) {
        throw new Exception("No hay folios de oficio disponibles.");
    }
    $stmt_prosp = $conexion->prepare("SELECT oficina_id, programador_id, retenedor, impuesto_id, periodos FROM siga_prospectosie WHERE id = ?");
    $stmt_prosp->execute([$prospecto_id]);
    $prospectoRow = $stmt_prosp->fetch(PDO::FETCH_ASSOC);
    if (!$prospectoRow) {
        throw new Exception("No se encontró el prospecto con id $prospecto_id");
    }
    $oficina_id = $prospectoRow['oficina_id'] ?? null;
    $programador_id = $prospectoRow['programador_id'] ?? null;
    $retenedor_id = $prospectoRow['retenedor'] ?? null; // referencia a siga_prospectosie_retenedores.id_retenedor
    $impuesto_id = $prospectoRow['impuesto_id'] ?? null;
    $periodos = $prospectoRow['periodos'] ?? null;

    $impuestoInfo = getImpuestoInfo($conexion, $impuesto_id);
    if (!$impuestoInfo) {
        throw new Exception("No se encontró información para el impuesto_id: $impuesto_id");
    }
    $numero_orden = getNumeroOrden($conexion, $impuestoInfo['prefix']);

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
        (id_prospecto, num_oficio, num_orden, anio, grupo, fraccion, id_programador, id_generador, fecha_orden, estatus, id_firmante, id_jefe, id_supervisor, art_retenedor, sujeto_retenedor, periodos)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert_orden = $conexion->prepare($insert_orden);
    // Si no se proporciona fecha_orden, se usa la fecha actual del servidor de BD.
    $stmt_insert_orden->execute([$prospecto_id, $folio_oficio['num_folio'], $numero_orden, date('Y'),
        $oficina_grupo, $oficina_fraccion, $programador_id,
        $id_generador, $fecha_orden, 1, getFirmas($conexion)['id_firmante'], getFirmas($conexion)['id_jefe'], getFirmas($conexion)['id_supervisor'],
        $art_retenedor, $sujeto_retenedor, $periodos]);

    $folio_oficio['num_orden'] = $numero_orden;

    // Si el número de orden utilizado proviene de la tabla de disponibles, eliminarlo.
    $stmt_check_disponible = $conexion->prepare("SELECT id FROM siga_prospectos_ordenes_disponibles WHERE num_orden = ?");
    $stmt_check_disponible->execute([$numero_orden]);
    if ($stmt_check_disponible->fetch()) {
        $stmt_delete = $conexion->prepare("DELETE FROM siga_prospectos_ordenes_disponibles WHERE num_orden = ?");
        $stmt_delete->execute([$numero_orden]);
    }
    return $folio_oficio;
}

function getFirmas(PDO $conexion) {
    $consulta_personal = "SELECT id_actuante, nombre AS nombre_actuante, cargo, iniciales, estatus FROM siga_prospectosie_personal_actuante";
    $stmt_personal = $conexion->prepare($consulta_personal);
    $stmt_personal->execute();
    $personal_actuante = $stmt_personal->fetchAll(PDO::FETCH_ASSOC);
    $iniciales = [];
    $firmas = [
        'cargo' => '',
        'nombre_actuante' => '',
        'iniciales' => '',
        'id_firmante' => null,
        'id_jefe' => null,
        'id_supervisor' => null,
    ];

    foreach ($personal_actuante as $persona) {
        if (intval($persona['estatus']) === 1) {
            switch (intval($persona['id_actuante'])) {
                case 1: // Firmante
                $texto_cargo = $persona['cargo'];
                    $firmas['cargo'] = str_replace("\\n", "</w:t><w:br/><w:t>", $texto_cargo);
                    $firmas['nombre_actuante'] = $persona['nombre_actuante'];
                    $firmas['id_firmante'] = $persona['id_actuante'];
                    break;
                case 2: // Jefe
                    $firmas['id_jefe'] = $persona['id_actuante'];
                    $iniciales[] = $persona['iniciales'];
                    break;
                case 3: // Supervisor
                    $firmas['id_supervisor'] = $persona['id_actuante'];
                    $iniciales[] = $persona['iniciales'];
                    break;
            }
        }
    }
    $firmas['iniciales'] = implode(' / ', $iniciales);
    return $firmas;
}

function formatPeriodos($conexion, $id_prospecto) {
    $conexion->exec("SET lc_time_names = 'es_ES'");
    $consulta_periodos = "SELECT DATE_FORMAT(fecha_inicial, '%d de %M de %Y') AS fechainicial_formateada, 
        DATE_FORMAT(fecha_final, '%d de %M de %Y') AS fechafinal_formateada
        from siga_prospectosie_periodos where prospecto_id = ?";
    $stmt = $conexion->prepare($consulta_periodos);
    $stmt->execute([$id_prospecto]);
    $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($periodos)) {
        return 'Periodo no especificado';
    }

    $periodos_formateados = [];
    $es_primero = true;
    foreach ($periodos as $periodo) {
        if ($es_primero) {
            $periodos_formateados[] = $periodo['fechainicial_formateada'] . " al " . $periodo['fechafinal_formateada'];
            $es_primero = false;
        } else {
            $periodos_formateados[] = "del " . $periodo['fechainicial_formateada'] . " al " . $periodo['fechafinal_formateada'];
        }
    }

    $total = count($periodos_formateados);
    if ($total === 1) {
        return $periodos_formateados[0];
    } else {
        $ultimo_periodo = array_pop($periodos_formateados);
        return implode(', ', $periodos_formateados) . ', y ' . $ultimo_periodo;
    }
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
            return $fecha_orden_str; // Si falla, devuelve la fecha original
        }
    }
    return '';
}

function fillTemplateFromData(PDO $conexion, array $prospecto, array $folio, array $firmas, $fecha_orden_str) {
    // Formatear la fecha al formato "dd de MMMM de yyyy"
    if (class_exists('IntlDateFormatter')) {
        // Implementación original si la extensión está disponible
    }

    $fecha_formateada = formatDateToSpanish($fecha_orden_str);
    $templateProcessor = new TemplateProcessor(__DIR__ . '/formatos/ISN.docx');
    $templateProcessor->setValue('num_folio', $folio['num_folio'] ?? 'XXXX');   
    $templateProcessor->setValue('orden', $folio['num_orden'] ?? 'X-XXX-XXXX');
    $templateProcessor->setValue('anio', $folio['anio'] ?? date('Y'));
    $templateProcessor->setValue('representante_legal', $prospecto['representante_legal'] ?? '');
    $templateProcessor->setValue('rfc', $prospecto['rfc'] ?? '');
    $templateProcessor->setValue('nombre', $prospecto['nombre'] ?? '');
    $templateProcessor->setValue('domicilio_completo', $prospecto['domicilio_completo'] ?? '');
    $templateProcessor->setValue('calle_numero', $prospecto['calle_numero'] ?? '');
    $templateProcessor->setValue('colonia', $prospecto['colonia'] ?? '');
    $templateProcessor->setValue('ciudad_estado', $prospecto['ciudad_estado'] ?? '');    
    $templateProcessor->setValue('periodos', formatPeriodos($conexion, $prospecto['id'] ?? ''));
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
    $templateProcessor->setValue('fecha_orden', $fecha_formateada);    return $templateProcessor;
}

function convertDocxToPdf($docxPath, $outputDir)
{
    if (!file_exists($docxPath)) {
        error_log("convertDocxToPdf: El archivo DOCX no existe: $docxPath");
        return false;
    }
    $outputDir = rtrim($outputDir, '/') . '/';
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

$data = [];
if (isset($_POST['data'])) {
    $data = json_decode($_POST['data'], true);
} else {
    $data = json_decode(file_get_contents("php://input"), true);
}
$opcion = isset($data['opcion']) ? $data['opcion'] : 0;

switch ($opcion) {
    case 1: // VISTA PREVIA (no crea folio ni inserta orden)
        $prospecto_id = $data['prospecto']['id'] ?? null;
        $fecha_orden_vista = $data['fecha_orden'] ?? date('Y-m-d');
        try {
        if (!$prospecto_id) {
            throw new Exception('No se proporcionó prospecto.id');
        }
        $conexion = getConexion();
        $prospecto = getProspectoData($conexion, $prospecto_id);
        if (!$prospecto) {
            throw new Exception("No se encontró el prospecto con ID: " . $prospecto_id);
        }
        $impuesto_id = $prospecto['impuesto_id'];
        $impuestoInfo = getImpuestoInfo($conexion, $impuesto_id);
        if (!$impuestoInfo) {
            throw new Exception("No se encontró información para el impuesto_id: $impuesto_id");
        }
        $prefix = $impuestoInfo['prefix'];

        $folio = getFolioOrCreate($conexion, $prospecto_id, false, null, $fecha_orden_vista);
        $firmas = getFirmas($conexion);
        $templateProcessor = fillTemplateFromData($conexion, $prospecto, $folio, $firmas, $fecha_orden_vista);
        $savePath = __DIR__ . '/ordenes_generadas/';
        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
        }
        $tmpDocx = $savePath . $prefix . '_' . strtoupper($prospecto['rfc']) . '.docx';
        $templateProcessor->saveAs($tmpDocx);
        if (!convertDocxToPdf($tmpDocx, $savePath)) {
            throw new Exception("Error al convertir DOCX a PDF con LibreOffice.");
        }
        $pdfFilePath = $savePath . $prefix . '_' . strtoupper($prospecto['rfc']) . '.pdf';
        sendPdfInline($pdfFilePath, 'vista_previa.pdf');
        } catch (Exception $e) {
            header("Content-Type: text/plain");
            echo "Error al generar PDF:\n" . $e->getMessage() . "\n\n";
            echo $e->getTraceAsString();
        }
        break;
    case 2: // Opción para contar órdenes generadas y pendientes
        try {
            $prospecto_ids = isset($data['prospecto_ids']) ? $data['prospecto_ids'] : [];
            if (empty($prospecto_ids)) {
                echo json_encode(['ordenes_generadas_count' => 0, 'ordenes_pendientes_count' => 0]);
                exit;
            }
            $conexion = getConexion();
            $fecha_orden = $data['fecha_orden'] ?? date('Y-m-d');
            $anio_orden = date('Y', strtotime($fecha_orden));

            $placeholders = implode(',', array_fill(0, count($prospecto_ids), '?'));
            
            // La consulta ahora une con siga_prospectosie para obtener los periodos de cada prospecto
            // y valida contra el año de la orden y los periodos correspondientes.
            $consulta = "SELECT DISTINCT o.id_prospecto 
                         FROM siga_prospectosie_ordenes o
                         JOIN siga_prospectosie p ON o.id_prospecto = p.id
                         WHERE o.estatus = 1 
                            AND o.anio = ?
                            AND o.periodos = p.periodos
                            AND o.id_prospecto IN ($placeholders)";
            
            $stmt = $conexion->prepare($consulta);
            $params = array_merge([$anio_orden], $prospecto_ids);
            $stmt->execute($params);
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
    case 3:
         $prospecto = $data['prospecto'];
         $prospecto_id = $prospecto['id'];
         try {
             $conexion = getConexion();
             $consulta = "UPDATE siga_prospectosie_ordenes SET 
                             num_oficio = :num_oficio,
                             grupo = :grupo,
                             fraccion = :fraccion, 
                             id_programador = :id_programador,
                             art_retenedor = :art_retenedor,
                             sujeto_retenedor = :sujeto_retenedor,
                             periodos = :periodos
                          WHERE id_prospecto = :id_prospecto";
             $stmt = $conexion->prepare($consulta);
             $stmt->execute([
                 'num_oficio' => $prospecto['num_oficio'],
                 'grupo' => $prospecto['oficina_grupo'],
                 'fraccion' => $prospecto['oficina_fraccion'],
                 'id_programador' => $prospecto['programador_id'], 
                 'art_retenedor' => $prospecto['art_retenedor'],
                 'sujeto_retenedor' => $prospecto['sujeto_retenedor'],
                 'periodos' => $prospecto['periodos'],
                 'id_prospecto' => $prospecto_id
             ]);
 
             header('Content-Type: application/json');
             echo json_encode(['success' => true, 'message' => 'Orden actualizada correctamente']);
         } catch (Exception $e) {
             header("HTTP/1.1 500 Internal Server Error");
             echo json_encode(['error' => 'Error al actualizar la orden: ' . $e->getMessage()]);
         }
         break;
    case 4:
        $prospecto_id = $data['prospecto']['id'] ?? null;
        $fecha_orden_vista = $data['fecha_orden'] ?? date('Y-m-d');
        try {
        if (!$prospecto_id) {
            throw new Exception('No se proporcionó prospecto.id');
        }
        $conexion = getConexion();
        $prospecto = getProspectoData($conexion, $prospecto_id);
        if (!$prospecto) {
            throw new Exception("No se encontró el prospecto con ID: " . $prospecto_id);
        }
        $impuesto_id = $prospecto['impuesto_id'];
        $impuestoInfo = getImpuestoInfo($conexion, $impuesto_id);
        if (!$impuestoInfo) {
            throw new Exception("No se encontró información para el impuesto_id: $impuesto_id");
        }
        $prefix = $impuestoInfo['prefix'];

        $folio = getFolioOrCreate($conexion, $prospecto_id, false, null, $fecha_orden_vista);
        $firmas = getFirmas($conexion);
        $templateProcessor = fillTemplateFromData($conexion, $prospecto, $folio, $firmas, $fecha_orden_vista);
        $savePath = __DIR__ . '/ordenes_generadas/';
        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
        }
        $tmpDocx = $savePath . $prefix . '_' . strtoupper($prospecto['rfc']) . '_EMITIDA' . '.docx';
        $templateProcessor->saveAs($tmpDocx);
        if (!convertDocxToPdf($tmpDocx, $savePath)) {
            throw new Exception("Error al convertir DOCX a PDF con LibreOffice.");
        }
        $pdfFilePath = $savePath . $prefix . '_' . strtoupper($prospecto['rfc']) . '_EMITIDA' . '.pdf';
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
        $copias = isset($data['copias']) ? intval($data['copias']) : 1;
        $fecha_orden = $data['fecha_orden'] ?? date('Y-m-d');
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
            $folio = getFolioOrCreate($conexion, $prospecto_id, true, $id_generador, $fecha_orden);
            $firmas = getFirmas($conexion);
            $templateProcessor = fillTemplateFromData($conexion, $prospecto, $folio, $firmas, $fecha_orden);
            $savePath = __DIR__ . '/ordenes_generadas/';
            if (!is_dir($savePath)) {
                mkdir($savePath, 0777, true);
            }
            $baseName = $prefix . '_' . strtoupper($prospecto['rfc']); // Ej: ISN_RFC1234
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
            $impreso = imprimirPDF($finalPdf, $copias);
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
