<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['n_proceso'])) {

    $n_proceso = strtoupper(trim($_POST['n_proceso']));
    $exportadora = $_POST['exportadora'];
    $temporada = TEMPORADA_ACTUAL;
    $tipo_proceso = 'CALIBRADO';

    try {
        // 1. MÉTODO: busqueda_proceso()
        $sqlBusqueda = "SELECT
            planta.descripcion,
            procesos.lote as Lote,
            procesos.bins as Bins,
            procesos.kilos as Kilos,
            recepcion_granel.guia as Guia,
            procesos.empresa as Exportadora,
            recepcion_granel.productor as cod_prod,
            CONCAT(recepcion_granel.productor, '   ', productores.r_social) as Productor,
            recepcion_granel.csg as CSG,
            recepcion_granel.sdp as SDP,
            recepcion_granel.cuartel as Predio,
            recepcion_granel.especie as Especie,
            recepcion_granel.variedad as Variedad,
            recepcion_granel.fecha as Fecha_Recepcion,
            procesos.estado as Estado,
            procesos.planta as planta,
            CONCAT(procesos.planta, '   ', planta.descripcion) as full_planta,
            procesos.fecha as Fecha_Proceso,
            procesos.merma,
            procesos.desecho
        FROM procesos
        INNER JOIN recepcion_granel ON procesos.lote = recepcion_granel.lote
        INNER JOIN productores ON recepcion_granel.productor = productores.codigo
        INNER JOIN planta ON procesos.planta = planta.csp
        WHERE procesos.n_proceso = ?
          AND procesos.tipo_proceso = ?
          AND procesos.empresa = ?
          AND productores.empresa = ?
          AND recepcion_granel.exportadora = ?
          AND recepcion_granel.temporada = ?";

        $stmt = $pdo->prepare($sqlBusqueda);

        if (!$stmt) {
            throw new Exception("Error preparando consulta 1");
        }

        $stmt->execute([$n_proceso, $tipo_proceso, $exportadora, $exportadora, $exportadora, $temporada]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($resultados) > 0) {
            // Se encontró el proceso (EXISTENTE)
            $total_bins = 0;
            $total_kilos = 0;
            foreach ($resultados as $row) {
                $total_bins += (float)$row['Bins'];
                $total_kilos += (float)$row['Kilos'];
            }

            echo json_encode([
                'status' => 'EXISTENTE',
                'cabecera' => $resultados[0],
                'detalle' => $resultados,
                'totales' => [
                    'bins' => $total_bins,
                    'kilos' => $total_kilos
                ]
            ]);
            exit;
        }

        // 2. MÉTODO: validar_nproceso() (Si no se encontró arriba)
        $sqlValidar = "SELECT id FROM procesos
                       WHERE n_proceso = ?
                         AND empresa = ?
                         AND temporada = ? LIMIT 1";

        $stmtVal = $pdo->prepare($sqlValidar);

        if (!$stmtVal) {
            throw new Exception("Error preparando consulta 2");
        }

        $stmtVal->execute([$n_proceso, $exportadora, $temporada]);

        if ($stmtVal->fetchColumn()) {
            echo json_encode([
                'status' => 'ERROR',
                'mensaje' => 'No se puede utilizar este numero para crear proceso'
            ]);
        } else {
            echo json_encode([
                'status' => 'NUEVO'
            ]);
        }

    } catch (Exception $e) {
        error_log("busqueda-proceso error: " . $e->getMessage());
        echo json_encode(['status' => 'EXCEPTION', 'mensaje' => 'Error interno del servidor. Contacte al administrador.']);
    }
} else {
    echo json_encode(['status' => 'ERROR', 'mensaje' => 'Solicitud inválida']);
}
?>