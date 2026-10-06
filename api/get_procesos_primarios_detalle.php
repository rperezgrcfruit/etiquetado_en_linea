<?php
//session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';

function obtenerDetalleProcesoPrimario($n_proceso)
{
    global $pdo;
    $temporadaActual = TEMPORADA_ACTUAL;
    // Obtener los procesos primarios abiertos
    $query = "SELECT * FROM procesos INNER JOIN recepcion_granel ON procesos.lote = recepcion_granel.lote WHERE n_proceso = :n_proceso AND estado = 'ABIERTO' AND tipo_proceso = 'calibrado'
AND empresa = :empresa
and recepcion_granel.temporada = :receptemporada 
AND recepcion_granel.exportadora = :exportadoraempresa
AND procesos.temporada = :temporada";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'n_proceso' => $n_proceso,
        'empresa' => $_SESSION['empresa'],
        'temporada' => $temporadaActual,
        'receptemporada' => $temporadaActual,
        'exportadoraempresa' => $_SESSION['empresa']

    ]);
    $procesosPrimarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $procesosPrimarios;
    // Devolver los resultados en formato JSON
    // header('Content-Type: application/json');
    // echo json_encode($procesos);
}
