<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/etiquetado/config/config.php';

$empresa = $_GET['empresa'] ?? '';

try {
    $sql = 'SELECT  IF (tipo_proceso = "REPROCESO", CONCAT(n_proceso , " - ", tipo_proceso,"/SECUNDARIO"), CONCAT(n_proceso ,"-", tipo_proceso)) as dataProceso, n_proceso, tipo_proceso FROM procesos WHERE empresa = :empresa AND temporada = :temporada AND tipo_proceso <> "CALIBRADO" AND
    estado = :estado GROUP BY n_proceso ORDER BY n_proceso DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['empresa' => $empresa, 'temporada' => TEMPORADA_ACTUAL, 'estado' => 'ABIERTO']);
    $procesos = $stmt->fetchAll();
    echo json_encode($procesos);
} catch (Exception $e) {
    error_log("obtener_num_proceso error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener procesos. Contacte al administrador.']);
}