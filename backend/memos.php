<?php
ob_start();

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
                case 2: // Firmante
                $texto_cargo = $persona['cargo'];
                    $firmas['cargo'] = str_replace("\\n", "</w:t><w:br/><w:t>", $texto_cargo);
                    $firmas['nombre_actuante'] = $persona['nombre_actuante'];
                    $firmas['id_firmante'] = $persona['id_actuante'];
                    break;
                case 3: // Supervisor
                    $firmas['id_supervisor'] = $persona['id_actuante'];
                    $firmas['iniciales'] = $persona['iniciales'];
                    break;
            }
        }
    }
    return $firmas;
}
function fechaFrontendToDB(?string $fecha): string
{
    if (empty($fecha)) {
        return date('Y-m-d');
    }

    // Espera formato DD/MM/YYYY
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
            return $fecha_orden_str; // Si falla, devuelve la fecha original
        }
    }
    return '';
}
function numeroATexto(int $numero): string
{
    if (!class_exists('NumberFormatter')) {
        return (string)$numero;
    }
    $formatter = new NumberFormatter("es", NumberFormatter::SPELLOUT);
    $texto = $formatter->format($numero);
    $texto = str_replace(
        ['-', '‐', '-', '–', '—'],
        ' ',
        $texto
    );               
    return mb_strtoupper($texto, 'UTF-8');
}
function analizarProspectos(array $prospectos): array
{
    $total = count($prospectos);

    $nombre_num = numeroATexto($total);

    $hayOrden = false;
    $hayCarta = false;

    foreach ($prospectos as $p) {
        $num = strtoupper(trim($p['num_orden'] ?? ''));

        if (strpos($num, 'D-') === 0 || strpos($num, 'G-') === 0) {
            $hayOrden = true;
        }

        if (strpos($num, 'M-') === 0) {
            $hayCarta = true;
        }
    }

    if ($hayOrden && $hayCarta) {
        $tipo = 'Órdenes y cartas invitación';
    } elseif ($hayOrden) {
        $tipo = ($total > 1) ? 'Órdenes' : 'Órden';
    }elseif ($hayCarta) {
        $tipo = ($total > 1) ? 'Cartas invitación' : 'Carta invitación';
    } else {
        $tipo = '';
    }

    return [
        'num_ordenes' => $total,
        'nombre_num' => $nombre_num,
        'tipo_orden_carta' => $tipo
    ];
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
function obtieneDatosMemo($conexion, $id){
    $consulta = "SELECT m.id AS folio_memo,m.fecha AS fecha_memo, m.destinatario, m.asunto, m.oficina_id, o.nombre AS oficina_nombre,
    m.departamento_id, d.nombre AS departamento_nombre FROM memos m LEFT JOIN memos_cat_oficinas o ON o.id = m.oficina_id 
    LEFT JOIN memos_cat_deptos d ON d.id = m.departamento_id WHERE m.id = :id";
    $stmt = $conexion->prepare($consulta);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    return $data;
}
function fillTemplateFromData(PDO $conexion,array $prospectos, array $infoProspectos, $folio_memo, array $firmas, $fecha_memo, array $datos_memo) {  
    $fecha_formateada = formatDateToSpanish($fecha_memo);
    $template_file = "memo.docx";
    $oficinaId   = (int)$datos_memo['oficina_id'];
    $oficina     = $datos_memo['oficina_nombre'] ?? '';
    $departamento = $datos_memo['departamento_nombre'] ?? '';               
    if ($oficinaId === 4) {
        // Oficina 4 → Jefe de departamento
        $puesto_destinatario = 'JEFE DE DEPARTAMENTO ' . $departamento;
    } else {
        // Cualquier otra oficina
        $puesto_destinatario = 'JEFE DE OFICINA DE AUDITORIA DE ' . $oficina;
    }
    if ($oficinaId === 4 && empty($departamento)) {
        $puesto_destinatario = 'JEFE DE DEPARTAMENTO ';
    }
    usort($prospectos, function ($a, $b) {
        return ($a['oficina_id'] ?? 0) <=> ($b['oficina_id'] ?? 0);
    });


    $template_path = __DIR__ . "/memos/{$template_file}";
    if (!file_exists($template_path)) {
        throw new Exception("No se encontró la plantilla {$template_file}");
    }
    $anio2 = (new DateTime($fecha_memo ?: 'now'))->format('y');

    $templateProcessor = new TemplateProcessor($template_path);
    $templateProcessor->cloneRow('row_no', count($prospectos));
    $templateProcessor->setValue('folio_memo', $folio_memo); 
    $templateProcessor->setValue('fecha_memo', $fecha_formateada); 
    $templateProcessor->setValue('anio2', $anio2);
    $templateProcessor->setValue('destinatario', $datos_memo['destinatario'] ?? ' ');
    $templateProcessor->setValue('puesto_destinatario', $puesto_destinatario ?? ' ');
    $templateProcessor->setValue('oficina', $oficina ?? ' ');
    $templateProcessor->setValue('departamento', $datos_memo['departamento'] ?? ' ');
    $templateProcessor->setValue('cargo', $firmas['cargo'] ?? '');
    $templateProcessor->setValue('nombre_actuante', $firmas['nombre_actuante'] ?? '');
    $templateProcessor->setValue('iniciales', $firmas['iniciales'] ?? ''); 
    $templateProcessor->setValue('num_ordenes', $infoProspectos['num_ordenes'] ?? ''); 
    $templateProcessor->setValue('nombre_num', $infoProspectos['nombre_num'] ?? ''); 
    $templateProcessor->setValue('tipo_orden_carta', $infoProspectos['tipo_orden_carta'] ?? ''); 
    $contador = 1;
    foreach ($prospectos as $index => $p) {
        $fila = $index + 1;
        $templateProcessor->setValue("row_no#{$fila}", $contador);
        $templateProcessor->setValue("row_localidad#{$fila}", $p['ciudad_estado'] ?? '');
        $templateProcessor->setValue("row_orden#{$fila}", strtoupper($p['num_orden']) . '/' . $anio2);
        $contador++;
    }
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
function getCiudadEstadoByProspecto(PDO $conexion, int $prospectoId): string
{
    $sql = "SELECT CONCAT(IF(p.localidad != '' AND (m.nombre IS NULL OR TRIM(p.localidad) != TRIM(m.nombre)), CONCAT(' ', p.localidad, ','),''), IF(m.nombre != '', CONCAT(' ', m.nombre, ', '), ''),'SINALOA') AS municipio
    FROM siga_prospectos p LEFT JOIN siga_prospectos_municipios m ON p.municipio_id = m.municipio_id
    WHERE p.id = :id LIMIT 1";

    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':id', $prospectoId, PDO::PARAM_INT);
    $stmt->execute();

    return strtoupper($stmt->fetchColumn() ?: '');
}
function getMemoFolderByFecha(string $fecha): array
{
    $dt = new DateTime($fecha);
    $year = $dt->format('Y');
    $month = $dt->format('m');

    $basePath = __DIR__ . "/memos/{$year}/{$month}/";

    if (!is_dir($basePath)) {
        mkdir($basePath, 0777, true);
    }

    return [
        'path' => $basePath,
        'year' => $year,
        'month' => $month
    ];
}

$conexion = getConexion();
$data = json_decode(file_get_contents("php://input"), true);
$opcion = isset($data['opcion']) ? (int)$data['opcion'] : 0;

switch ($opcion) {
    case 1: // CONSULTAR MEMOS
        $consulta = "SELECT 
            m.id, m.id AS folio_memo, m.fecha AS fecha_memo, m.destinatario, m.asunto, m.oficina_id, m.departamento_id, o.nombre AS oficina_descripcion, 
            d.nombre AS departamento_descripcion, u.nombres AS usuario_nombre FROM memos m LEFT JOIN memos_cat_oficinas o ON o.id = m.oficina_id
        LEFT JOIN memos_cat_deptos d ON d.id = m.departamento_id LEFT JOIN memos_cat_usuarios u ON u.id = m.usuario_id ORDER BY m.id DESC";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data=$resultado->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    case 2: // ALTA DE MEMOS
        try {
            $conexion->beginTransaction();
            
            if (empty($data['oficina_id'])) {
                throw new Exception('La oficina es obligatoria');
            }
            $oficina_id = (int)$data['oficina_id'];
            $departamento_id = !empty($data['departamento_id'])? (int)$data['departamento_id']: null;
            if ($oficina_id === 4 && empty($departamento_id)) {
                throw new Exception('El departamento es obligatorio para la oficina seleccionada');
            }
            if ($oficina_id !== 4) {
                $departamento_id = null;
            }
            $fecha = fechaFrontendToDB($data['fecha'] ?? null);
            $destinatario = !empty($data['destinatario'])? strtoupper($data['destinatario']): null;
            $asunto = !empty($data['asunto'])? strtoupper($data['asunto']): null;
            $copias = isset($data['copias']) ? intval($data['copias']) : 1;
            $memosUsuarioId = 39; //ESE ES EL ID DEL USUARIO DE SIGA

            $consulta = "INSERT INTO memos (fecha, destinatario, oficina_id, departamento_id, asunto, usuario_id) 
                    VALUES (:fecha, :destinatario, :oficina_id, :departamento_id, :asunto, :usuario_id )";

            $stmt = $conexion->prepare($consulta);
            $stmt->bindParam(':fecha', $fecha);
            $stmt->bindParam(':destinatario', $data['destinatario']);
            $stmt->bindParam(':oficina_id', $data['oficina_id'], PDO::PARAM_INT);
            $stmt->bindParam(':departamento_id', $data['departamento_id'], PDO::PARAM_INT);
            $stmt->bindParam(':asunto', $data['asunto']);
            $stmt->bindParam(':usuario_id', $memosUsuarioId, PDO::PARAM_INT);
            $stmt->execute();
            $folio_memo = $conexion->lastInsertId();
            $prospectos = $data['prospectos'] ?? [];
            foreach ($prospectos as &$p) {
                if (!isset($p['ciudad_estado'])) {
                    $p['ciudad_estado'] = getCiudadEstadoByProspecto($conexion,(int)$p['id']);
                }
            }
            unset($p);
            $sqlUpdOrden = "UPDATE siga_prospectos_ordenes SET memo_id = :memo_id WHERE id_prospecto = :prospecto_id AND estatus = 1";
            $stmtUpdOrden = $conexion->prepare($sqlUpdOrden);
            foreach ($prospectos as $p) {
                $stmtUpdOrden->execute([':memo_id' => $folio_memo, ':prospecto_id' => $p['id']]);
            }
            $datos_memo = obtieneDatosMemo($conexion, $folio_memo);
            $firmas = getFirmas($conexion);
            $infoProspectos = analizarProspectos($prospectos);
            $templateProcessor = fillTemplateFromData($conexion, $prospectos, $infoProspectos, $folio_memo, $firmas, $fecha, $datos_memo);
            $folderInfo = getMemoFolderByFecha($fecha);
            $savePath  = $folderInfo['path'] . 'generado/';
            if (!is_dir($savePath)) {
                mkdir($savePath, 0777, true);
            }
            $fechaArchivo = str_replace('/', '-', $fecha);
            $baseName = 'memo_' . $folio_memo . '_' . $fechaArchivo; 
                $finalDocx = $savePath . $baseName . '.docx';
                $finalPdf  = $savePath . $baseName . '.pdf';
                $templateProcessor->saveAs($finalDocx);
                if (!convertDocxToPdf($finalDocx, $savePath)) {
                    throw new Exception("Error al convertir DOCX a PDF en memos.");
                }
                file_put_contents(__DIR__ . "/impresion.log","Backend está a punto de imprimir memo: $finalDocx\n",FILE_APPEND);
                
                $impreso = imprimirPDF($finalPdf, $copias);

                if (!$impreso) {
                    error_log("No se pudo imprimir el PDF del memo: $finalPdf");
                }
                $conexion->commit();
                $pdf_url = getBaseUrl() . "/memos/{$folderInfo['year']}/{$folderInfo['month']}/generado/{$baseName}.pdf";
                header('Content-Type: application/json');
                echo json_encode(['success' => true,'pdf_url' => $pdf_url,"impreso" => $impreso]);
        } catch (Exception $e) {
             $conexion->rollBack();
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'mensaje' => $e->getMessage()
            ]);
        }
        exit;
    case 3: // UPDATE MEMOS
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID del memo es requerido']);
            exit;
        }

        $consulta = "UPDATE memos SET oficina_id = :oficina_id, departamento_id = :departamento_id, destinatario = :destinatario, asunto = :asunto WHERE id = :id";
        $stmt = $conexion->prepare($consulta);
        $stmt->bindParam(':oficina_id', $data['oficina_id'], PDO::PARAM_INT);
        $stmt->bindParam(':departamento_id', $data['departamento_id'], PDO::PARAM_INT);
        $stmt->bindParam(':destinatario', $data['destinatario']);
        $stmt->bindParam(':asunto', $data['asunto']);
        $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            $data = ['success' => false, 'message' => 'No se realizaron cambios'];
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            $data = ['success' => true, 'message' => 'Memo actualizado correctamente'];
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
       
        exit;
    case 4: // REIMPRIMIR MEMOS
        try {
            if (empty($data['memo_id'])) {
                throw new Exception('ID del memorándum no recibido');
            }
            $memoId = (int)$data['memo_id'];
            $copias = isset($data['copias']) ? (int)$data['copias'] : 1;
            $datos_memo = obtieneDatosMemo($conexion, $memoId);
            if (!$datos_memo) {
                throw new Exception('Memorándum no encontrado');
            }

            $fecha = $datos_memo['fecha_memo'];
            $folio_memo = $datos_memo['folio_memo'];
            $sqlProspectos = "SELECT p.id, p.rfc, p.oficina_id, so.num_orden, so.num_oficio FROM siga_prospectos_ordenes so 
            INNER JOIN siga_prospectos p ON p.id = so.id_prospecto WHERE so.memo_id = :memo_id AND so.estatus = 1";
            $stmt = $conexion->prepare($sqlProspectos);
            $stmt->bindParam(':memo_id', $memoId, PDO::PARAM_INT);
            $stmt->execute();
            $prospectos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($prospectos as &$p) {
                $p['ciudad_estado'] = getCiudadEstadoByProspecto($conexion, (int)$p['id']);
            }
            unset($p);
            if (!$prospectos) {
                throw new Exception('El memo no tiene órdenes asociadas');
            }
            $firmas = getFirmas($conexion);
            $infoProspectos = analizarProspectos($prospectos);

            $templateProcessor = fillTemplateFromData($conexion, $prospectos, $infoProspectos, $folio_memo, $firmas, $fecha, $datos_memo);
            $folderInfo = getMemoFolderByFecha($fecha);
            $savePath  = $folderInfo['path'] . 'reimpresion/';
            if (!is_dir($savePath)) {
                mkdir($savePath, 0777, true);
            }
            $fechaArchivo = str_replace('-', '_', $fecha);
            $baseName = "memo_reimpresion_{$folio_memo}_{$fechaArchivo}";
            $finalDocx = $savePath . $baseName . '.docx';
            $finalPdf  = $savePath . $baseName . '.pdf';
            $templateProcessor->saveAs($finalDocx);

            if (!convertDocxToPdf($finalDocx, $savePath)) {
                throw new Exception('Error al convertir DOCX a PDF');
            }
            $impreso = imprimirPDF($finalPdf, $copias);
            echo json_encode([ 'success' => true, 'pdf_url' => getBaseUrl() . "/memos/{$folderInfo['year']}/{$folderInfo['month']}/reimpresion/{$baseName}.pdf", 'impreso' => $impreso]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
        }
        exit;
    case 5: // CONSULTAR DEPARTAMENTOS
        $consulta = "SELECT * FROM memos_cat_deptos";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data=$resultado->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);

        exit;
    case 6: // CONSULTAR DESTINATARIOS
        $consulta = "SELECT id, oficina_id, departamento_id, CONCAT(nombres,' ',apellido_paterno,' ',apellido_materno) AS nombre_completo  FROM memos_cat_destinatarios";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data=$resultado->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);

        exit;
    case 7: // CONSULTAR OFICINAS
        $consulta = "SELECT * FROM memos_cat_oficinas";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data=$resultado->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);

        exit;
    case 8: // CONSULTAR PROSPECTOS PARA MEMOS
        $consulta = "SELECT p.id, p.rfc, p.nombre, p.oficina_id, o.nombre AS oficina, p.estatus, so.num_orden, so.num_oficio FROM siga_prospectos p
                INNER JOIN siga_prospectos_ordenes so ON so.id_prospecto = p.id AND so.estatus = 1 AND so.memo_id IS NULL
                INNER JOIN siga_prospectos_oficinas o ON o.id = p.oficina_id WHERE p.estatus = 6 ORDER BY so.num_orden DESC";
        $resultado = $conexion->prepare($consulta);
        $resultado->execute();
        $data=$resultado->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);

        exit;
    case 9: // CONSULTAR MEMOS CON PERMISO DE REIMPRESIÓN
        try {
            $usuarioSistemaId = 39; // Usuario SIGA
            $sql = "SELECT m.id, m.id AS folio_memo, m.fecha AS fecha_memo, m.destinatario, m.asunto, m.oficina_id, m.departamento_id, 
            o.nombre AS oficina_descripcion, d.nombre AS departamento_descripcion, u.nombres AS usuario_nombre,
                    CASE WHEN m.usuario_id = :usuario_id AND EXISTS (SELECT 1 FROM siga_prospectos_ordenes so WHERE so.memo_id = m.id AND so.estatus = 1)
                        THEN 1 ELSE 0 END AS puede_reimprimir FROM memos m LEFT JOIN memos_cat_oficinas o ON o.id = m.oficina_id
                LEFT JOIN memos_cat_deptos d ON d.id = m.departamento_id LEFT JOIN memos_cat_usuarios u ON u.id = m.usuario_id ORDER BY m.id DESC";
            $stmt = $conexion->prepare($sql);
            $stmt->bindValue(':usuario_id', $usuarioSistemaId, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
        }
        exit;
    default:
        echo json_encode([
            'status' => false,
            'msg' => 'Opción no válida'
        ]);
        exit;
}
