<?php
//session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
function obtenerDetalleProcesoSecundario($n_proceso)
{
    global $pdo;
    $temporadaActual = TEMPORADA_ACTUAL;
    // Obtener los procesos secundarios abiertos
    $query = "SELECT existencia_calibrado.*, productores.csg FROM existencia_calibrado INNER JOIN productores ON existencia_calibrado.productor = productores.codigo 
    WHERE reproceso = :n_proceso AND tipo_proceso = 'CALIBRADO'
AND existencia_calibrado.empresa = :empresa AND existencia_calibrado.temporada = :temporada and productores.empresa = :empresa2";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'n_proceso' => $n_proceso,
        'empresa' => $_SESSION['empresa'],
        'temporada' => $temporadaActual,
        'empresa2' => $_SESSION['empresa']

    ]);
    $procesosSecundarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //echo json_encode($procesosSecundarios);
    return $procesosSecundarios;

    
}

function obtenerDetalleProcesoSecundarioExpo($n_proceso)
{
    global $pdo;
    $temporadaActual = TEMPORADA_ACTUAL;
    // Obtener los procesos secundarios abiertos
    $query = "SELECT existencia_exportacion.*, ROUND((embalajes.`peso neto` * existencia_exportacion.cajas), 2) AS kilosExportacion 
    , ROUND((embalajes.deshidratacion * existencia_exportacion.cajas), 2) AS deshidratacion_total
    FROM existencia_exportacion INNER JOIN embalajes ON existencia_exportacion.embalaje = embalajes.codigo
    WHERE n_proceso = :n_proceso AND tipo_proceso = 'REPROCESO'
    AND existencia_exportacion.empresa = :empresa AND existencia_exportacion.temporada = :temporada
    AND embalajes.empresa = :empresa2
    ORDER BY folio DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'n_proceso' => $n_proceso,
        'empresa' => $_SESSION['empresa'],
        'temporada' => $temporadaActual,
        'empresa2' => $_SESSION['empresa']
    ]);
    $procesosSecundariosExpo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //echo json_encode($procesosSecundariosExpo);
    return $procesosSecundariosExpo;

    
}

function obtenerDetalleProcesoSecundarioCom($n_proceso)
{
    global $pdo;
    $temporadaActual = TEMPORADA_ACTUAL;
    // Obtener los procesos secundarios abiertos
    $query = "SELECT * FROM existencia_comercial WHERE n_proceso = :n_proceso AND tipo_proceso = 'REPROCESO'
    AND empresa = :empresa AND temporada = :temporada ORDER BY folio DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'n_proceso' => $n_proceso,
        'empresa' => $_SESSION['empresa'],
        'temporada' => $temporadaActual

    ]);
    $procesosSecundariosCom = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //echo json_encode($procesosSecundariosExpo);
    $total_kilos_com = array_sum(array_column($procesosSecundariosCom, 'kilosComercial'));
    return $procesosSecundariosCom;

    
}
