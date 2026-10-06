<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['lote'])) {

    $lote = strtoupper(trim($_POST['lote']));
    $exportadora = $_POST['exportadora'];
    $temporada = TEMPORADA_ACTUAL;

    try {
        $sql = "SELECT r.guia, r.productor AS cod_prod, CONCAT(r.productor, '   ', p.r_social) AS full_prod,
                       r.especie, r.variedad, r.cant_bins, r.kilos_recep, r.sdp, r.cuartel AS predio, r.csg
                FROM recepcion_granel r
                INNER JOIN productores p ON r.productor = p.codigo
                WHERE r.lote = ? AND r.exportadora = ?
                  AND r.bins > 0 AND r.kilos > 0
                  AND r.temporada = ? AND p.empresa = ? LIMIT 1";

        $stmt = $pdo->prepare($sql);
        if (!$stmt) throw new Exception("Error en consulta");

        $stmt->execute([$lote, $exportadora, $temporada, $exportadora]);

        $datos = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($datos) {
            // Equivalente a la lógica de tu Label4.Text para actualizar kilos dinámicamente
            $promedio_bin = (float)$datos['kilos_recep'] / (float)$datos['cant_bins'];
            $datos['promedio_bin'] = round($promedio_bin, 2);

            echo json_encode(['status' => 'OK', 'data' => $datos]);
        } else {
            echo json_encode(['status' => 'ERROR', 'mensaje' => 'Lote no se encuentra en existencia o su saldo es 0.']);
        }
    } catch (Exception $e) {
        error_log("buscar-lote error: " . $e->getMessage());
        echo json_encode(['status' => 'EXCEPTION', 'mensaje' => 'Error interno del servidor. Contacte al administrador.']);
    }
} else {
    echo json_encode(['status' => 'ERROR', 'mensaje' => 'Dato vacío.']);
}
?>