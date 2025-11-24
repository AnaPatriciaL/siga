<?php
ob_start(); // Inicia el buffer de salida para capturar cualquier salida no deseada.

include_once 'cors.php';
include_once 'conexion.php';
require __DIR__ . '/vendor/autoload.php'; // Esta es la ruta correcta una vez instalado Composer en la carpeta backend.

// --- INICIO DE CORRECCIÓN ---
// Asegurar que la librería TCPDF esté cargada y configurada.
require_once __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';
\PhpOffice\PhpWord\Settings::setPdfRendererPath(__DIR__ . '/vendor/tecnickcom/tcpdf');
\PhpOffice\PhpWord\Settings::setPdfRendererName('TCPDF');
// --- FIN DE CORRECCIÓN ---
use PhpOffice\PhpWord\TemplateProcessor;

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
    case 4: // Opción para contar órdenes generadas y pendientes
        try {
            $prospecto_ids = isset($data['prospecto_ids']) ? $data['prospecto_ids'] : [];

            if (empty($prospecto_ids)) {
                echo json_encode(['ordenes_generadas_count' => 0, 'ordenes_pendientes_count' => 0]);
                exit;
            }

            $objeto = new Conexion();
            $conexion = $objeto->Conectar();
            
            // Crear placeholders para la consulta IN (?, ?, ...)
            $placeholders = implode(',', array_fill(0, count($prospecto_ids), '?'));

            $consulta = "SELECT DISTINCT id_prospecto FROM siga_prospectosie_ordenes WHERE id_prospecto IN ($placeholders)";
            $stmt = $conexion->prepare($consulta);
            $stmt->execute($prospecto_ids);
            $ids_con_orden = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Obtiene un array plano de IDs

            $ordenes_generadas_count = count($ids_con_orden);
            $total_prospectos = count($prospecto_ids);
            $ordenes_pendientes_count = $total_prospectos - $ordenes_generadas_count;

            header('Content-Type: application/json');
            echo json_encode([
                'ordenes_generadas_count' => $ordenes_generadas_count, // Corregido
                'ordenes_pendientes_count' => $ordenes_pendientes_count,
                'ids_con_orden' => $ids_con_orden // Nuevo: Array con los IDs que tienen orden
            ]);

        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(['error' => 'Error al contar las órdenes: ' . $e->getMessage()]);
        }
        break;

    case 5: // Opción para VISTA PREVIA
    $prospecto_id = $data['prospecto']['id'];

    try {
        $objeto = new Conexion();
        $conexion = $objeto->Conectar();

        // 1. Obtener datos del prospecto
        $consulta_prospecto = "SELECT
                          a.*,
                          b.impuesto AS impuesto,
                          b.descripcion AS impuestos_descripcion,
                          d.usuario AS programador_descripcion,
                          d.nombre_completo AS programador_nombre_completo,
                          e.nombre AS oficina_descripcion,
                          e.grupo AS oficina_grupo,
                          e.fraccion AS oficina_fraccion,
                          e.domicilio AS oficina_domicilio,
                          e.telefono AS oficina_telefono,
                          g.nombre AS fuente_descripcion,
                          h.nombre AS estatus_descripcion,
                          j.nombre AS municipio_nombre,
                          k.art_retenedor,
                          k.sujeto_retenedor,
                          CONCAT(
                              IFNULL(a.calle, ''),
                              IF(a.num_exterior != '', CONCAT(', ', a.num_exterior), ''),
                              IF(a.num_interior != '', CONCAT(', ', a.num_interior), ''),
                              IF(a.colonia != '', CONCAT(', ', a.colonia), ''),
                              IF(a.cp != '', CONCAT(' C.P. ', a.cp), ''),
                              IF(a.localidad != '', CONCAT(', ', a.localidad), '')
                          ) AS domicilio_completo,
                          CONCAT(
                              IF(a.localidad != '', CONCAT(a.localidad), ''),
                              IF(j.nombre != '', CONCAT(', ', j.nombre), ''),
                              ' SINALOA'
                          ) AS ciudad_estado,
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
        $stmt = $conexion->prepare($consulta_prospecto);
        $stmt->execute([$prospecto_id]);
        $prospecto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prospecto) {
            throw new Exception("No se encontró el prospecto con ID: " . $prospecto_id);
        }

        // 2. Verificar si ya existe orden
        $stmt_orden = $conexion->prepare("SELECT * FROM siga_prospectosie_ordenes WHERE id_prospecto = ?");
        $stmt_orden->execute([$prospecto_id]);
        $orden_existente = $stmt_orden->fetch(PDO::FETCH_ASSOC);

        if ($orden_existente) {
            $stmt_folio = $conexion->prepare("SELECT * FROM siga_prospectosie_folios_oficios WHERE num_folio = ?");
            $stmt_folio->execute([$orden_existente['num_oficio']]);
            $folio_oficio = $stmt_folio->fetch(PDO::FETCH_ASSOC);
        } else {
            $folio_oficio = ['num_folio' => 'XXXX', 'anio' => date('Y')];
        }

        // 3. Crear plantilla DOCX
        $templateProcessor = new TemplateProcessor(__DIR__ . '/formatos/ISN.docx');

        $templateProcessor->setValue('num_folio', $folio_oficio['num_folio']);
            $templateProcessor->setValue('anio', $folio_oficio['anio']);
            $templateProcessor->setValue('representante_legal', $prospecto['representante_legal']);
            $templateProcessor->setValue('rfc', $prospecto['rfc']);
            $templateProcessor->setValue('nombre', $prospecto['nombre']);
            $templateProcessor->setValue('domicilio_completo', $prospecto['domicilio_completo']);
            $templateProcessor->setValue('calle_numero', $prospecto['calle_numero']);
            $templateProcessor->setValue('colonia', $prospecto['colonia']);
            $templateProcessor->setValue('ciudad_estado', $prospecto['ciudad_estado']);
            $templateProcessor->setValue('periodos', $prospecto['periodos']);
            $templateProcessor->setValue('impuesto', $prospecto['impuesto']);
            $templateProcessor->setValue('determinado', number_format($prospecto['determinado'], 2));
            $templateProcessor->setValue('oficina_descripcion', $prospecto['oficina_descripcion']);
            $templateProcessor->setValue('oficina_grupo', $prospecto['oficina_grupo']);
            $templateProcessor->setValue('oficina_fraccion', $prospecto['oficina_fraccion']);
            $templateProcessor->setValue('oficina_domicilio', $prospecto['oficina_domicilio']);
            $templateProcessor->setValue('oficina_telefono', $prospecto['oficina_telefono']);
            $templateProcessor->setValue('art_retenedor', $prospecto['art_retenedor']);
            $templateProcessor->setValue('sujeto_retenedor', $prospecto['sujeto_retenedor']);

        // --- INICIO DE CORRECCIÓN ---
        // 3. Obtener los datos del personal actuante para las firmas
        $consulta_personal = "SELECT id_actuante, nombre AS nombre_actuante, cargo, iniciales, estatus FROM siga_prospectosie_personal_actuante";
        $stmt_personal = $conexion->prepare($consulta_personal);
        $stmt_personal->execute();
        $personal_actuante = $stmt_personal->fetchAll(PDO::FETCH_ASSOC);

        $iniciales = [];
        foreach ($personal_actuante as $persona) {
            if ($persona['estatus'] == 1) { // Asegurarse que el tipo de dato es correcto
                if ($persona['id_actuante'] == 1) { // El firmante principal
                    $templateProcessor->setValue('cargo', $persona['cargo']);
                    $templateProcessor->setValue('nombre_actuante', $persona['nombre_actuante']);
                } else {
                    $iniciales[] = $persona['iniciales'];
                }
            }
        }
        $templateProcessor->setValue('iniciales', implode(' / ', $iniciales));
        // --- FIN DE CORRECCIÓN ---

        // 4. Guardar temporal DOCX
        $tmpDocx = tempnam(sys_get_temp_dir(), 'VISTA_PREVIA_');
        $templateProcessor->saveAs($tmpDocx);

        // 5. Convertir DOCX → PDF (usando el renderizador global TCPDF)

        $phpWord = \PhpOffice\PhpWord\IOFactory::load($tmpDocx);
        $tmpPdf = tempnam(sys_get_temp_dir(), 'VISTA_PREVIA_') . '.pdf';

        $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
        $pdfWriter->save($tmpPdf);

        // 6. Enviar PDF como respuesta (para <embed>)
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="vista_previa.pdf"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        readfile($tmpPdf);

        // 7. Borrar archivos temporales
        unlink($tmpDocx);
        unlink($tmpPdf);
        exit;

    } catch (Exception $e) {
        header("Content-Type: text/plain");
        echo "Error al generar PDF:\n" . $e->getMessage() . "\n\n";
        echo $e->getTraceAsString();
    }
    break;

    default: // Comportamiento por defecto: generar la orden (anteriormente era todo el archivo)
        $prospecto_id = $data['prospecto']['id'];
        $id_generador = $data['usuario_id']; // Recibimos el ID del usuario que genera

        try {
            // 1. Conectar a la base de datos
            $objeto = new Conexion();
            $conexion = $objeto->Conectar();
            $conexion->beginTransaction();

            // 2. Obtener los datos completos del prospecto
            $consulta_prospecto = "SELECT
                          a.*,
                          b.impuesto AS impuesto,
                          b.descripcion AS impuestos_descripcion,
                          d.usuario AS programador_descripcion,
                          d.nombre_completo AS programador_nombre_completo,
                          e.nombre AS oficina_descripcion,
                          e.grupo AS oficina_grupo,
                          e.fraccion AS oficina_fraccion,
                          e.domicilio AS oficina_domicilio,
                          e.telefono AS oficina_telefono,
                          g.nombre AS fuente_descripcion,
                          h.nombre AS estatus_descripcion,
                          j.nombre AS municipio_nombre,
                          k.art_retenedor,
                          k.sujeto_retenedor,
                          CONCAT(
                              IFNULL(a.calle, ''),
                              IF(a.num_exterior != '', CONCAT(', ', a.num_exterior), ''),
                              IF(a.num_interior != '', CONCAT(', ', a.num_interior), ''),
                              IF(a.colonia != '', CONCAT(', ', a.colonia), ''),
                              IF(a.cp != '', CONCAT(' C.P. ', a.cp), ''),
                              IF(a.localidad != '', CONCAT(', ', a.localidad), '')
                          ) AS domicilio_completo,
                          CONCAT(
                              IFNULL(a.calle, ''),
                              IF(a.num_exterior != '', CONCAT(', ', a.num_exterior), ''),
                              IF(a.num_interior != '', CONCAT(', ', a.num_interior), '')
                          ) AS calle_numero,
                          CONCAT(
                              IF(a.localidad != '', CONCAT( a.localidad), ''),
                              IF(j.nombre != '', CONCAT(', ', j.nombre), ''),
                              ' SINALOA'
                          ) AS ciudad_estado,
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
            
            $stmt = $conexion->prepare($consulta_prospecto);
            $stmt->execute([$prospecto_id]);
            $prospecto = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$prospecto) {
                throw new Exception("No se encontró el prospecto con ID: " . $prospecto_id);
            }
            
            // 3. Verificar si ya existe una orden para este prospecto
            $consulta_orden_existente = "SELECT * FROM siga_prospectosie_ordenes WHERE id_prospecto = ?";
            $stmt_orden = $conexion->prepare($consulta_orden_existente);
            $stmt_orden->execute([$prospecto_id]);
            $orden_existente = $stmt_orden->fetch(PDO::FETCH_ASSOC);

            $folio_oficio = null;

            if ($orden_existente) {
                // Si ya existe, usar el folio de esa orden
                $consulta_folio = "SELECT * FROM siga_prospectosie_folios_oficios WHERE num_folio = ?"; // Corregido
                $stmt_folio = $conexion->prepare($consulta_folio);
                $stmt_folio->execute([$orden_existente['num_oficio']]);
                $folio_oficio = $stmt_folio->fetch(PDO::FETCH_ASSOC);
            } else {
                // Si no existe, obtener y marcar un nuevo folio
                $consulta_folio_nuevo = "SELECT * FROM siga_prospectosie_folios_oficios WHERE estatus = 0 ORDER BY num_folio ASC LIMIT 1 FOR UPDATE";
                $stmt_folio_nuevo = $conexion->prepare($consulta_folio_nuevo);
                $stmt_folio_nuevo->execute();
                $folio_oficio = $stmt_folio_nuevo->fetch(PDO::FETCH_ASSOC);

                if ($folio_oficio) {
                    // Marcar como usado
                    $update_folio = "UPDATE siga_prospectosie_folios_oficios SET estatus = 1 WHERE id = ?";
                    $stmt_update = $conexion->prepare($update_folio);
                    $stmt_update->execute([$folio_oficio['id']]);

                    // Insertar la nueva orden en la tabla de órdenes
                    $insert_orden = "INSERT INTO siga_prospectosie_ordenes (id_prospecto, num_oficio, num_orden, grupo, fraccion, id_programador, id_generador, fecha_orden, estatus, art_retenedor, sujeto_retenedor) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?)";
                    $stmt_insert_orden = $conexion->prepare($insert_orden);
                    $stmt_insert_orden->execute([
                        $prospecto_id,
                        $folio_oficio['num_folio'],
                        $folio_oficio['num_folio'], // Asumiendo que num_orden es el num_folio
                        $prospecto['oficina_grupo'],
                        $prospecto['oficina_fraccion'],
                        $prospecto['programador_id'],
                        $id_generador,
                        1, // Estatus inicial 'Generada'
                        $prospecto['art_retenedor'],
                        $prospecto['sujeto_retenedor']
                    ]);
                } else {
                    throw new Exception("No hay folios de oficio disponibles.");
                }
            }

            // 4. Cargar la plantilla y reemplazar valores
            $templateProcessor = new TemplateProcessor(__DIR__ . '/formatos/ISN.docx');
            
            // Reemplazar los placeholders con los datos del prospecto
            $templateProcessor->setValue('num_folio', $folio_oficio['num_folio']);
            $templateProcessor->setValue('anio', $folio_oficio['anio']);
            $templateProcessor->setValue('representante_legal', $prospecto['representante_legal']);
            $templateProcessor->setValue('rfc', $prospecto['rfc']);
            $templateProcessor->setValue('nombre', $prospecto['nombre']);
            $templateProcessor->setValue('domicilio_completo', $prospecto['domicilio_completo']);
            $templateProcessor->setValue('calle_numero', $prospecto['calle_numero']);
            $templateProcessor->setValue('colonia', $prospecto['colonia']);
            $templateProcessor->setValue('ciudad_estado', $prospecto['ciudad_estado']);
            $templateProcessor->setValue('periodos', $prospecto['periodos']);
            $templateProcessor->setValue('impuesto', $prospecto['impuesto']);
            $templateProcessor->setValue('determinado', number_format($prospecto['determinado'], 2));
            $templateProcessor->setValue('oficina_descripcion', $prospecto['oficina_descripcion']);
            $templateProcessor->setValue('oficina_grupo', $prospecto['oficina_grupo']);
            $templateProcessor->setValue('oficina_fraccion', $prospecto['oficina_fraccion']);
            $templateProcessor->setValue('oficina_domicilio', $prospecto['oficina_domicilio']);
            $templateProcessor->setValue('oficina_telefono', $prospecto['oficina_telefono']);
            $templateProcessor->setValue('art_retenedor', $prospecto['art_retenedor']);
            $templateProcessor->setValue('sujeto_retenedor', $prospecto['sujeto_retenedor']);

            // 3. Obtener los datos del personal actuante para las firmas
            $consulta_personal = "SELECT id_actuante, nombre AS nombre_actuante, cargo, iniciales, estatus FROM siga_prospectosie_personal_actuante";
            $stmt_personal = $conexion->prepare($consulta_personal);
            $stmt_personal->execute();
            $personal_actuante = $stmt_personal->fetchAll(PDO::FETCH_ASSOC);

            $iniciales = [];
            foreach ($personal_actuante as $persona) {
                if ($persona['estatus'] === 1) {
                    if ($persona['id_actuante'] === 1) {
                        $templateProcessor->setValue('cargo', $persona['cargo']);
                        $templateProcessor->setValue('nombre_actuante', $persona['nombre_actuante']);
                    } else {
                        $iniciales[] = $persona['iniciales'];
                    }
                }
            }
            $templateProcessor->setValue('iniciales', implode(' / ', $iniciales));

            $conexion->commit(); // Confirmar la transacción si todo fue exitoso

            $fileName = 'ISN_' . $prospecto['rfc'] . '.docx'; // Cambiado a .docx
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Access-Control-Expose-Headers: Content-Disposition');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            $templateProcessor->saveAs('php://output');
            ob_end_flush(); // Envía el buffer de salida (el archivo) al navegador.

        } catch (Exception $e) {
            ob_end_clean(); // Limpiar buffer en caso de error
            error_log('Error al generar el documento: ' . $e->getMessage());
            header("HTTP/1.1 500 Internal Server Error");
            echo 'Error al generar el documento: ',  $e->getMessage(), "\n";
        }
        break;
}
