<?php
include_once 'cors.php';
include_once 'conexion.php';

$objeto = new Conexion();
$conexion = $objeto->Conectar();

// 🔹 Entrada: JSON o GET/POST
$input = json_decode(file_get_contents("php://input"), true);
$datos = $input ?? $_REQUEST;

// 🔹 Variables
$opcion = isset($datos['opcion']) ? intval($datos['opcion']) : 0;
$estatus_prospecto = isset($datos['estatus_prospecto']) ? intval($datos['estatus_prospecto']) : 0;
$id = isset($datos['id']) ? $datos['id'] : null;


// Campos comunes
$rfc = $datos['rfc'] ?? '';
$nombre = $datos['nombre'] ?? '';
$calle= $datos['calle'] ?? '';
$num_exterior = $datos['num_exterior'] ?? '';
$num_interior = $datos['num_interior'] ?? '';
$domicilio = $datos['domicilio'] ?? '';
$colonia = $datos['colonia'] ?? '';
$cp = $datos['cp'] ?? '';
$localidad = $datos['localidad'] ?? '';
$municipio_id = $datos['municipio_id'] ?? '';
$giro = $datos['giro'] ?? '';
$oficina_id = $datos['oficina_id'] ?? '';
$fuente_id = $datos['fuente_id'] ?? '';
$periodos = $datos['periodos'] ?? '';
$antecedente_id = $datos['antecedente_id'] ?? '';
$impuesto_id = $datos['impuesto_id'] ?? '';
$determinado = $datos['determinado'] ?? '';
$programador_id = $datos['programador_id'] ?? '';
$retenedor = $datos['retenedor'] ?? '';
$representante_legal = $datos['representante_legal'] ?? '';
$cambio_domicilio = $datos['cambio_domicilio'] ?? '';
$domicilio_anterior = $datos['domicilio_anterior'] ?? '';
if (!empty($domicilio_anterior)) {
    $domicilio_anterior = mb_strtoupper($domicilio_anterior, 'UTF-8');
    $domicilio_anterior = str_replace('NO.', 'No.', $domicilio_anterior);
}
$notificador = $datos['notificador'] ?? '';
$fecha_acta = $datos['fecha_acta'] ?? '';
$origen_id = $datos['origen_id'] ?? '';
$observaciones = $datos['observaciones'] ?? '';
$estatus = $datos['estatus'] ?? '';

$data = [];

switch ($opcion) {
  case 1: // Consulta Dinámica por Estatus
    $consulta = "SELECT
                  a.*,
                  b.impuesto AS impuesto,
                  b.descripcion AS impuestos_descripcion,
                  d.usuario AS programador_descripcion,
                  d.nombre_completo AS programador_nombre_completo,
                  e.nombre AS oficina_descripcion,
                  g.nombre AS fuente_descripcion,
                  h.nombre AS estatus_descripcion,
                  j.nombre AS municipio_nombre,
                  CONCAT( 
                    IFNULL(a.calle, ''),
                    IF(a.num_exterior != '', CONCAT(' No. ', a.num_exterior), ''),
                    IF(a.num_interior != '', CONCAT(' INTERIOR ', a.num_interior), ''),
                    IF(a.colonia != '', CONCAT(', ', a.colonia), ''),
                    IF(a.cp != '', CONCAT(' C.P. ', a.cp), ''),
                    IF(
                        a.localidad != '' 
                        AND (j.nombre IS NULL OR TRIM(a.localidad) != TRIM(j.nombre)),
                        CONCAT(' ', a.localidad, ', '),
                        ''
                    ),
                    IF(j.nombre != '', CONCAT(' ', j.nombre, ', '), ''),
                    ' SINALOA'
                  ) AS domicilio_completo,
                  CONCAT(
                      IFNULL(a.calle, ''),
                      IF(a.num_exterior != '', CONCAT(' No. ', a.num_exterior), ''),
                      IF(a.num_interior != '', CONCAT(', INTERIOR ', a.num_interior), '')
                  ) AS calle_numero,
                  CONCAT(
                      IF(a.cp != '', CONCAT('C.P. ', a.cp, ' '), ''),
                      IF(a.colonia != '', CONCAT(' ', a.colonia), ''),
                      IFNULL(a.localidad, '')
                  ) AS cp_municipio,
                  CONCAT(
                      IF(a.localidad != '', CONCAT(a.localidad), ''),
                      IF(j.nombre != '', CONCAT(', ', j.nombre), ''),
                      ' SINALOA'
                  ) AS ciudad_estado,
                  CONCAT(
                      IF(
                        a.localidad != '' 
                        AND (j.nombre IS NULL OR TRIM(a.localidad) != TRIM(j.nombre)),
                        CONCAT(' ', a.localidad, ', '),
                        ''
                    ),
                    IF(j.nombre != '', CONCAT(' ', j.nombre, ', '), ''),
                    ' SINALOA'
                  ) AS municipio_estado,
                  f.descripcion AS antecedente_descripcion
                FROM siga_prospectos AS a
                LEFT JOIN siga_prospectos_impuestos AS b ON a.impuesto_id = b.id
                LEFT JOIN siga_prospectos_programadores AS d ON a.programador_id = d.id
                LEFT JOIN siga_prospectos_oficinas AS e ON a.oficina_id = e.id
                LEFT JOIN siga_prospectos_antecedentes AS f ON a.antecedente_id = f.id
                LEFT JOIN siga_prospectos_fuentes AS g ON a.fuente_id = g.id
                LEFT JOIN siga_prospectos_estatus_prospectos AS h ON a.estatus = h.id
                LEFT JOIN siga_prospectos_municipios AS j ON a.municipio_id = j.municipio_id
                ";

    $params = [];
    if ($estatus_prospecto == 0) {
        $consulta .= " WHERE a.estatus > 0";
    } else {
        $consulta .= " WHERE a.estatus = ?";
        $params[] = $estatus_prospecto;
    }

    $stmt = $conexion->prepare($consulta);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    break;


  case 2: // Alta
    // Verificar si el RFC ya existe con antecedente 6 o 7
    $consulta_verificacion = "SELECT antecedente_id FROM siga_prospectos WHERE rfc = ? AND antecedente_id IN (6, 7)";
    $stmt_verificacion = $conexion->prepare($consulta_verificacion);
    $stmt_verificacion->execute([$rfc]);
    $existente = $stmt_verificacion->fetch(PDO::FETCH_ASSOC);

    if ($existente) {
        $antecedente_id_existente = $existente['antecedente_id'];
        $mensaje = "No es posible crear el prospecto. El RFC ya se encuentra ";
        if ($antecedente_id_existente == 6) {
            $mensaje .= "EN PROCESO.";
        } else { // antecedente_id == 7
            $mensaje .= "como NO LOCALIZADO.";
        }
        $data = ['success' => false, 'mensaje' => $mensaje];
        break;
    }
    // Convertir DD/MM/YYYY a YYYY-MM-DD para la base de datos
    if (!empty($fecha_acta)) {
        $dateObj = DateTime::createFromFormat('d/m/Y', $fecha_acta);
        if ($dateObj) {
            $fecha_acta = $dateObj->format('Y-m-d');
        }
    } else {
        $fecha_acta = null;
    }

    $consulta = "INSERT INTO siga_prospectos
      (rfc, nombre, calle, num_exterior, num_interior, colonia, cp, localidad, municipio_id, oficina_id, fuente_id,  giro, periodos,
      antecedente_id, impuesto_id, determinado, programador_id, representante_legal, retenedor, origen_id, observaciones, estatus, 
      cambio_domicilio, domicilio_anterior, notificador, fecha_acta)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($consulta);
    $stmt->execute([
      $rfc, $nombre, $calle, $num_exterior, $num_interior, $colonia, $cp, $localidad, $municipio_id, $oficina_id, 
      $fuente_id, $giro, $periodos, $antecedente_id, $impuesto_id, 
      $determinado, $programador_id,  $representante_legal, $retenedor, $origen_id, $observaciones, $estatus, $cambio_domicilio, $domicilio_anterior, $notificador, $fecha_acta
    ]);
    $data = ['mensaje' => 'Registro insertado correctamente'];
    $last_id = $conexion->lastInsertId();
    $data = ['success' => true, 'id' => $last_id, 'mensaje' => 'Registro insertado correctamente'];
    break;

  case 3: // Cambios
    // Convertir DD/MM/YYYY a YYYY-MM-DD para la base de datos
    if (!empty($fecha_acta)) {
        $dateObj = DateTime::createFromFormat('d/m/Y', $fecha_acta);
        if ($dateObj) {
            $fecha_acta = $dateObj->format('Y-m-d');
        }
    } else {
        $fecha_acta = null;
    }
    if (strlen($rfc) === 13) {
        $representante_legal = '';
    }
    $consulta = "UPDATE siga_prospectos SET
      rfc = ?, nombre = ?, calle = ?, num_exterior = ?, num_interior = ?, colonia = ?, cp = ?, localidad = ?, municipio_id = ?, oficina_id = ?, 
      fuente_id = ?, giro = ?, periodos = ?, antecedente_id = ?, impuesto_id = ?, determinado = ?, programador_id = ?, representante_legal = ?, 
      retenedor = ?, origen_id = ?, observaciones = ?, estatus = ?, cambio_domicilio = ?, domicilio_anterior = ?, notificador = ?, fecha_acta = ?
      WHERE id = ?";
    $stmt = $conexion->prepare($consulta);
    $stmt->execute([      
      $rfc, $nombre, $calle, $num_exterior, $num_interior, $colonia, $cp, $localidad, $municipio_id, $oficina_id, 
      $fuente_id, $giro, $periodos, $antecedente_id, $impuesto_id, $determinado, $programador_id,  $representante_legal, 
      $retenedor, $origen_id, $observaciones, $estatus, $cambio_domicilio, $domicilio_anterior, $notificador, $fecha_acta, $id
    ]);
    $data = ['mensaje' => 'Registro actualizado correctamente'];
    break;

  case 4: // Baja lógica
    $consulta = "UPDATE siga_prospectos SET estatus = 0 WHERE id = ?";
    $stmt = $conexion->prepare($consulta);
    $stmt->execute([$id]);
    $data = ['mensaje' => 'Registro eliminado (baja lógica)'];
    break;

  case 5: // Cambio de Estatus
    $estatus_nuevo = $datos['estatus'] ?? null;
    $observaciones = $datos['observaciones'] ?? null;
    $antecedente_id = $datos['antecedente_id'] ?? null;
    $id = $datos['id'] ?? null;

    $consulta = "UPDATE siga_prospectos SET estatus = ?";

    $params = [$estatus_nuevo];

    if ($estatus_nuevo == 4) {
        $consulta .= ", fecha_comite = CURDATE()";
    } 
    elseif ($estatus_nuevo == 5) {
        $consulta .= ", fecha_autorizacion = CURDATE()";
    } 
    elseif ($estatus_nuevo == 6) {
        $consulta .= ", fecha_emision = CURDATE()";
    } 
    elseif ($estatus_nuevo == 7) {
        $consulta .= ", observaciones = ?, antecedente_id = ?";
        $params[] = $observaciones;
        $params[] = $antecedente_id;
    }

    $consulta .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $conexion->prepare($consulta);
    $stmt->execute($params);

    $data = ['mensaje' => 'Estatus actualizado correctamente'];
    break;


  case 6: // Eliminar periodos por prospecto_id
    if (isset($datos['prospecto_id'])) {
        $prospecto_id = $datos['prospecto_id'];
        $sql = "DELETE FROM siga_prospectos_periodos WHERE prospecto_id = :prospecto_id";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':prospecto_id', $prospecto_id);
        $stmt->execute();
        $data = ["success" => true, "message" => "Periodos eliminados correctamente."];
    } else {
        $data = ["error" => "prospecto_id no proporcionado para eliminar periodos."];
    }
    break;

  case 7: // Insertar un nuevo periodo
    if (isset($datos['prospecto_id']) && isset($datos['fecha_inicial']) && isset($datos['fecha_final'])) {
        $prospecto_id = $datos['prospecto_id'];
        $fecha_inicial_ddmmyyyy = $datos['fecha_inicial'];
        $fecha_final_ddmmyyyy = $datos['fecha_final'];

        // Convertir DD/MM/YYYY a YYYY-MM-DD para la base de datos
        $fecha_inicial_ymd = DateTime::createFromFormat('d/m/Y', $fecha_inicial_ddmmyyyy)->format('Y-m-d');
        $fecha_final_ymd = DateTime::createFromFormat('d/m/Y', $fecha_final_ddmmyyyy)->format('Y-m-d');

        $sql = "INSERT INTO siga_prospectos_periodos (fecha_inicial, fecha_final, prospecto_id, status) VALUES (?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            $fecha_inicial_ymd, $fecha_final_ymd, $prospecto_id, 1
        ]);
        $data = ["success" => true, "message" => "Periodo insertado correctamente."];
    } else {
        $data = ["error" => "Datos incompletos para insertar periodo."];
    }
    break;
  default:
    $data = ['error' => 'Opción inválida o no enviada'];
    break;
}

header('Content-Type: application/json');
echo json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = null;
