<?php
// api/buscar_salidas.php — Lista salidas con join a impresoras
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/etiquetado/config/config.php';

try {
    $sql = "SELECT
                sp.codigo,
                sp.descripcion,
                sp.calibre,
                sp.embalaje,
                sp.categoria,
                sp.tipo_proceso,
                sp.empresa,
                sp.impresora_zebra,
                COALESCE(it.ip, '') as impresora_ip,
                CASE
                    WHEN sp.calibre IS NOT NULL
                         AND sp.embalaje IS NOT NULL
                         AND sp.impresora_zebra IS NOT NULL
                    THEN 'activo'
                    ELSE 'sin_configurar'
                END as estado
            FROM salidas_packing sp
            LEFT JOIN impresoras_tarjado it ON sp.impresora_zebra = it.nombre
            ORDER BY sp.codigo";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
} catch (PDOException $e) {
    error_log("buscar_salidas error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Error al buscar salidas. Contacte al administrador."]);
}