<?php
include_once 'cors.php';
// Configurar CORS
// header("Access-Control-Allow-Origin: http://10.10.120.3:8080"); // Permite el origen específico
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Métodos permitidos
// header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Cabeceras permitidas

// // Manejar solicitudes OPTIONS (preflight request)
// if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
//     http_response_code(200);
//     exit();
// }

require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Obtener los datos enviados desde Vue.js (por POST)
$postData = json_decode(file_get_contents('php://input'), true);
$data = $postData['data'];

// Cargar el archivo de plantilla (machote)
$spreadsheet = IOFactory::load('../plantillas/Plantilla_Antecedente_ISN.xlsx');

// Seleccionar la hoja activa
$sheet = $spreadsheet->getActiveSheet();

// Cambiar el nombre de la hoja con el campo 'rfc'
$sheet->setTitle($data['rfc']);

// Insertar los datos en las celdas específicas
$sheet->setCellValue('E11', $data['rfc']); // Rellenar el nombre en la celda B1
$sheet->setCellValue('K11', $data['nombre']);  // Rellenar la edad en la celda B2
$sheet->setCellValue('E13', $data['domicilio_completo']);  // Rellenar la edad en la celda B2
$sheet->setCellValue('I14', $data['periodos']);  // Rellenar la edad en la celda B2
$sheet->setCellValue('C85', 'L.C.P. '. strtoupper($data['programador_nombre_completo']));  // Rellenar la edad en la celda B2
// Obtener el día, mes y año por separado
$dia = date("d");  // Día del mes (con ceros iniciales)
$mes = date("m");  // Mes numérico (con ceros iniciales)
$año = date("Y");  // Año completo (4 dígitos)
$sheet->setCellValue('W10', $dia);  // Rellenar la edad en la celda B2
$sheet->setCellValue('X10', $mes);  // Rellenar la edad en la celda B2
$sheet->setCellValue('Y10', $año);  // Rellenar la edad en la celda B2

// Crear un archivo Excel nuevo con los datos
$writer = new Xlsx($spreadsheet);

// Establecer el nombre del archivo, concatenando 'antecedente_' con el rfc
$fileName = 'antecedente_' . $data['rfc'] . '.xlsx';

// Establecer las cabeceras para la descarga del archivo
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Guardar el archivo modificado en la salida
$writer->save('php://output');
exit;
