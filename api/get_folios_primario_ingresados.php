<?php
//session_start();
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
$nProceso = $_GET['proceso'] ?? null;
include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';
$temporada = TEMPORADA_ACTUAL;
// Obtener los procesos primarios abiertos
$query = "SELECT * FROM existencia_calibrado WHERE n_proceso = :nProceso and temporada = :temporada and empresa = :empresa order by folio desc";
$stmt = $pdo->prepare($query);
$stmt->execute(['nProceso' => $nProceso, 'temporada' => $temporada, 'empresa' => $_SESSION['empresa']]);
$primarioIngresados = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Calculas el total manualmente
$totalKilosPrimario = array_sum(array_column($primarioIngresados, 'kilos'));


// Obtener los procesos primarios comercial abiertos
$query = "SELECT * FROM existencia_comercial WHERE n_proceso = :nProceso and temporada = :temporada and empresa = :empresa and tipo_proceso = 'CALIBRADO' ORDER BY folio DESC";
$stmt = $pdo->prepare($query);
$stmt->execute(['nProceso' => $nProceso, 'temporada' => $temporada, 'empresa' => $_SESSION['empresa']]);
$primarioIngresadosComercial = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Calculas el total manualmente
$totalKilosCom = array_sum(array_column($primarioIngresadosComercial, 'kilos'));

// Devolver los resultados en formato JSON
// header('Content-Type: application/json');
// echo json_encode($procesos);

$query = "SELECT 
                (SELECT COALESCE(SUM(kilos), 0) FROM procesos WHERE n_proceso = :nProceso AND temporada = :temporada AND empresa = :empresa and tipo_proceso = 'CALIBRADO') AS kilosEntrada,
                (SELECT COALESCE(SUM(kilos), 0) FROM existencia_calibrado WHERE n_proceso = :nProceso AND temporada = :temporada AND empresa = :empresa and tipo_proceso = 'CALIBRADO') AS kilosPrimario,
                (SELECT COALESCE(SUM(kilos), 0) FROM existencia_comercial WHERE n_proceso = :nProceso AND temporada = :temporada AND empresa = :empresa and tipo_proceso = 'CALIBRADO') AS kilosComercial
            ";

$stmt = $pdo->prepare($query);
$stmt->execute(['nProceso' => $nProceso, 'temporada' => $temporada, 'empresa' => $_SESSION['empresa']]);
$detalleProceso = $stmt->fetch(PDO::FETCH_ASSOC);
  


?>