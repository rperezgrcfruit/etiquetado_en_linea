<?php
require_once '../config/config.php';

$nProceso = $_GET['n_proceso'] ?? '';
$empresa = $_GET['empresa'] ?? '';
$tipoProceso = $_GET['tipo_proceso'] ?? 'NORMAL';

if (!$nProceso || !$empresa) {
    echo json_encode(["error" => "Parámetros 'n_proceso' y 'empresa' son requeridos."]);
    exit;
}

$especie = null;

try {
    if ($tipoProceso === 'NORMAL') {
        // Obtener lote del proceso normal
        $sql = 'SELECT lote FROM procesos WHERE n_proceso = :n_proceso AND empresa = :empresa AND temporada = :temporada AND tipo_proceso = "NORMAL" LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':n_proceso' => $nProceso,
            ':empresa' => $empresa,
            ':temporada' => TEMPORADA_ACTUAL
        ]);
        $loteRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$loteRow) {
            echo json_encode(["error" => "Proceso no encontrado"]);
            exit;
        }

        $lote = $loteRow['lote'];

        // Obtener especie de recepcion_granel
        $sql = 'SELECT especie FROM recepcion_granel WHERE lote = :lote AND exportadora = :empresa AND temporada = :temporada LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':lote' => $lote,
            ':empresa' => $empresa,
            ':temporada' => TEMPORADA_ACTUAL
        ]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $especie = $data['especie'];
        }

    } elseif ($tipoProceso === 'REPROCESO') {
        // Obtener lote del reproceso
        $sql = 'SELECT lote FROM procesos WHERE n_proceso = :n_proceso AND empresa = :empresa AND temporada = :temporada AND tipo_proceso = "REPROCESO" LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':n_proceso' => $nProceso,
            ':empresa' => $empresa,
            ':temporada' => TEMPORADA_ACTUAL
        ]);
        $loteRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$loteRow) {
            echo json_encode(["error" => "Reproceso no encontrado"]);
            exit;
        }

        $lote = $loteRow['lote'];

        // Buscar n_proceso del calibrado origen
        $sql = 'SELECT n_proceso FROM existencia_calibrado WHERE folio = :folio AND empresa = :empresa AND temporada = :temporada AND tipo_proceso = "CALIBRADO" LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':folio' => $lote,
            ':empresa' => $empresa,
            ':temporada' => TEMPORADA_ACTUAL
        ]);
        $calRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$calRow) {
            echo json_encode(["error" => "No se encontró calibrado origen"]);
            exit;
        }

        $nProcesoCal = $calRow['n_proceso'];

        // Obtener lote de recepción del calibrado
        $sql = 'SELECT lote FROM procesos WHERE n_proceso = :n_proceso AND empresa = :empresa AND temporada = :temporada AND tipo_proceso = "CALIBRADO" LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':n_proceso' => $nProcesoCal,
            ':empresa' => $empresa,
            ':temporada' => TEMPORADA_ACTUAL
        ]);
        $procRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$procRow) {
            echo json_encode(["error" => "No se encontró proceso calibrado"]);
            exit;
        }

        $loteRecep = $procRow['lote'];

        // Obtener especie
        $sql = 'SELECT especie FROM recepcion_granel WHERE lote = :lote AND exportadora = :empresa AND temporada = :temporada LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':lote' => $loteRecep,
            ':empresa' => $empresa,
            ':temporada' => TEMPORADA_ACTUAL
        ]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $especie = $data['especie'];
        }
    }

    if ($especie) {
        echo json_encode(['especie' => $especie]);
    } else {
        echo json_encode(["error" => "No se pudo determinar la especie"]);
    }

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
} catch (\Throwable $e) {
    echo json_encode(["error" => $e->getMessage()]);
}