<?php

//IMPRESION DE ETIQUETAS CALIBRADO - PRIMARIO
$data = json_decode(file_get_contents("php://input"), true);

//print_r($data);
//exit;
if ($data === null) {
  echo json_encode(['error' => 'No se recibieron datos JSON válidos']);
  exit;
}

$d = $data;

function z($v)
{
  return str_replace(['^', '~', "\n", "\r"], ' ', $v ?? '');
}

if (!isset($d[0][0]['folio'])) {
  $folio    = z($d['folio']);
  $empresa   = z($d['empresa']);
  $especie   = z($d['especie']);
  $variedad  = z($d['variedad']);
  $fecha_raw = $d['fecha'];
  $fecha_objeto = new DateTime($fecha_raw);
  $fecha = date('d/m/Y');
  $productor = z($d['productor']);
  $calibre   = z($d['calibre']);
  $cantidad   = z($d['kilos']);
  $unidad    = "Kgs";
} else {
  $folio    = z($d[0][0]['folio']);
  $empresa   = z($d[0][0]['empresa']);
  $especie   = z($d[0][0]['especie']);
  $variedad  = z($d[0][0]['variedad']);
  $fecha_raw = $d[0][0]['fecha'];
  $fecha_objeto = new DateTime($fecha_raw);
  $fecha = date('d/m/Y');
  $productor = z($d[0][0]['productor']);
  $calibre   = z($d[0][0]['calibre']);
  $cantidad   = z($d[0][0]['kilos']);
  $unidad    = "Kgs";
}


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
  "unidad"    => $unidad
];

// Convertimos a JSON
$qrdata = json_encode($dataArray);

$zpl = "^XA\n";
$zpl .= "^CI28\n";
$zpl .= "^PW812\n";
$zpl .= "^LL560\n";
$zpl .= "^CI28\n";
$zpl .= "^LH5,10\n";
$zpl .= "^MD20\n";

// 1. FOLIO (Grande arriba)
$zpl .= "^FO280,40^A0N,60,60^FD$folio^FS\n";
$zpl .= "^FO280,100^BY2^BCN,50,N,N^FD$folio^FS\n";

// 2. PRODUCTOR / EMPRESA
$zpl .= "^FO125,190^A0N,28,28^FDProductor: $productor^FS\n";
$zpl .= "^FO125,230^A0N,35,35^FDEmpresa: $empresa^FS\n";

// 3. ESPECIE Y VARIEDAD (Líneas grandes)
$zpl .= "^FO200,275^A0N,40,40^FDEspecie: $especie^FS\n";
$zpl .= "^FO200,330^A0N,40,40^FDVariedad: $variedad^FS\n";

// 4. FECHA
$zpl .= "^FO200,400^A0N,40,30^FDFecha Produccion: $fecha^FS\n";

// 5. CALIBRE
$zpl .= "^FO200,450^A0N,40,40^FDCalibre/Color: $calibre^FS\n";

// 6. CANTIDAD Y UNIDAD (Kilos)
$zpl .= "^FO200,500^A0N,60,60^FD$unidad^FS\n";
$zpl .= "^FO300,500^A0N,60,60^FD$cantidad^FS\n";

// 7. FOLIOS REPETIDOS AL FINAL (Como en la foto)
$zpl .= "^FO280,650^A0N,50,50^FD$folio^FS\n";

$zpl .= "^FO280,750^A0N,50,50^FD$folio^FS\n";

$zpl .= "^FO280,850^A0N,50,50^FD$folio^FS\n";

$zpl .= "^FO500,450^BQN,2,2^FDQA,$qrdata^FS\n";

$zpl .= "^XZ";


header('Content-Type: text/plain');
// 2) Enviar a Zebra por TCP 9100
$printerIp = "192.168.1.88";
$printerPort = 9100;
$copias = 2;
$errno = 0;
$errstr = '';

for ($i = 0; $i < $copias; $i++) {

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
  echo json_encode(['error' => $e->getMessage()]);
  exit;
}
