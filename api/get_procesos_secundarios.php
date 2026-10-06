<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';

// Obtener los procesos secundarios abiertos
$query = "SELECT * FROM procesos WHERE estado = 'ABIERTO' and tipo_proceso = 'REPROCESO' and empresa = :empresa and temporada= '" . TEMPORADA_ACTUAL . "' GROUP BY n_proceso ORDER BY n_proceso DESC";
$stmt = $pdo->prepare($query);
$stmt->execute(['empresa' => $_SESSION['empresa']]);
$procesosSecundarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Devolver los resultados en formato JSON
// header('Content-Type: application/json');
// echo json_encode($procesosSecundarios);
?>