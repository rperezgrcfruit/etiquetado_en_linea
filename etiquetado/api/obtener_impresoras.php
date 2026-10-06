<?php
// api/obtener_impresoras.php — Lista impresoras Zebra configuradas
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/etiquetado/config/config.php';

try {
    $sql = "SELECT nombre, ip FROM impresoras_tarjado ORDER BY nombre";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($result);
} catch (PDOException $e) {
    error_log("obtener_impresoras error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener impresoras. Contacte al administrador."]);
}