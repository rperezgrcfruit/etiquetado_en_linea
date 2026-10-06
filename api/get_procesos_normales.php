<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';

// Obtener los procesos normales abiertos
$query = "SELECT * FROM procesos WHERE estado = 'ABIERTO' and tipo_proceso = 'NORMAL' and empresa = :empresa and temporada= '" . TEMPORADA_ACTUAL . "' GROUP BY n_proceso ORDER BY n_proceso DESC";
$stmt = $pdo->prepare($query);
$stmt->execute(['empresa' => $_SESSION['empresa']]);
$procesosNormales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Devolver los resultados en formato JSON
// header('Content-Type: application/json');
// echo json_encode($procesosNormales);
?>