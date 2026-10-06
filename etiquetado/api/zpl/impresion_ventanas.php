<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require $_SERVER['DOCUMENT_ROOT'] . '/packing/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/etiquetado/config/config.php';

$temporada = TEMPORADA_ACTUAL;

// Capturamos el JSON entrante
$data = json_decode(file_get_contents('php://input'), true);

$sql = "SELECT * FROM existencia_exportacion WHERE folio = :folio AND temporada = :temporada AND empresa = :empresa";

$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':folio' => $data['folio'] ?? '',
  ':temporada' => $temporada,
  ':empresa' => $data['empresa'] ?? $_SESSION['empresa'] ?? ''
]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

// Validamos que el folio realmente exista antes de continuar
if (!$result) {
    http_response_code(404);
    echo "Error: Folio no encontrado.";
    exit;
}

$tipoPallet = $result['tipo_pallet'];

if ($tipoPallet === 'COMPLETO') {
    // ---------------------------------------------------------
    // RUTA 1: IMPRESIÓN DIRECTA A ZEBRA ZPL
    // ---------------------------------------------------------
    function z($v) {
        return str_replace(['^', '~', "\n", "\r"], ' ', $v ?? '');
    }

    $folio    = z($result['folio']);
    $empresa   = z($result['empresa']);
    $especie   = z($result['especie']);
    $variedad  = z($result['variedad']);
    
    // Formateo de fecha
    $fecha_raw = z($result['fecha embalaje']);
    if (!empty($fecha_raw)) {
        $fecha_objeto = new DateTime($fecha_raw);
        $fecha = $fecha_objeto->format('d/m/Y');
    } else {
        $fecha = '';
    }
    
    $productor = z($result['productor']);
    $calibre   = z($result['calibre']);
    $etiqueta  = z($result['etiqueta']);
    $cajas      = z($result['cajas']);
    $categoria  = z($result['categoria']);

    if ($empresa === 'FMS SPA') {
        $categoria = 'CAT 1';
    }

    $zpl = "^XA\n";
    $zpl .= "^PW800";
    $zpl .= "^LL2400";
    $zpl .= "^GB780,2300,4^FS";
    $zpl .= "^GB780,1200,4^FS";
    $zpl .= "^GB500,2300,4^FS";
    $zpl .= "^GB700,2300,4^FS";
    $zpl .= "^GB700,500,4^FS";
    $zpl .= "^GB300,1200,4^FS";
    $zpl .= "^FWR";
    $zpl .= "^FO700,200^A0,50,50^FD$empresa^FS";
    $zpl .= "^FO600,200^A0,50,50^FDEspecie^FS";
    $zpl .= "^FO500,200^A0,60,60^FD$especie^FS";
    $zpl .= "^FO600,600^A0,50,50^FDVariedad^FS";
    $zpl .= "^FO500,600^A0,60,60^FD$variedad^FS";
    $zpl .= "^FO400,200^A0,50,50^FDCategoria^FS";
    $zpl .= "^FO300,200^A0,60,60^FD$categoria^FS";
    $zpl .= "^FO400,700^A0,50,50^FDCalibre^FS";
    $zpl .= "^FO300,700^A0,60,60^FD$calibre^FS";
    $zpl .= "^FO200,200^A0,50,50^FDProductor^FS";
    $zpl .= "^FO120,200^A0,50,50^FD$productor^FS";
    $zpl .= "^FO200,700^A0,50,50^FDCajas^FS";
    $zpl .= "^FO120,700^A0,50,50^FD$cajas^FS";
    $zpl .= "^FO700,1700^A0,50,50^FDRECIBIDOR^FS";
    $zpl .= "^FO458,1600^A0,40,40^FDNumero de Pallet^FS";
    $zpl .= "^FO250,1400^A0,140,140^FD$folio^FS";
    $zpl .= "^FO30,1200";
    $zpl .= "^BQN,2,3";
    $zpl .= "^FO170,1450^BCR,100,Y,N,N";
    $zpl .= "^FD$folio^FS";
    $zpl .= "^XZ";

    // Enviar a Zebra por TCP 9100
    $printerIp = "192.168.1.80";
    $printerPort = 9100;
    $copias = 4;
    $errno = 0;
    $errstr = '';

    for ($i = 0; $i < $copias; $i++) {
        $fp = @fsockopen($printerIp, $printerPort, $errno, $errstr, 5);
        if (!$fp) {
            http_response_code(500);
            echo "Error conectando a Zebra $printerIp:$printerPort";
            exit;
        }
        fwrite($fp, $zpl);
        fclose($fp);
    }
    
    // Notificamos a JS que es un texto plano y enviamos éxito
    header('Content-Type: text/plain');
    echo "ZEBRA_OK";
    exit;

} else {
    // ---------------------------------------------------------
    // RUTA 2: GENERACIÓN DE PDF CON DOMPDF
    // ---------------------------------------------------------
    
    // Encapsulamos el resultado en un arreglo para que el foreach de la plantilla HTML funcione
    $datosReporte = [$result]; 

    // IMPORTANTISIMO: Le decimos al navegador que lo que viene es un PDF
    header('Content-Type: application/pdf');

    ob_start();
    require 'plantilla_pdf.php'; 
    $html = ob_get_clean();

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $dompdf->stream("Reporte_".$result['folio'].".pdf", array("Attachment" => false));
    exit;
}