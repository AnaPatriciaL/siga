<?php
ob_start(); // Inicia el buffer de salida para capturar cualquier salida no deseada.

include_once 'cors.php';
include_once 'conexion.php';

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
$datos = json_decode(file_get_contents("php://input"), true);
$opcion = $datos['opcion'] ?? null; 

switch ($opcion){
    case 1: // Asignar listado de comité
        $prospectos = $datos['prospectos'] ?? [];
        if (empty($prospectos)) {
            echo json_encode([
                'status' => false,
                'msg' => 'No se recibieron prospectos'
            ]);
            exit;
        }

        $anio = date('y');
        $sqlUltimo = "SELECT folio_listado FROM siga_prospectos WHERE folio_listado LIKE ? ORDER BY folio_listado DESC LIMIT 1";
        $like = "%/$anio";
        $stmtUltimo = $conexion->prepare($sqlUltimo);
        $stmtUltimo->execute([$like]);
        $ultimoFolio = $stmtUltimo->fetchColumn();

        if ($ultimoFolio) {
            $consecutivo = (int) substr($ultimoFolio, 0, 4);
            $consecutivo++;
        } else {
            $consecutivo = 1;
        }

        $folioNuevo = str_pad($consecutivo, 4, '0', STR_PAD_LEFT) . '/' . $anio;
        $conexion->beginTransaction();
        $sqlUpdate = "UPDATE siga_prospectos SET folio_listado = ? WHERE id = ?";
        $stmtUpdate = $conexion->prepare($sqlUpdate);

        foreach ($prospectos as $p) {
            $stmtUpdate->execute([
                $folioNuevo,
                $p['id']
            ]);
        }
        $conexion->commit();

        echo json_encode([
            'status' => true,
            'folio' => $folioNuevo,
            'msg' => 'Listado asignado correctamente'
        ]);
        break;
    default:
        echo json_encode([
            'status' => false,
            'msg' => 'Opción no válida'
        ]);
        exit;
}