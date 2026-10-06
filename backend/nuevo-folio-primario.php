<?php
//session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/repositories/FolioRepository.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/api/get_proceso_normal_detalle.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/api/get_proceso_secundario_detalle.php';

session_start();
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (!isset($_SESSION['username'])) {
    header('Location: /packing/index');
    exit();
}

if (isset($_POST) && !empty($_POST)) {
    requireCsrf();

    $numeroProceso = $_GET['proceso'] ?? null;
    if (!$numeroProceso) {
        echo "Número de proceso requerido.";
        exit();
    }

    // Obtener detalle del proceso para insertCalibrado
    $procesos = obtenerDetalleProcesoNormal($numeroProceso);
    if (!$procesos) {
        $procesos = obtenerDetalleProcesoSecundario($numeroProceso);
    }
    if (!$procesos) {
        echo "Proceso no encontrado.";
        exit();
    }

    // Usar el repositorio moderno
    $repo = new FolioRepository();

    // Verificar folio disponible
    $folioDisponible = $repo->getFolioCalibradoDisponible();
    if (!$folioDisponible) {
        echo "No hay folios disponibles para tipo CALIBRADO/PRIMARIO. Contacte al administrador para configurar folios en asignacion_folios.";
        exit();
    }

    // Preparar datos para el repositorio
    $_POST['txtKgNeto'] = intval($_POST['txtKgBruto']) - BIN_TARA;

    try {
        $result = $repo->insertCalibrado($_POST, $procesos);

        if ($result) {
            header('Location: /packing/view/procesos/ingreso-folio-primario?proceso=' . $numeroProceso);
            exit();
        } else {
            echo "No hay folios disponibles para tipo CALIBRADO/PRIMARIO. Contacte al administrador para configurar folios en asignacion_folios.";
        }
    } catch (\Throwable $e) {
        error_log("Calibrado folio insert failed: " . $e->getMessage());
        echo "Error al ingresar el folio. Contacte al administrador.";
    }
}