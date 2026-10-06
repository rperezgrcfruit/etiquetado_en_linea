<?php
require 'vendor/autoload.php';
require 'packing/config/db.php'; // Aquí tienes tu conexión PDO a MariaDB
use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Recibimos la variable dinámica (ej: por GET o POST)
//$numeroProceso = $_GET['proceso'] ?? 'C1-2026';

try {
    // 2. Conexión directa a tu base de datos MariaDB
    //$pdo = new PDO("mysql:host=localhost;dbname=grcfruit_grc;charset=utf8mb4", "tu_usuario", "tu_password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. Tu consulta SQL real y dinámica
    // Usamos prepare para evitar inyecciones SQL
    $sql = "SELECT folio, embalaje, calibre, kilos 
            FROM existencia_calibrado 
            WHERE n_proceso = :proceso 
            ORDER BY folio ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['proceso' => $numeroProceso]);
    
    // 4. ¡ADIÓS XML! Guardamos todo directamente en un Array Asociativo
    $datosReporte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificamos que la consulta trajo datos
    if (count($datosReporte) === 0) {
        die("No hay folios ingresados para este proceso.");
    }

} catch(PDOException $e) {
    die("Error de conexión o consulta: " . $e->getMessage());
}

// 5. Cargamos la plantilla HTML pasándole el arreglo de datos
ob_start();
require 'plantilla_pdf.php'; // En este archivo vive tu HTML
$html = ob_get_clean();

// 6. Generamos el PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 7. Lo mostramos en pantalla
$dompdf->stream("Reporte_".$numeroProceso.".pdf", array("Attachment" => false));
?>