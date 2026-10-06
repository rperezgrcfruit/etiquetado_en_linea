<?php
// Error display disabled — errors go to PHP error log
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/etiquetado/config/config_local.php';
$temporada = TEMPORADA_ACTUAL;
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
echo "prueba";
// $controller = $_POST['controller'] ?? $_GET['controller'] ?? '';
// $button     = $_POST['button'] ?? $_GET['button'] ?? 'BTN-001';
// $mac        = $_OST['mac'] ?? $_GET['mac'] ?? '';
// $ip         = $_SERVER['REMOTE_ADDR'] ?? '';

// echo json_encode([
//   'ok' => true,
//   'received' => [
//     'controller' => $controller,
//     'button' => $button,
//     'mac' => $mac,
//     'from_ip' => $ip,
//     'time' => date('Y-m-d H:i:s')
//   ]
// ]);
function ImpresionError2($message)
{

  $zpl = "^XA\n";
  $zpl .= "^CI28\n";
  $zpl .= "^PW812\n";
  $zpl .= "^LL560\n";
  $zpl .= "^CI28\n";
  $zpl .= "^LH5,10\n";
  $zpl .= "^MD20\n";
  $zpl .= "^FO05,300^A0N,18,18^FD $message^FS\n";

  $zpl .= "^XZ";

  header('Content-Type: text/plain');
  // 2) Enviar a Zebra por TCP 9100
  $printerIp = "192.168.1.76";
  $printerPort = 9100;

  $errno = 0;
  $errstr = '';
  $fp = @fsockopen($printerIp, $printerPort, $errno, $errstr, 5);

  if (!$fp) {
    http_response_code(500);
    echo "❌ Error conectando a Zebra $printerIp:$printerPort\n";
    echo "Err: $errstr ($errno)\n";
    exit;
  }

  $bytes = fwrite($fp, $zpl);
  fclose($fp);

  echo "✅ OK. Etiqueta enviada.\n";
  echo "Bytes enviados: $bytes\n";
  exit;
}



//ImpresionError('No se proporcionó el parámetro salida');
if (!isset($_GET['salida']) && !empty($_GET['salida'])) {
  echo json_encode(['error' => 'No se proporcionó el parámetro salida']);
  ImpresionError2('No se proporcionó el parámetro salida');
  exit;
}

$salida = $_GET['salida'];
//echo json_encode(['received_salida' => $salida]);



try {
  $sql = "SELECT * FROM salidas_packing WHERE codigo = :salida";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['salida' => $salida]);
  $result = $stmt->fetchAll();
  echo json_encode($result);
  //$pdo = null; // Cerrar la conexión
  echo 'calibre: ' . $result[0]['calibre'];
  $calibre = $result[0]['calibre'] ?? '96';
  $n_proceso = $result[0]['nProceso'] ?? 'REP1';
  $embalaje = $result[0]['embalaje'] ?? null;
  $empresa = $result[0]['empresa'] ?? null;
  $categoria = $result[0]['categoria'] ?? null;
  $tipoProceso = $result[0]['tipo_proceso'];
  $cantidadImpresion = $data['cantidad'] ?? 1;
  $impresora = $result[0]['impresora_zebra'];
  //cho json_encode(['calibre' => $calibre, 'n_proceso' => $n_proceso, 'categoria' => $categoria, 'tipo_proceso' => $tipoProceso]);
} catch (PDOException $e) {
  echo json_encode(['error' => $e->getMessage()]);
  exit;
}

$sql = "SELECT * FROM empresa WHERE R_SOCIAL = :empresa";
$stmt = $pdo->prepare($sql);
$stmt->execute(['empresa' => $empresa]);
$empresaData = $stmt->fetch(PDO::FETCH_ASSOC);
$cse = $empresaData['cse'] ?? '0';

$sql = "SELECT * FROM embalajes WHERE codigo = :embalaje and empresa = :empresa";
$stmt = $pdo->prepare($sql);
$stmt->execute(['embalaje' => $embalaje, 'empresa' => $empresa]);
$embalajeData = $stmt->fetch(PDO::FETCH_ASSOC);
$pesoNeto = $embalajeData['Peso Neto'] ?? '0';

if (!$calibre || !$n_proceso) {
  echo json_encode(['error' => 'No se encontró el calibre o n_proceso para la salida proporcionada']);
  ImpresionError2('No se encontró el calibre o n_proceso para la salida proporcionada');
  exit;
}



if ($tipoProceso === 'NORMAL') {
  try {
    $sql = "select n_proceso, tipo_proceso, DATE_FORMAT(procesos.fecha, '%d-%m-%Y') as fecha_proceso,
  procesos.lote, procesos.empresa, recepcion_granel.guia as guia_recepcion, recepcion_granel.especie, recepcion_granel.variedad,
  recepcion_granel.fecha as fecha_lote, recepcion_granel.sdp, recepcion_granel.productor as codProd, CONCAT(recepcion_granel.productor , ' ' , productores.r_social) as productor,
  productores.ggn, productores.csg, productores.comuna, productores.provincia, productores.region, procesos.bins as Bins 
  from procesos INNER JOIN recepcion_granel ON procesos.lote = recepcion_granel.lote 
  INNER JOIN productores ON recepcion_granel.productor = productores.codigo
  where procesos.n_proceso = :n_proceso and procesos.empresa= :empresa and procesos.temporada=:temporada and
  recepcion_granel.exportadora= :empresa2 and recepcion_granel.temporada=:temporada2
  and productores.empresa= :empresa3 and procesos.tipo_proceso = 'NORMAL' ORDER BY procesos.id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['n_proceso' => $n_proceso, 'empresa' => $empresa, 'temporada' => $temporada, 'empresa2' => $empresa, 'temporada2' => $temporada, 'empresa3' => $empresa]);
    $result = $stmt->fetchAll();
    if (!$result) {
      echo json_encode(['error' => 'No se encontraron datos para el n_proceso proporcionado']);
      ImpresionError2('No se encontraron datos para el proceso: ' . $n_proceso . ' proporcionado');
      exit;
    } else {
      $result[0]['calibre'] = $calibre; // Agregar el calibre al resultado
      //echo json_encode($result);
    }
  } catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    ImpresionError2('Error al obtener datos del proceso NORMAL');
    exit;
  }
} elseif ($tipoProceso === 'REPROCESO') {

  try {

    $sql = "
        SELECT 
            p_re.n_proceso,
            p_re.tipo_proceso,
            DATE_FORMAT(p_re.fecha, '%d-%m-%Y') as fecha_proceso,

            p_re.lote,
            p_re.empresa,
            rg.lote as lote_recepcion,
            rg.guia as guia_recepcion,
            rg.especie,
            rg.variedad,
            rg.fecha as fecha_lote,
            rg.sdp,

            rg.productor as codProd,

            CONCAT(rg.productor, ' ', prod.r_social) as productor,

            prod.ggn,
            prod.csg,
            prod.comuna,
            prod.provincia,
            prod.region,

            p_re.bins as Bins

        FROM procesos p_re

        INNER JOIN existencia_calibrado ec
            ON ec.folio = p_re.lote
            AND ec.empresa = p_re.empresa
            AND ec.temporada = p_re.temporada

        INNER JOIN procesos p_cal
            ON p_cal.n_proceso = ec.n_proceso
            AND p_cal.tipo_proceso = 'CALIBRADO'
            AND p_cal.empresa = ec.empresa
            AND p_cal.temporada = ec.temporada

        INNER JOIN recepcion_granel rg
            ON rg.lote = p_cal.lote
            AND rg.exportadora = p_cal.empresa
            AND rg.temporada = p_cal.temporada

        INNER JOIN productores prod
            ON prod.codigo = rg.productor
            AND prod.empresa = rg.exportadora

        WHERE p_re.n_proceso = :n_proceso
          AND p_re.empresa = :empresa
          AND p_re.temporada = :temporada
          AND p_re.tipo_proceso = 'REPROCESO'

        ORDER BY p_re.id
        ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
      ':n_proceso' => $n_proceso,
      ':empresa' => $empresa,
      ':temporada' => $temporada
    ]);

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
//echo json_encode($result);
    if (!$result) {

      echo json_encode([
        'error' => 'No se encontraron datos para el reproceso'
      ]);

      ImpresionError2('No se encontraron datos para el reproceso');
      exit;
    }

    $loteRecepcion = $result[0]['lote_recepcion'];
    $result[0]['calibre'] = $calibre;

    //echo json_encode($result);

  } catch (PDOException $e) {

    echo json_encode([
      'error' => $e->getMessage()
    ]);

    ImpresionError2('Error al obtener datos del proceso NORMAL');
    exit;
  }
}





try {



  function ObtenerGgn2($productor, $empresa, $especie)
  {
    global $pdo;
    $sql = "SELECT ggn FROM ggn_productores WHERE id_productor = :productor and empresa = :empresa and especie = :especie";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['productor' => $productor, 'empresa' => $empresa, 'especie' => $especie]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    return $data['ggn'] ?? '0';
  }

function ObtenerImpresora($impresora) {
  global $pdo;
  $sql = "SELECT ip FROM impresoras_tarjado WHERE nombre = :impresora";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['impresora' => $impresora]);
  $data = $stmt->fetch(PDO::FETCH_ASSOC);
  return $data['ip'] ?? '';
}



  $mode = $_GET['mode'] ?? 'zpl';

  if ($mode === 'zpl' && isset($result[0])) {

    $d = $result;

    //echo json_encode(['zpl_dataEMPRESA' => $d[0]['empresa']]); // Mostrar los datos que se usarán para generar la etiqueta
    //exit; // Detener aquí para ver el resultado JSON antes de generar la etiqueta
    // Función simple para limpiar texto
    function z($v)
    {
      return str_replace(['^', '~', "\n", "\r"], ' ', $v ?? '');
    }

    $empresa   = z($d[0]['empresa']);
    $variedad  = z($d[0]['variedad']);
    $fecha     = z($d[0]['fecha_proceso']);
    $productor = z($d[0]['productor']);
    $ggn       = z($d[0]['ggn']);
    $csg       = z($d[0]['csg']);
    $comuna    = z($d[0]['comuna']);
    $prov      = z($d[0]['provincia']);
    $region    = z($d[0]['region']);
    $lote      = z($d[0]['lote']);
    $proceso   = z($d[0]['n_proceso']);
    $calibre   = z($d[0]['calibre']);
    $bins      = z($d[0]['Bins']);
    $especie   = z($d[0]['especie']);
    $sdp       = z($d[0]['sdp']);

    if ($empresa === 'FMS SPA') {
      $categoria = 'CLASS 1 o CAT 1/ US#1';
    }

    $ggn = ObtenerGgn($d[0]['codProd'], $empresa, $d[0]['especie']);
    $barcode = $lote . '|' . $proceso . '|' . $productor . '|' . $fecha . '|' . $especie . '|' . $variedad;
    $qrData  = $barcode;
    $coc = '4063651347296';

    $zpl = "^XA\n";
    $zpl .= "^CI28\n";
    $zpl .= "^PW812\n";
    $zpl .= "^LL560\n";
    $zpl .= "^CI28\n";
    $zpl .= "^LH5,10\n";
    $zpl .= "^MD20\n";

    $zpl .= "^FO10,15^A0N,22,22^FDEXPORTADO POR/EXPORTED BY $empresa^FS\n";
    $zpl .= "^FO10,40^A0N,24,24^FDCoc: $coc^FS\n";
    $zpl .= "^FO10,61^A0N,24,24^FDCse: $cse^FS\n";
    $zpl .= "^FO600,15^A0N,22,22^FDProducto de Chile^FS\n";
    $zpl .= "^FO600,40^A0N,22,22^FDProduct of Chile^FS\n";

    $zpl .= "^FO10,80^GB760,3,3^FS\n";

    $zpl .= "^FO10,95^A0N,23,23^FDPRODUCTO/PRODUCT: $especie^FS\n";
    $zpl .= "^FO10,122^A0N,23,23^FDVARIEDAD/VARIETY: $variedad^FS\n";
    $zpl .= "^FO10,140^A0N,23,23^FDLote Granel: $loteRecepcion^FS\n";


    $zpl .= "^FO10,165^A0N,23,23^FD$categoria^FS\n";
    $zpl .= "^FO10,190^A0N,30,30^FDFECHA/DATE: $fecha^FS\n";

    $zpl .= "^FO600,270^BQN,4,4^FDLA,$qrData^FS\n";

    $zpl .= "^FO600,90^GB150,120,3^FS\n";
    $zpl .= "^FO617,100^A0N,28,28^FDcalibre/Size^FS\n";
    $zpl .= "^FO625,135^A0N,90,90^FD$calibre^FS\n";

    $zpl .= "^FO380,85^A0N,26,26^FDEmbalaje: $embalaje^FS\n";
    $zpl .= "^FO420,120^A0N,23,23^FDPeso Neto: $pesoNeto kg^FS\n";

    $zpl .= "^FO10,240^A0N,23,23^FDPRODUCTOR/GROWER: $productor^FS\n";
    $zpl .= "^FO10,270^A0N,28,28^FDGGN: $ggn^FS\n";
    $zpl .= "^FO300,270^A0N,28,28^FDSDP: $sdp^FS\n";

    $zpl .= "^FO10,305^A0N,28,28^FDCSG: $csg^FS\n";
    $zpl .= "^FO10,332^A0N,27,27^FDPROVINCIA: $prov^FS\n";
    $zpl .= "^FO10,360^A0N,27,27^FDCOMUNA: $comuna^FS\n";
    $zpl .= "^FO10,385^A0N,27,27^FDRegion: $region^FS\n";

    $zpl .= "^FO10,420^A0N,28,28^FDCSP: 121293^FS\n";
    $zpl .= "^FO200,420^A0N,28,28^FDCoC: 4063651646719^FS\n";
    $zpl .= "^FO10,450^A0N,27,27^FDEmbalado por/Packed by: GRC Fruit SPA Hopfenblatt 1747^FS\n";
    $zpl .= "^FO10,480^A0N,23,26^FB600,3,0,L,0^FDComuna: San Felipe, Provincia: San Felipe de Aconcagua, Region: V^FS\n";

    //$zpl .= "^FO300,300^A0N,18,18^FDCOMUNA: $comuna^FS\n";
    //$zpl .= "^FO450,300^A0N,18,18^FDREGION: $region^FS\n";

    $zpl .= "^XZ";

    header('Content-Type: text/plain');
    // 2) Enviar a Zebra por TCP 9100
    $printerIp = ObtenerImpresora($impresora);
    $printerPort = 9100;

    $errno = 0;
    $errstr = '';

    for ($i = 0; $i < $cantidadImpresion; $i++) {
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
    echo "✅ OK. Etiqueta enviada.\n";
    echo "Bytes enviados: $bytes\n";
    exit;
  }
} catch (PDOException $th) {
  echo json_encode(['error' => $th->getMessage()]);
  exit;
}
