<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/etiquetado/config/config.php';
session_start();
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
if (!isset($_SESSION['username'])) {
    header('Location: /packing/index');
    exit();
}

if (isset($_POST) && !empty($_POST)) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/repositories/FolioRepository.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/api/get_proceso_normal_detalle.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/api/get_proceso_secundario_detalle.php';

    requireCsrf();
    $numeroProceso = $_GET['proceso'] ?? null;
    $_POST['proceso'] = $numeroProceso;

    // Obtener detalle del proceso para insertExportacion
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
    try {
        $result = $repo->insertExportacion($_POST, $procesos);

        if ($result) {
            $tipoProceso = $_POST['tipo_proceso'] ?? 'NORMAL';
            if ($tipoProceso === 'NORMAL') {
                header('Location: /packing/view/procesos/ingreso-folio-normal?proceso=' . $numeroProceso);
            } elseif ($tipoProceso === 'REEMBALAJE') {
                header('Location: /packing/view/procesos/ingreso-folio-reembalaje?proceso=' . $numeroProceso);
            } else {
                header('Location: /packing/view/procesos/ingreso-folio-secundario?proceso=' . $numeroProceso);
            }
            exit();
        } else {
            // Check if it was a folio availability issue
            $tipoPallet = $_POST['cbTipoPallet'] ?? '';
            if ($tipoPallet && in_array($tipoPallet, ['COMPLETO', 'SALDO', 'MUESTRA', 'DIFERENCIA'])) {
                echo "No hay folios disponibles para tipo {$tipoPallet}. Contacte al administrador para configurar folios en asignacion_folios.";
            } else {
                // Error genérico ya logeado en el repo
                echo "Error al ingresar el folio. Contacte al administrador.";
            }
        }
    } catch (\Throwable $e) {
        error_log("Folio DB error: " . $e->getMessage());
        if ($e->getCode() == '45000') {
            // Error de trigger de base de datos (lógica de negocio) — mensaje genérico
            echo "El folio no pudo ser ingresado. Verifique los datos e intente nuevamente.";
        } else {
            echo "Error de base de datos. Contacte al administrador.";
        }
    }
}