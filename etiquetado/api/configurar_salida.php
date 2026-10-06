<?php
// api/configurar_salida.php — Actualiza configuración completa de una salida
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/etiquetado/config/config.php';

// Soporte GET (legacy) y POST (nuevo)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $_GET = array_merge($_GET, $input ?? []);
}

$embalaje      = $_GET['embalaje'] ?? '';
$calibre       = $_GET['calibre'] ?? '';
$idSalida      = $_GET['id_salida'] ?? '';
$empresa       = $_GET['empresa'] ?? '';
$n_proceso     = $_GET['n_proceso'] ?? '';
$categoria     = $_GET['categoria'] ?? '';
$tipoProceso   = $_GET['tipo_proceso'] ?? 'NORMAL';
$impresora     = $_GET['impresora_zebra'] ?? '';

if (!$idSalida || !$empresa || !$n_proceso) {
    http_response_code(400);
    echo json_encode(["error" => "Parámetros requeridos: id_salida, empresa, n_proceso"]);
    exit;
}

try {
    $sql = 'UPDATE salidas_packing SET
                embalaje = :embalaje,
                calibre = :calibre,
                n_proceso = :n_proceso,
                empresa = :empresa,
                categoria = :categoria,
                tipo_proceso = :tipoProceso,
                impresora_zebra = :impresora_zebra
            WHERE codigo = :id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':embalaje'       => $embalaje ?: null,
        ':calibre'        => $calibre ?: null,
        ':n_proceso'      => $n_proceso,
        ':empresa'        => $empresa,
        ':categoria'      => $categoria ?: null,
        ':tipoProceso'    => $tipoProceso,
        ':impresora_zebra'=> $impresora ?: null,
        ':id'             => $idSalida
    ]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(["error" => "Salida no encontrada: $idSalida"]);
    } else {
        echo json_encode(["success" => "Salida configurada correctamente"]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}