<?php
//session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';

// Obtener los procesos primarios abiertos
$query = "SELECT * FROM colores WHERE empresa = :empresa and especie = 'LIMON'";
$stmt = $pdo->prepare($query);
$stmt->execute(['empresa' => $_SESSION['empresa']]);
$colores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Devolver los resultados en formato JSON
// header('Content-Type: application/json');
// echo json_encode($procesos);
?>