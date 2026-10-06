<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /packing/index");
    exit();
}

if (!isset($_GET['folio'])) {
    echo "No se proporcionó un folio.";
    exit();
}

$folio = $_GET['folio'];
$tipoProceso = $_GET['tipoProceso'];

if ($tipoProceso === 'REPROCESO') {
    $query = "SELECT * FROM existencia_comercial WHERE folio = :folio and temporada = :temporada and empresa = :empresa and tipo_proceso = 'REPROCESO'";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['folio' => $folio, 'temporada' => TEMPORADA_ACTUAL, 'empresa' => $_SESSION['empresa']]);
    $primarioIngresados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$primarioIngresados) {
        echo "No se encontró el folio: " . htmlspecialchars($folio);
        exit();
    }
}else if ($tipoProceso === 'NORMAL') {
    $query = "SELECT * FROM existencia_comercial WHERE folio = :folio and temporada = :temporada and empresa = :empresa and tipo_proceso = 'NORMAL'";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['folio' => $folio, 'temporada' => TEMPORADA_ACTUAL, 'empresa' => $_SESSION['empresa']]);
    $primarioIngresados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$primarioIngresados) {
        echo "No se encontró el folio: " . htmlspecialchars($folio);
        exit();
    }
} else if ($tipoProceso === 'REEMBALAJE') {
    $query = "SELECT * FROM existencia_comercial WHERE folio = :folio and temporada = :temporada and empresa = :empresa and tipo_proceso = 'REEMBALAJE'";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['folio' => $folio, 'temporada' => TEMPORADA_ACTUAL, 'empresa' => $_SESSION['empresa']]);
    $primarioIngresados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$primarioIngresados) {
        echo "No se encontró el folio: " . htmlspecialchars($folio);
        exit();
    }
} else {
    $query = "SELECT * FROM existencia_comercial WHERE folio = :folio and temporada = :temporada and empresa = :empresa and tipo_proceso = 'CALIBRADO'";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['folio' => $folio, 'temporada' => TEMPORADA_ACTUAL, 'empresa' => $_SESSION['empresa']]);
    $primarioIngresados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$primarioIngresados) {
        echo "No se encontró el folio: " . htmlspecialchars($folio);
        exit();
    }
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/api/impresiones/impresion_etiquetas_bin.php';
imprimirEtiquetasBin([$primarioIngresados], 2, "192.168.1.88", 9100);
