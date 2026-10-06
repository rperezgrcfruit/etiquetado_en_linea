<?php
/**
 * @deprecated REPLACED BY FolioRepository::insertComercial() + getFolioComercialDisponible()
 * Was: legacy functions for COMERCIAL folio insertion.
 * All backend files now use FolioRepository directly.
 */
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: /packing/index');
    exit();
}

/**
 * @deprecated Use FolioRepository::getFolioComercialDisponible()
 */
function getFolioComercialDisponible($pdo) {
    $stmt = $pdo->prepare("SELECT folio FROM asignacion_folios WHERE tipo_folio = 'COMERCIAL' AND estado_folio = 'DISPONIBLE' and empresa = :empresa LIMIT 1");
    $stmt->execute(['empresa' => $_SESSION['empresa']]);
    return $stmt->fetchALL(PDO::FETCH_ASSOC);
}

/**
 * @deprecated Use FolioRepository::insertComercial()
 */
function IngresoFolioComercial($pdo, $folioAsignado, $arrayDatos) {
    $fechaActual = date('Y-m-d');
    $stmt = $pdo->prepare("INSERT INTO existencia_comercial(n_proceso, tipo_proceso, fecha, planta, empresa, productor, especie, variedad,
    folio, envase, unidad, categoria, calibre, kilos, estado, temporada)
    VALUES (:n_proceso, :tipo_proceso, :fecha, :planta, :empresa, :productor, :especie, :variedad,
    :folio, :envase, :unidad, :categoria, :calibre, :kilos, :estado, :temporada)");
    $stmt->execute([
        'n_proceso' => $arrayDatos['numeroProceso'],
        'tipo_proceso' => $arrayDatos['tipo_proceso'],
        'fecha' => $fechaActual,
        'empresa' => $_SESSION['empresa'],
        'productor' => $arrayDatos['productor'],
        'especie' => $arrayDatos['especie'],
        'variedad' => $arrayDatos['variedad'],
        'folio' => $folioAsignado,
        'envase' => 'BIN',
        'categoria' => 'COMERCIAL',
        'calibre' => $arrayDatos['cbCalibreComercial'],
        'unidad' => 'KGS',
        'kilos' => $arrayDatos['inputTextKgNetoCom'],
        'planta' => 'GRCFRUIT SPA',
        'estado' => 'EXISTENCIA',
        'temporada' => TEMPORADA_ACTUAL
    ]);
    if ($stmt->rowCount() > 0) {
        $stmtUpdate = $pdo->prepare("UPDATE asignacion_folios SET estado_folio = 'UTILIZADO' WHERE folio = :folio and empresa = :empresa and tipo_folio = 'COMERCIAL' and temporada = :temporada");
        $stmtUpdate->execute([
            'folio' => $folioAsignado,
            'empresa' => $_SESSION['empresa'],
            'temporada' => TEMPORADA_ACTUAL
        ]);

        $stmHistorico = $pdo->prepare("INSERT INTO `historico` (`folio_lote`, `tipo`, `cantidad`, `n_operacion`, `tipo_operacion`, `tipo_movimiento`, `usuario`, `empresa`, `temporada`)
        VALUES (:folio, :tipo, :cantidad, :n_operacion, :tipo_operacion, :tipo_movimiento, :usuario, :empresa, :temporada)");
        $stmHistorico->execute([
            'folio' => $folioAsignado,
            'tipo' => 'COMERCIAL',
            'cantidad' => $arrayDatos['inputTextKgNetoCom'],
            'n_operacion' => $arrayDatos['numeroProceso'],
            'tipo_operacion' => $arrayDatos['tipo_proceso'],
            'tipo_movimiento' => 'INGRESO',
            'usuario' => $_SESSION['username'],
            'empresa' => $_SESSION['empresa'],
            'temporada' => TEMPORADA_ACTUAL
        ]);
        return true;
    } else {
        return false;
    }
}