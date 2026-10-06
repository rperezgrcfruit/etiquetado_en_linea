<?php
/**
 * @deprecated REPLACED BY FolioRepository::insertCalibrado() + getFolioCalibradoDisponible()
 * Was: legacy functions for CALIBRADO/PRIMARIO folio insertion.
 * All backend files now use FolioRepository directly.
 */
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: /packing/index');
    exit();
}

/**
 * @deprecated Use FolioRepository::getFolioCalibradoDisponible()
 */
function getFolioCalibradoDisponible($pdo) {
    $stmt = $pdo->prepare("SELECT folio FROM asignacion_folios WHERE tipo_folio = 'CALIBRADO/PRIMARIO' AND estado_folio = 'DISPONIBLE' and empresa = :empresa LIMIT 1");
    $stmt->execute(['empresa' => $_SESSION['empresa']]);
    return $stmt->fetchALL(PDO::FETCH_ASSOC);
}

/**
 * @deprecated Use FolioRepository::insertCalibrado()
 */
function IngresoFolioCalibrado($pdo, $folioAsignado, $arrayDatos) {
    $fechaActual = date('Y-m-d');

    $stmt = $pdo->prepare("INSERT INTO existencia_calibrado (n_proceso, tipo_proceso, fecha, empresa, productor, especie
    , variedad, folio, embalaje, categoria, calibre, unidad, kg_bruto, tara, cantidad, kilos
    , packing, estado, reproceso, temporada, n_despacho, desverdizado, ingreso_desverdizado, salida_desverdizado, sdp)
    VALUES (:n_proceso, :tipo_proceso, :fecha, :empresa, :productor, :especie, :variedad, :folio, :embalaje, :categoria,
    :calibre, :unidad, :kg_bruto, :tara, :cantidad, :kilos, :packing, :estado, :reproceso, :temporada, :n_despacho,
    :desverdizado, :ingreso_desverdizado, :salida_desverdizado, :sdp)");
    $stmt->execute([
        'n_proceso' => $arrayDatos['numeroProceso'],
        'tipo_proceso' => 'CALIBRADO',
        'fecha' => $fechaActual,
        'empresa' => $_SESSION['empresa'],
        'productor' => $arrayDatos['productor'],
        'especie' => $arrayDatos['especie'],
        'variedad' => $arrayDatos['variedad'],
        'folio' => $folioAsignado,
        'embalaje' => 'BIN',
        'categoria' => 'CAT1',
        'calibre' => $arrayDatos['cbColor'],
        'unidad' => 'UN',
        'kg_bruto' => $arrayDatos['txtKgBruto'],
        'tara' => '43',
        'cantidad' => '1',
        'kilos' => $arrayDatos['txtKgNeto'],
        'packing' => 'GRCFRUIT SPA',
        'estado' => 'EXISTENCIA',
        'reproceso' => '',
        'temporada' => TEMPORADA_ACTUAL,
        'n_despacho' => '',
        'desverdizado' => '',
        'ingreso_desverdizado' => '',
        'salida_desverdizado' => '',
        'sdp' => $arrayDatos['sdp']
    ]);
    if ($stmt->rowCount() > 0) {
        $stmtUpdate = $pdo->prepare("UPDATE asignacion_folios SET estado_folio = 'UTILIZADO' WHERE folio = :folio and empresa = :empresa and tipo_folio = 'CALIBRADO/PRIMARIO' and temporada = :temporada");
        $stmtUpdate->execute([
            'folio' => $folioAsignado,
            'empresa' => $_SESSION['empresa'],
            'temporada' => TEMPORADA_ACTUAL
        ]);

        $stmHistorico = $pdo->prepare("INSERT INTO `historico` (`folio_lote`, `tipo`, `cantidad`, `n_operacion`, `tipo_operacion`, `tipo_movimiento`, `usuario`, `empresa`, `temporada`)
        VALUES (:folio, :tipo, :cantidad, :n_operacion, :tipo_operacion, :tipo_movimiento, :usuario, :empresa, :temporada)");
        $stmHistorico->execute([
            'folio' => $folioAsignado,
            'tipo' => 'CALIBRADO/PRIMARIO',
            'cantidad' => $arrayDatos['txtKgNeto'],
            'n_operacion' => $arrayDatos['numeroProceso'],
            'tipo_operacion' => 'CALIBRADO',
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