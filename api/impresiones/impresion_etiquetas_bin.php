<?php
//session_start();

if (!isset($_SESSION['username'])) {
  header("Location: /packing/index");
  exit();
}

//IMPRESION DE ETIQUETAS CALIBRADO - PRIMARIO
$data = json_decode(file_get_contents("php://input"), true);


// if ($data === null) {
//   echo json_encode(['error' => 'No se recibieron datos JSON válidos']);
//   //exit;
// }

$d = $data;

function z($v)
{
  return str_replace(['^', '~', "\n", "\r"], ' ', $v ?? '');
}

function imprimirEtiquetasBin($d, $copies, $printerIp, $printerPort)
{

$fechaActual = date('d-m-y');

// 2. Lógica de hardware según la IP
if ($printerIp === '192.168.1.88') {
    // Es la Zebra GK420t (Alineación central)
    // Empujamos el texto hacia la derecha para saltarnos el rodillo
    $eje_x = 0; 
    $comando_extra = "^LS60\n^PR6\n"; // Un número positivo jala todo a la izquierda
} elseif ($printerIp === '192.168.1.79') {
    // Es la Zebra ZT231 (Alineación izquierda)
    // Mantenemos el margen original pegado al borde
    $eje_x = 5;
} else {
    // Valor por defecto (como seguridad en caso de una IP nueva)
    $eje_x = 5; 
}

  $folio    = z($d[0][0]['folio'] ?? 'folio');
  $empresa   = z($d[0][0]['empresa'] ?? 'empresa');
  $especie   = z($d[0][0]['especie'] ?? 'especie');
  $variedad  = z($d[0][0]['variedad'] ?? 'variedad');
  $fecha_raw = $d[0][0]['fecha'] ?? 'fecha';
  $fecha_objeto = new DateTime($fecha_raw);
  $fecha = $fecha_objeto->format($fecha_objeto ? 'd-m-Y' : 'd-m-Y');
  $productor = z($d[0][0]['productor'] ?? 'productor');
  $calibre   = z($d[0][0]['calibre'] ?? 'calibre');
  $cantidad   = z($d[0][0]['kilos'] ?? '400');
  $unidad    = z('KGS');
  $sdp       = z($d[0][0]['sdp'] ?? '');

  $barcode = $folio;
  // Creamos un array asociativo con tus variables
  $dataArray = [
    "folio"     => $folio,
    "empresa"   => $empresa,
    "especie"   => $especie,
    "variedad"  => $variedad,
    "fecha"     => $fecha,
    "productor" => $productor,
    "calibre"   => $calibre,
    "cantidad"  => $cantidad,
    "unidad"    => $unidad,
    "sdp"       => $sdp
  ];

  $arrayQr = [$folio, $empresa, $especie, $fecha, $calibre];

  // Convertimos a JSON
  $qrdata = json_encode($arrayQr);

  // $zpl = "^XA\n";
  // $zpl .= "^CI28\n";
  // $zpl .= "^PW812\n";
  // $zpl .= "^LL560\n";
  // $zpl .= "^CI28\n";
  // $zpl .= "^LH5,10\n";
  // $zpl .= "^MD20\n";

  // 3. Armamos el encabezado ZPL dinámico
$zpl = "^XA\n";
$zpl .= "^CI28\n";
$zpl .= "^PW560\n"; 
$zpl .= "^LL960\n"; 
// Inyectamos el eje_x dinámico
$zpl .= "^LH" . $eje_x . ",10\n"; 
$zpl .= $comando_extra; // <--- Inyectamos el tirón a la izquierda aquí
$zpl .= "^MD20\n";

  // 1. FOLIO (Grande arriba)
  $zpl .= "^FO122,40^A0N,60,60^FD$folio^FS\n";
  $zpl .= "^FO122,100^BY2^BCN,50,N,N^FD$folio^FS\n";

  // 2. PRODUCTOR / EMPRESA
  $zpl .= "^FO122,180^A0N,30,20^FDProductor: $productor^FS\n";
  $zpl .= "^FO122,220^A0N,40,40^FDSDP: $sdp^FS\n";
  $zpl .= "^FO122,265^A0N,40,40^FDEmpresa: $empresa^FS\n";

  // 3. ESPECIE Y VARIEDAD (Líneas grandes)
  $zpl .= "^FO130,320^A0N,35,35^FDEspecie: $especie^FS\n";
  $zpl .= "^FO130,350^A0N,35,35^FDVariedad: $variedad^FS\n";

  // 4. FECHA
  $zpl .= "^FO130,400^A0N,40,30^FDFecha Produccion: $fecha^FS\n";

  // 5. CALIBRE
  $zpl .= "^FO130,450^A0N,40,40^FDCalibre/Color: $calibre^FS\n";

  // 6. CANTIDAD Y UNIDAD (Kilos)
  $zpl .= "^FO250,500^A0N,50,50^FD$unidad^FS\n";
  $zpl .= "^FO130,500^A0N,50,50^FD$cantidad^FS\n";

  // 7. FOLIOS REPETIDOS AL FINAL (Como en la foto)
  $zpl .= "^FO122,650^A0N,50,50^FD$folio^FS\n";

  $zpl .= "^FO122,750^A0N,50,50^FD$folio^FS\n";

  $zpl .= "^FO122,850^A0N,50,50^FD$folio^FS\n";

  //$zpl .= "^FO510,450^BQN,4,4^FDQA,$qrdata^FS\n";

  $zpl .= "^XZ";


  header('Content-Type: text/plain');
  // 2) Enviar a Zebra por TCP 9100
  // $printerIp = "192.168.1.96";
  // $printerPort = 9100;

  $errno = 0;
  $errstr = '';

  for ($i = 0; $i < $copies; $i++) {
    $fp = @fsockopen($printerIp, $printerPort, $errno, $errstr, 5);
    if (!$fp) {
      http_response_code(500);
      echo "❌ Error conectando a Zebra $printerIp:$printerPort\n";
      echo "Err: $errstr ($errno)\n";
      exit;
    }
    $bytes = fwrite($fp, $zpl);
    fclose($fp);
  }


  try {
    $mode = "zpl";
  } catch (Exception $e) {
    error_log("Print ZPL error: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno de impresión. Contacte al administrador.']);
    exit;
  }
}
