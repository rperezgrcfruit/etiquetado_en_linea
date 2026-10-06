<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php'; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['n_proceso'], $_POST['lote'])) {
    
    $n_proceso = strtoupper(trim($_POST['n_proceso']));
    $lote = strtoupper(trim($_POST['lote']));
    $bins = trim($_POST['bins']);
    $kilos = trim($_POST['kilos']);
    $planta = trim($_POST['planta']);
    $fecha = trim($_POST['fecha']); // YYYY-MM-DD
    $estado = trim($_POST['estado']);
    $exportadora = $_POST['exportadora'];
    $temporada = TEMPORADA_ACTUAL;
    
    $tipo_proceso = "CALIBRADO";
    $tipo_lote = "GRANEL";
    $vacio = "";
    $cero = 0;

    try {
        // Iniciamos la transacción de seguridad
        $conexion->begin_transaction();

        // 1. Insertar en la tabla procesos (20 columnas según tu VB.NET)
        $sqlInsert = "INSERT INTO procesos VALUES(NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtInsert = $conexion->prepare($sqlInsert);
        if (!$stmtInsert) throw new Exception("Error preparando INSERT: " . $conexion->error);

        // ss s s s s ss s s s s ss s s s s
        $stmtInsert->bind_param("sssssssssssssssssss", 
            $n_proceso, $tipo_proceso, $fecha, $planta, $lote, $tipo_lote, $vacio, $vacio, 
            $exportadora, $bins, $kilos, $cero, $cero, $cero, $cero, $cero, $cero, $estado, $temporada
        );
        $stmtInsert->execute();

        // 2. Descontar inventario (actualizar_existencia_granel2)
        $sqlUpdate = "UPDATE recepcion_granel 
                      SET kilos = kilos - ?, bins = bins - ? 
                      WHERE lote = ? AND exportadora = ? AND temporada = ?";
        $stmtUpdate = $conexion->prepare($sqlUpdate);
        if (!$stmtUpdate) throw new Exception("Error preparando UPDATE: " . $conexion->error);

        $stmtUpdate->bind_param("ddsss", $kilos, $bins, $lote, $exportadora, $temporada);
        $stmtUpdate->execute();

        // Si ambas operaciones fueron exitosas, guardamos los cambios reales
        $conexion->commit();

        echo json_encode(['status' => 'OK', 'mensaje' => 'Lote Agregado Correctamente']);

    } catch (Exception $e) {
        $conexion->rollback(); // Si algo falla, revertimos todo
        error_log("agregar-lote error: " . $e->getMessage());
        echo json_encode(['status' => 'EXCEPTION', 'mensaje' => 'Error interno del servidor. Contacte al administrador.']);
    }
}
?>