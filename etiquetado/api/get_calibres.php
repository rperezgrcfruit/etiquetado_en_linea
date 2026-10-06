<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

require_once(ROOT_PATH . '/packing/etiquetado/config/config_local.php');

if (!isset($_GET["data"])) {
  echo json_encode(['error' => 'No se proporcionó el parámetro data']);
  exit;
}

$hostName = $_GET['data'];

$sql = "SELECT codigo, descripcion, calibre, categoria FROM salidas_packing WHERE id_wt32 =:hostName";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':hostName', $hostName, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$result) {
  echo json_encode(['error' => 'No se encontró la estación con el id_wt32 proporcionado']);
  exit;
}

echo json_encode($result);
