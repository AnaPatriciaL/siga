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
        "------ IMPRESION ------\n" .
        date("Y-m-d H:i:s") . "\n" .
        "PDF recibido: $rutaPDF\n" .
        "Copias solicitadas: $copias\n",
        FILE_APPEND
    );
    if (!file_exists($sumatra)) {
        file_put_contents($logFile, "Sumatra NO encontrado en: $sumatra\n\n", FILE_APPEND);
        return false;
    }
    $printSettings = "simplex";
    if ($copias > 1) {
        $printSettings .= "," . $copias . "x";
    }
    $cmd = "\"$sumatra\" -print-to-default -silent -print-settings \"$printSettings\" \"$rutaPDF\"";
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
                          h.nombre AS estatus_descripcion, j.nombre AS municipio_nombre, j.municipio_id, 
                          j.oficina_id, k.art_retenedor, k.sujeto_retenedor,
                          CONCAT(IFNULL(a.calle, ''),
                              IF(a.num_exterior != '', CONCAT(', ', a.num_exterior), ''),
                              IF(a.num_interior != '', CONCAT(', ', a.num_interior), ''),
                              IF(a.colonia != '', CONCAT(', ', a.colonia), ''),
                              IF(a.cp != '', CONCAT(' C.P. ', a.cp), ''),
                              IF(a.localidad != '', CONCAT(', ', a.localidad), '')) AS domicilio_completo,
                          CONCAT(IFNULL(a.calle, ''),
                              IF(a.num_exterior != '', CONCAT(' No. ', a.num_exterior), ''),
                              IF(a.num_interior != '', CONCAT(' INTERIOR ', a.num_interior), '')) AS calle_numero,
                          CONCAT (IF(a.cp != '', CONCAT(' C.P. ', a.cp), ''),
                          IF(a.localidad != '' AND (j.nombre IS NULL OR TRIM(a.localidad) != TRIM(j.nombre)),
                                        CONCAT(' ', a.localidad, ','),''),
                                IF(j.nombre != '', CONCAT(' ', j.nombre, ', '), ''),
                                'SINALOA') AS ciudad_estado,
                          f.descripcion AS antecedente_descripcion
                        FROM siga_prospectos AS a
                        LEFT JOIN siga_prospectos_impuestos AS b ON a.impuesto_id = b.id
                        LEFT JOIN siga_prospectos_programadores AS d ON a.programador_id = d.id
                        LEFT JOIN siga_prospectos_oficinas AS e ON a.oficina_id = e.id
                        LEFT JOIN siga_prospectos_antecedentes AS f ON a.antecedente_id = f.id
                        LEFT JOIN siga_prospectos_fuentes AS g ON a.fuente_id = g.id
                        LEFT JOIN siga_prospectos_estatus_prospectos AS h ON a.estatus = h.id
                        LEFT JOIN siga_prospectos_municipios AS j ON a.municipio_id = j.municipio_id 
                        LEFT JOIN siga_prospectos_retenedores AS k ON a.retenedor = k.id_retenedor
                        WHERE a.id = ?";
    $stmt = $conexion->prepare($consulta);
    $stmt->execute([$prospecto_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getImpuestoInfo($conexion, $impuesto_id) {
    $sql = "SELECT impuesto FROM siga_prospectos_impuestos WHERE id = ? LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$impuesto_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    return [
        'template_file' => $row['impuesto'] . ".docx",
        'prefix'        => $row['impuesto']
    ];
}

function getNumeroOrden(PDO $conexion, $impuesto_prefix, $anio) {

    if (strpos($impuesto_prefix, 'M') === 0) {
        $prefix = $impuesto_prefix . "-";
    } else if (strpos($impuesto_prefix, 'G') === 0) {
        $prefix = $impuesto_prefix . "-";
    } else {
        $prefix = "D-" . $impuesto_prefix . "-";
    }

    $sql_ordenes = "SELECT MAX(CAST(SUBSTRING(num_orden, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) FROM siga_prospectos_ordenes WHERE num_orden LIKE ? AND anio = ?";
    $stmt_ordenes = $conexion->prepare($sql_ordenes);
    $stmt_ordenes->execute([$prefix . '%', $anio]);
    $max_ordenes = $stmt_ordenes->fetchColumn() ?: 0;

    $sql_emitidas = "SELECT MAX( CAST(SUBSTRING(orden, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) FROM emitidas  WHERE orden LIKE ? AND anio = ?";
    $stmt_emitidas = $conexion->prepare($sql_emitidas);
    $stmt_emitidas->execute([$prefix . '%', $anio]);
    $max_emitidas = $stmt_emitidas->fetchColumn() ?: 0;

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
    $stmt_prosp_check = $conexion->prepare("SELECT periodos FROM siga_prospectos WHERE id = ?");
    $stmt_prosp_check->execute([$prospecto_id]);
    $prospecto_check_data = $stmt_prosp_check->fetch(PDO::FETCH_ASSOC);
    $periodos_prospecto = $prospecto_check_data['periodos'] ?? null;

    $anio_orden = date('Y', strtotime($fecha_orden ?? 'now'));

    // Verificar si ya existe una orden para el mismo prospecto, con los mismos periodos y en el mismo año.
    $stmt_orden = $conexion->prepare(
        "SELECT * FROM siga_prospectos_ordenes 
         WHERE id_prospecto = ? AND periodos = ? AND estatus = 1"
    );
    $stmt_orden->execute([$prospecto_id, $periodos_prospecto]);
    $orden_existente = $stmt_orden->fetch(PDO::FETCH_ASSOC);
    if ($orden_existente) {
        $stmt_folio = $conexion->prepare("SELECT * FROM siga_prospectos_folios_oficios WHERE num_folio = ?");
        $stmt_folio->execute([$orden_existente['num_oficio']]);
        $folio = $stmt_folio->fetch(PDO::FETCH_ASSOC) ?: ['num_folio' => $orden_existente['num_oficio'], 'anio' => date('Y')];
        $folio['num_orden'] = $orden_existente['num_orden'];
        $folio['num_folio'] = str_pad($folio['num_folio'], 5, '0', STR_PAD_LEFT);
        return $folio;
    }
    if (!$create) {
        // Para la vista previa, generamos un número de orden temporal sin guardarlo
        return ['num_folio' => 'XXXXX', 'anio' => date('Y'), 'num_orden' => 'XXXX'];
    }
    $stmt_folio_nuevo = $conexion->prepare("SELECT * FROM siga_prospectos_folios_oficios WHERE estatus = 0 AND anio = ? ORDER BY num_folio ASC LIMIT 1 FOR UPDATE");
    $stmt_folio_nuevo->execute([$anio_orden]);
    $folio_oficio = $stmt_folio_nuevo->fetch(PDO::FETCH_ASSOC);
    if (!$folio_oficio) {
        throw new Exception("No hay folios de oficio disponibles.");
    }
    $folio_oficio['num_folio'] = str_pad($folio_oficio['num_folio'], 5, '0', STR_PAD_LEFT);
    $stmt_prosp = $conexion->prepare("SELECT oficina_id, programador_id, retenedor, impuesto_id, periodos, cambio_domicilio, domicilio_anterior, notificador, fecha_acta FROM siga_prospectos WHERE id = ?");
    $stmt_prosp->execute([$prospecto_id]);
    $prospectoRow = $stmt_prosp->fetch(PDO::FETCH_ASSOC);
    if (!$prospectoRow) {
        throw new Exception("No se encontró el prospecto con id $prospecto_id");
    }
    $oficina_id = $prospectoRow['oficina_id'] ?? null;
    $programador_id = $prospectoRow['programador_id'] ?? null;
    $retenedor_id = $prospectoRow['retenedor'] ?? null; // referencia a siga_prospectos_retenedores.id_retenedor
    $impuesto_id = $prospectoRow['impuesto_id'] ?? null;
    $periodos = $prospectoRow['periodos'] ?? null;
    $cambio_domicilio = $prospectoRow['cambio_domicilio'] ?? 0;

    if ($cambio_domicilio == 1) {
        $domicilio_anterior = $prospectoRow['domicilio_anterior'] ?? null;
        $notificador = $prospectoRow['notificador'] ?? null;
        $fecha_acta = $prospectoRow['fecha_acta'] ?? null;
    } else {
        $domicilio_anterior = null;
        $notificador = null;
        $fecha_acta = null;
    }

    $impuestoInfo = getImpuestoInfo($conexion, $impuesto_id);
    if (!$impuestoInfo) {
        throw new Exception("No se encontró información para el impuesto_id: $impuesto_id");
    }
    $numero_orden = getNumeroOrden($conexion, $impuestoInfo['prefix'], $anio_orden);
    $oficina_grupo = null;
    $oficina_fraccion = null;
    if ($oficina_id) {
        $stmt_oficina = $conexion->prepare("SELECT grupo AS oficina_grupo, fraccion AS oficina_fraccion, domicilio AS oficina_domicilio, telefono AS oficina_telefono FROM siga_prospectos_oficinas WHERE id = ?");
        $stmt_oficina->execute([$oficina_id]);
        $oficinaRow = $stmt_oficina->fetch(PDO::FETCH_ASSOC);
        if ($oficinaRow) {
            $oficina_grupo = $oficinaRow['oficina_grupo'] ?? null;
            $oficina_fraccion = $oficinaRow['oficina_fraccion'] ?? null;
            $oficina_domicilio = $oficinaRow['oficina_domicilio'] ?? null;
            $oficina_telefono = $oficinaRow['oficina_telefono'] ?? null;
        }
    }
    $art_retenedor = null;
    $sujeto_retenedor = null;
    if ($retenedor_id) {
        $stmt_reten = $conexion->prepare("SELECT art_retenedor, sujeto_retenedor FROM siga_prospectos_retenedores WHERE id_retenedor = ?");
        $stmt_reten->execute([$retenedor_id]); 
        $retenRow = $stmt_reten->fetch(PDO::FETCH_ASSOC);
        if ($retenRow) {
            $art_retenedor = $retenRow['art_retenedor'] ?? null;
            $sujeto_retenedor = $retenRow['sujeto_retenedor'] ?? null;
        }
    }
    $update_folio = "UPDATE siga_prospectos_folios_oficios SET estatus = 1 WHERE id = ?";
    $stmt_update = $conexion->prepare($update_folio);
    $stmt_update->execute([$folio_oficio['id']]);

    $insert_orden = "INSERT INTO siga_prospectos_ordenes 
        (id_prospecto, num_oficio, num_orden, anio, grupo, fraccion, id_programador, id_generador, fecha_orden, estatus, id_firmante, id_jefe, id_supervisor, 
        art_retenedor, sujeto_retenedor, periodos, cambio_domicilio, domicilio_anterior, notificador, fecha_acta)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert_orden = $conexion->prepare($insert_orden);
    $stmt_insert_orden->execute([$prospecto_id, $folio_oficio['num_folio'], $numero_orden, $anio_orden,
        $oficina_grupo, $oficina_fraccion, $programador_id,
        $id_generador, $fecha_orden, 1, getFirmas($conexion)['id_firmante'], getFirmas($conexion)['id_jefe'], getFirmas($conexion)['id_supervisor'],
        $art_retenedor, $sujeto_retenedor, $periodos, $cambio_domicilio, $domicilio_anterior, $notificador, $fecha_acta]);

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
    $consulta_personal = "SELECT id_actuante, nombre AS nombre_actuante, cargo, iniciales, estatus FROM siga_prospectos_personal_actuante";
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

function formatPeriodos($conexion, $id_prospecto, $texto_respaldo = null) {
    $conexion->exec("SET lc_time_names = 'es_ES'");
    $consulta_periodos = "SELECT DATE_FORMAT(fecha_inicial, '%d de %M de %Y') AS fechainicial_formateada, 
        DATE_FORMAT(fecha_final, '%d de %M de %Y') AS fechafinal_formateada
        from siga_prospectos_periodos where prospecto_id = ?";
    $stmt = $conexion->prepare($consulta_periodos);
    $stmt->execute([$id_prospecto]);
    $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $periodos_formateados = [];
    
    if (!empty($periodos)) {
        $es_primero = true;
        foreach ($periodos as $periodo) {
            if ($es_primero) {
                $periodos_formateados[] = $periodo['fechainicial_formateada'] . " al " . $periodo['fechafinal_formateada'];
                $es_primero = false;
            } else {
                $periodos_formateados[] = "del " . $periodo['fechainicial_formateada'] . " al " . $periodo['fechafinal_formateada'];
            }
        }
    } elseif (!empty($texto_respaldo) && $texto_respaldo !== 'Periodo no especificado') {
        $meses = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
        $raw_periodos = explode(',', $texto_respaldo);
        $es_primero = true;

        foreach ($raw_periodos as $raw) {
            $fechas = explode('-', trim($raw));
            if (count($fechas) === 2) {
                $fmt = function($d) use ($meses) {
                    $parts = explode('/', trim($d));
                    if (count($parts) !== 3) return trim($d);
                    return (int)$parts[0] . " de " . ($meses[(int)$parts[1]] ?? '') . " de " . $parts[2];
                };
                $inicio = $fmt($fechas[0]);
                $fin = $fmt($fechas[1]);

                if ($es_primero) {
                    $periodos_formateados[] = "$inicio al $fin";
                    $es_primero = false;
                } else {
                    $periodos_formateados[] = "del $inicio al $fin";
                }
            }
        }
    }

    if (empty($periodos_formateados)) {
        return 'Periodo no especificado';
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

function getDocumentacion($conexion, $rfc) {
    if (strlen($rfc) === 12) {
        $consulta = "SELECT descripcion FROM siga_prospectos_documentacion_gabinete WHERE tipo_persona = 'M' AND estatus = 1";
    } else {
       $consulta = "SELECT descripcion FROM siga_prospectos_documentacion_gabinete WHERE tipo_persona = 'F' AND estatus = 1";
    }
    $stmt = $conexion->prepare($consulta);
    $stmt->execute();
    $documentacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $documentacion;                
}

function fillTemplateFromData(PDO $conexion, array $prospecto, array $folio, array $firmas, $fecha_orden_str, $prefix) {
    $fecha_formateada = formatDateToSpanish($fecha_orden_str);
    $template_file = "{$prefix}.docx";

    if (!empty($prospecto['cambio_domicilio'])) {
        $template_file = "{$prefix} - CD.docx";
    } elseif (
        isset($prospecto['fuente_id']) &&
        in_array($prospecto['fuente_id'], [1, 2])
    ) {
        $template_file = "{$prefix} - NR.docx";
    }

    $template_path = __DIR__ . "/formatos/{$template_file}";
    if (!file_exists($template_path)) {
        throw new Exception("No se encontró la plantilla {$template_file}");
    }

    $templateProcessor = new TemplateProcessor($template_path);
    $rfc = trim($prospecto['rfc'] ?? '');
    if (strlen($rfc) === 13) {
        $templateProcessor->cloneBlock('block_rep', 0);
    } else {
        $templateProcessor->cloneBlock('block_rep', 1);
        $templateProcessor->setValue('representante_legal',!empty($prospecto['representante_legal'])? 'REPRESENTANTE LEGAL DE: ' : '');
    }
    if (empty(trim($prospecto['colonia'] ?? ''))) {
        $templateProcessor->cloneBlock('block_colonia', 0);
    } else {
        $templateProcessor->cloneBlock('block_colonia', 1);
        $templateProcessor->setValue('colonia', $prospecto['colonia']);
    }
    $templateProcessor->setValue('num_folio', $folio['num_folio'] ?? 'XXXXX');   
    $templateProcessor->setValue('orden', $folio['num_orden'] ?? 'X-XXX-XXXX');
    $templateProcessor->setValue('anio', $folio['anio'] ?? date('Y'));
    $templateProcessor->setValue('anio2', substr($folio['anio'] ?? date('Y'), -2));
    $templateProcessor->setValue('rfc', $prospecto['rfc'] ?? '');
    $templateProcessor->setValue('nombre', $prospecto['nombre'] ?? '');
    $templateProcessor->setValue('domicilio_completo', $prospecto['domicilio_completo'] ?? '');
    $templateProcessor->setValue('calle_numero', $prospecto['calle_numero'] ?? '');
    $templateProcessor->setValue('ciudad_estado', $prospecto['ciudad_estado'] ?? '');    
    $templateProcessor->setValue('periodos', formatPeriodos($conexion, $prospecto['id'] ?? '', $prospecto['periodos'] ?? null));
    $templateProcessor->setValue('impuesto', $prospecto['impuesto'] ?? '');
    $templateProcessor->setValue('determinado', isset($prospecto['determinado']) ? number_format($prospecto['determinado'], 2) : '0.00');
    $templateProcessor->setValue('oficina_descripcion', $prospecto['oficina_descripcion'] ?? '');
    $templateProcessor->setValue('oficina_grupo', $prospecto['oficina_grupo'] ?? '');
    $templateProcessor->setValue('oficina_fraccion', $prospecto['oficina_fraccion'] ?? '');
    $templateProcessor->setValue('oficina_domicilio', $prospecto['oficina_domicilio'] ?? '');
    $templateProcessor->setValue('oficina_telefono', $prospecto['oficina_telefono'] ?? '');
    $templateProcessor->setValue('art_retenedor', preg_replace('/\s+/', ' ', trim($prospecto['art_retenedor'] ?? '')));
    $templateProcessor->setValue('sujeto_retenedor', $prospecto['sujeto_retenedor'] ?? '');
    $templateProcessor->setValue('domicilio_anterior', $prospecto['domicilio_anterior'] ?? '');
    $fecha_acta_formateada = formatDateToSpanish($prospecto['fecha_acta'] ?? '');
    $templateProcessor->setValue('fecha_acta', $fecha_acta_formateada);
    $templateProcessor->setValue('notificador', $prospecto['notificador'] ?? '');
    $templateProcessor->setValue('cargo', $firmas['cargo'] ?? '');
    $templateProcessor->setValue('nombre_actuante', $firmas['nombre_actuante'] ?? '');
    $templateProcessor->setValue('iniciales', $firmas['iniciales'] ?? '');
    $templateProcessor->setValue('fecha_orden', $fecha_formateada);
    $documentacionArr = getDocumentacion($conexion, $rfc);
    $documentacionTexto = '';
    if (!empty($documentacionArr)) {
        $lineas = array_map(function ($row) {
            return trim($row['descripcion']);
        }, $documentacionArr);
        $documentacionTexto = implode("</w:t><w:br/><w:t>", $lineas);
    }
    $templateProcessor->setValue('documentacion', $documentacionTexto);

    return $templateProcessor;
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

function obtenerImpresoraPredeterminada() {
    $cmd = 'powershell -Command "(Get-CimInstance Win32_Printer | Where-Object { $_.Default -eq $true }).Name"';
    $salida = [];
    exec($cmd, $salida);

    if (!empty($salida[0])) {
        return trim($salida[0]);
    }

    return null;
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
        $nombre_documento = $prefix;
        $templateFile = "{$prefix}.docx";
        if ($prospecto['cambio_domicilio'] == 1) {
            $templateFile = "{$prefix} - CD.docx";
            $nombre_documento = "{$prefix} - CD";
        } elseif (isset($prospecto['fuente_id']) && in_array($prospecto['fuente_id'], [1, 2])) {
            $templateFile = "{$prefix} - NR.docx";
            $nombre_documento = "{$prefix} - NR";
        }

        $folio = getFolioOrCreate($conexion, $prospecto_id, false, null, $fecha_orden_vista);
        $firmas = getFirmas($conexion);
        $templateProcessor = fillTemplateFromData($conexion, $prospecto, $folio, $firmas, $fecha_orden_vista, $prefix);
        $savePath = __DIR__ . '/ordenes_generadas/';
        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
        }
        $tmpDocx = $savePath . $nombre_documento . '_' . strtoupper($prospecto['rfc']) . '_' . $fecha_orden_vista . '_VP.docx';
        $templateProcessor->saveAs($tmpDocx);
        if (!convertDocxToPdf($tmpDocx, $savePath)) {
            throw new Exception("Error al convertir DOCX a PDF con LibreOffice.");
        }
        $pdfFilePath = $savePath . $nombre_documento . '_' . strtoupper($prospecto['rfc']) . '_' . $fecha_orden_vista . '_VP.pdf';
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

            $placeholders = implode(',', array_fill(0, count($prospecto_ids), '?'));
            
            // La consulta ahora une con siga_prospectos para obtener los periodos de cada prospecto
            // y valida contra los periodos correspondientes.
            $consulta = "SELECT DISTINCT o.id_prospecto 
                         FROM siga_prospectos_ordenes o
                         JOIN siga_prospectos p ON o.id_prospecto = p.id
                         WHERE o.estatus = 1 
                            AND o.periodos = p.periodos
                            AND o.id_prospecto IN ($placeholders)";
            
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
    case 3:
         $prospecto = $data['prospecto'];
         $prospecto_id = $prospecto['id'];
         try {
             $conexion = getConexion();
             $consulta = "UPDATE siga_prospectos_ordenes SET 
                             id_programador = :id_programador,
                             periodos = :periodos
                          WHERE id_prospecto = :id_prospecto";
             $stmt = $conexion->prepare($consulta);
             $stmt->execute([
                 'id_programador' => $prospecto['programador_id'],
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
    case 4: // VISTA PREVIA AUTORIZADA 
        $prospecto_id = $data['prospecto']['id'] ?? null;
        $fecha_orden_vista = $data['fecha_orden'] ?? date('Y-m-d');
        $copias = isset($data['copias']) ? intval($data['copias']) : 1;
        try {
        if (!$prospecto_id) {
            throw new Exception('No se proporcionó prospecto.id');
        }
        $conexion = getConexion();
        $prospecto = getProspectoData($conexion, $prospecto_id);
        if (!$prospecto) {
            throw new Exception("No se encontró el prospecto con ID: " . $prospecto_id);
        }

        if (isset($prospecto['periodos'])) {
             $update_sql = "UPDATE siga_prospectos_ordenes 
                            SET fecha_orden = ? 
                            WHERE id_prospecto = ? AND periodos = ? AND estatus = 1";
             $stmt_update = $conexion->prepare($update_sql);
             $stmt_update->execute([$fecha_orden_vista, $prospecto_id, $prospecto['periodos']]);
        }

        $impuesto_id = $prospecto['impuesto_id'];
        $impuestoInfo = getImpuestoInfo($conexion, $impuesto_id);
        if (!$impuestoInfo) {
            throw new Exception("No se encontró información para el impuesto_id: $impuesto_id");
        }
        $prefix = $impuestoInfo['prefix'];
        $nombre_documento = $prefix;
        $templateFile = "{$prefix}.docx";
        if ($prospecto['cambio_domicilio'] == 1) {
            $templateFile = "{$prefix} - CD.docx";
            $nombre_documento = "{$prefix} - CD";
        } elseif (isset($prospecto['fuente_id']) && in_array($prospecto['fuente_id'], [1, 2])) {
            $templateFile = "{$prefix} - NR.docx";
            $nombre_documento = "{$prefix} - NR";
        }

        $folio = getFolioOrCreate($conexion, $prospecto_id, false, null, $fecha_orden_vista);
        $firmas = getFirmas($conexion);
        $templateProcessor = fillTemplateFromData($conexion, $prospecto, $folio, $firmas, $fecha_orden_vista, $prefix);
        $savePath = __DIR__ . '/ordenes_generadas/';
        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
        }
        $tmpDocx = $savePath . $nombre_documento . '_' . strtoupper($prospecto['rfc']) . '_' . $fecha_orden_vista . '_AUTORIZADA' . '.docx';
        $templateProcessor->saveAs($tmpDocx);
        if (!convertDocxToPdf($tmpDocx, $savePath)) {
            throw new Exception("Error al convertir DOCX a PDF con LibreOffice.");
        }
        $pdfFilePath = $savePath . $nombre_documento . '_' . strtoupper($prospecto['rfc']) . '_' . $fecha_orden_vista . '_AUTORIZADA' . '.pdf';

        /*file_put_contents(__DIR__ . "/impresion.log",
            "Backend está a punto de imprimir (Case 4): $tmpDocx\n",
            FILE_APPEND
        );
        $impreso = imprimirPDF($pdfFilePath, $copias);
        if (!$impreso) {
            error_log("No se pudo imprimir el PDF (Case 4): $pdfFilePath");
        }*/

        sendPdfInline($pdfFilePath, 'vista_previa.pdf');
        } catch (Exception $e) {
            header("Content-Type: text/plain");
            echo "Error al generar PDF:\n" . $e->getMessage() . "\n\n";
            echo $e->getTraceAsString();
        }
        break;
    case 5:
        try {
            $prospecto_ids = $data['prospecto_ids'] ?? [];
            if (empty($prospecto_ids)) {
                echo json_encode([]);
                exit;
            }
            $conexion = getConexion();
            $placeholders = implode(',', array_fill(0, count($prospecto_ids), '?'));

            $sql_firmante = "(SELECT nombre FROM siga_prospectos_personal_actuante WHERE id_actuante = 1)";
            $sql_jefe = "(SELECT nombre FROM siga_prospectos_personal_actuante WHERE id_actuante = 2)";

            $consulta = "SELECT 
                            o.fecha_orden, 
                            o.num_oficio, 
                            o.num_orden, 
                            p.nombre, 
                            CONCAT(IF(p.localidad != '' AND (m.nombre IS NULL OR TRIM(p.localidad) != TRIM(m.nombre)),
                                        CONCAT(' ', p.localidad, ','),''),
                                IF(m.nombre != '', CONCAT(' ', m.nombre, ', '), ''),
                                'SINALOA') AS municipio,
                            ($sql_firmante) AS nombre_firmante,
                            ($sql_jefe) AS nombre_jefe
                        FROM siga_prospectos_ordenes o
                        JOIN siga_prospectos p ON o.id_prospecto = p.id
                        LEFT JOIN siga_prospectos_municipios m ON p.municipio_id = m.municipio_id
                        WHERE o.id_prospecto IN ($placeholders) ORDER BY p.oficina_id";

            $stmt = $conexion->prepare($consulta);
            $stmt->execute($prospecto_ids);
            $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode($ordenes);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(['error' => 'Error al consultar las órdenes: ' . $e->getMessage()]);
        }
        break;
    case 6: // VISTA PREVIA EMITIDA (no crea folio ni inserta orden)
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

        $stmt_fecha = $conexion->prepare("SELECT fecha_orden FROM siga_prospectos_ordenes WHERE id_prospecto = ? AND periodos = ? AND estatus = 1");
        $stmt_fecha->execute([$prospecto_id, $prospecto['periodos']]);
        $orden_data = $stmt_fecha->fetch(PDO::FETCH_ASSOC);
        
        $fecha_orden_vista = $orden_data['fecha_orden'] ?? ($data['fecha_orden'] ?? date('Y-m-d'));

        $impuesto_id = $prospecto['impuesto_id'];
        $impuestoInfo = getImpuestoInfo($conexion, $impuesto_id);
        if (!$impuestoInfo) {
            throw new Exception("No se encontró información para el impuesto_id: $impuesto_id");
        }
        $prefix = $impuestoInfo['prefix'];
        $nombre_documento = $prefix;
        $templateFile = "{$prefix}.docx";
        if ($prospecto['cambio_domicilio'] == 1) {
            $templateFile = "{$prefix} - CD.docx";
            $nombre_documento = "{$prefix} - CD";
        } elseif (isset($prospecto['fuente_id']) && in_array($prospecto['fuente_id'], [1, 2])) {
            $templateFile = "{$prefix} - NR.docx";
            $nombre_documento = "{$prefix} - NR";
        }

        $folio = getFolioOrCreate($conexion, $prospecto_id, false, null, $fecha_orden_vista);
        $firmas = getFirmas($conexion);
        $templateProcessor = fillTemplateFromData($conexion, $prospecto, $folio, $firmas, $fecha_orden_vista, $prefix);
        $savePath = __DIR__ . '/ordenes_generadas/';
        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
        }
        $tmpDocx = $savePath . $nombre_documento . '_' . strtoupper($prospecto['rfc']) . '_' . $fecha_orden_vista . '_EMITIDA' . '.docx';
        $templateProcessor->saveAs($tmpDocx);
        if (!convertDocxToPdf($tmpDocx, $savePath)) {
            throw new Exception("Error al convertir DOCX a PDF con LibreOffice.");
        }
        $pdfFilePath = $savePath . $nombre_documento . '_' . strtoupper($prospecto['rfc']) . '_' . $fecha_orden_vista . '_EMITIDA' . '.pdf';

        sendPdfInline($pdfFilePath, 'vista_previa.pdf');
        } catch (Exception $e) {
            header("Content-Type: text/plain");
            echo "Error al generar PDF:\n" . $e->getMessage() . "\n\n";
            echo $e->getTraceAsString();
        }
        break;
    case 7: // OBTENER IMPRESORA PREDETERMINADA
        header('Content-Type: application/json');
        echo json_encode([
            'impresora' => obtenerImpresoraPredeterminada()
        ]);
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
            $prefix = $impuestoInfo['prefix'];
            $nombre_documento = $prefix;
            $templateFile = "{$prefix}.docx";
            if ($prospecto['cambio_domicilio'] == 1) {
                $templateFile = "{$prefix} - CD.docx";
                $nombre_documento = "{$prefix} - CD";
            } elseif (isset($prospecto['fuente_id']) && in_array($prospecto['fuente_id'], [1, 2])) {
                $templateFile = "{$prefix} - NR.docx";
                $nombre_documento = "{$prefix} - NR";
            }
            $templatePath = __DIR__ . "/formatos/{$templateFile}";
            if (!file_exists($templatePath)) {
                throw new Exception("No existe el archivo base: $templateFile");
            }
            $folio = getFolioOrCreate($conexion, $prospecto_id, true, $id_generador, $fecha_orden);
            $firmas = getFirmas($conexion);
            $templateProcessor = fillTemplateFromData($conexion, $prospecto, $folio, $firmas, $fecha_orden, $prefix);
            $savePath = __DIR__ . '/ordenes_generadas/';
            if (!is_dir($savePath)) {
                mkdir($savePath, 0777, true);
            }
            $baseName = $nombre_documento . '_' . strtoupper($prospecto['rfc']) . '_' . $fecha_orden; // Ej: ISN_RFC1234_2023-10-27
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
