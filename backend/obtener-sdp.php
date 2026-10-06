<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

    $query = "SELECT * FROM cuarteles WHERE empresa = :empresa and variedad = :variedad and cod_productor = :productor";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'empresa' => $data['empresa'],
        'variedad' => $data['variedad'],
        'productor' => $data['productor']
    ]);
    $sdp = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($sdp);

