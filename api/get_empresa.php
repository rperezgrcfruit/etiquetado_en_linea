<?php
/**
 * api/get_empresa.php
 * Refactored to use EmpresaRepository (repository pattern).
 * Returns all active empresas for the login dropdown.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/repositories/EmpresaRepository.php';

$empresaRepo = new EmpresaRepository();
$empresas = $empresaRepo->getAllActive();
