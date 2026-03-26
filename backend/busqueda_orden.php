<?php
include_once 'cors.php';
include_once 'conexion.php';
$objeto = new Conexion();
$conexion = $objeto->Conectar();

$orden = isset($_GET['orden']) && trim($_GET['orden']) != '' ? trim($_GET['orden']) : null;

if (!$orden) {
    echo json_encode(null);
    exit;
}

$sql = "SELECT 
            id,
            orden,
            fecha_orden,
            rfc,
            nombre,
            domicilio,
            oficina
        FROM 
            vi_emitidas
        WHERE orden LIKE :orden
        LIMIT 1";

try {
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':orden', $orden, PDO::PARAM_STR);
    $stmt->execute();

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo $data ? json_encode($data) : json_encode(null);
    
} catch (PDOException $e) {
    echo json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
}
?>



