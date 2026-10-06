<?php
/**
 * api/get_proceso_normal_detalle.php
 * Refactored to use ProcesoRepository (repository pattern).
 * Las funciones se mantienen como adaptadores para compatibilidad con
 * las vistas existentes que llaman obtainDetalleProcesoNormal() etc.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/repositories/ProcesoRepository.php';

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

function obtenerDetalleProcesoNormal($n_proceso)
{
    $repo = new ProcesoRepository();
    return $repo->getDetalleProcesoNormal($n_proceso);
}

function obtenerDetalleProcesoNormalExpo($n_proceso)
{
    $repo = new ProcesoRepository();
    return $repo->getFoliosIngresadosExpo($n_proceso);
}

function obtenerDetalleProcesoNormalCom($n_proceso)
{
    $repo = new ProcesoRepository();
    return $repo->getFoliosIngresadosCom($n_proceso);
}
