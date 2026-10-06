<?php
// Error display handled by config/db.php (env-based)
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/repositories/FolioRepository.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/api/get_proceso_normal_detalle.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/api/get_proceso_secundario_detalle.php';

session_start();

if (!isset($_SESSION['username'])) {
    header('Location: /packing/index.php');
    exit();
}

if (isset($_POST) && !empty($_POST)) {
    requireCsrf();

    $numeroProceso = $_POST['numeroProceso'] ?? null;
    if (!$numeroProceso) {
        echo "Número de proceso requerido.";
        exit();
    }

    // Obtener detalle del proceso para insertComercial
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
    $folioDisponible = $repo->getFolioComercialDisponible();
    if (!$folioDisponible) {
        echo "Nooooo hay folios disponibles para tipo COMERCIAL. Contacte al administrador para configurar folios en asignacion_folios.";
        exit();
    }

    // Preparar datos para el repositorio
    $tipoProceso = $_POST['tipo_proceso'] ?? 'NORMAL';

    try {
        $result = $repo->insertComercial($_POST, $procesos);
        if ($result) {
            if ($tipoProceso === 'REPROCESO') {
                header('Location: /packing/view/procesos/ingreso-folio-secundario?proceso=' . $_POST['numeroProceso']);
            } elseif ($tipoProceso === 'CALIBRADO') {
                header('Location: /packing/view/procesos/ingreso-folio-primario?proceso=' . $_POST['numeroProceso']);
            } elseif ($tipoProceso === 'NORMAL') {
                header('Location: /packing/view/procesos/ingreso-folio-normal?proceso=' . $_POST['numeroProceso']);
            } elseif ($tipoProceso === 'REEMBALAJE') {
                header('Location: /packing/view/procesos/ingreso-folio-reembalaje?proceso=' . $_POST['numeroProceso']);
            }
            exit();
        } else {
            echo "Nox hay folios disponibles para tipo COMERCIAL. Contacte al administrador para configurar folios en asignacion_folios.";
        }
    } catch (\Throwable $e) {
        error_log("Comercial folio insert failed: " . $e->getMessage());
        echo "Error al ingresar el folio. Contacte al administrador."  . $e->getMessage();
    }
}