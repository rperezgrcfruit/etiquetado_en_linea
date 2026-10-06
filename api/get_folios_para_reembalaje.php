<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    $empresa = $_SESSION['empresa'] ?? '';
    $temporada = TEMPORADA_ACTUAL;

    if (empty($empresa)) {
        echo json_encode(['error' => 'Empresa no definida en sesión']);
        exit();
    }

    // Buscar folios de exportación en estado EXISTENCIA que NO estén en reembalaje
    // y que pertenezcan a la empresa y temporada actual
    $query = "SELECT 
                ee.folio,
                ee.especie,
                ee.variedad,
                ee.productor,
                ee.productor_etiq,
                ee.embalaje,
                ee.calibre,
                ee.categoria,
                ee.cajasExis as cajas,
                ee.tipo_pallet,
                ee.estado,
                ee.reembalaje,
                ee.sdp,
                ee.csg,
                ROUND((emb.`peso neto` * ee.cajasExis), 2) as kilos,
                p.r_social as productor_nombre,
                p.csg as csg_productor
              FROM existencia_exportacion ee
              INNER JOIN embalajes emb ON ee.embalaje = emb.codigo AND emb.empresa = ee.empresa
              LEFT JOIN productores p ON ee.productor = p.codigo AND p.empresa = ee.empresa AND p.temporada = :temporada
              WHERE ee.empresa = :empresa
                AND ee.temporada = :temporada
                AND ee.estado = 'EXISTENCIA'
                AND (ee.reembalaje = '' OR ee.reembalaje IS NULL)
                AND emb.empresa = :empresa2
              ORDER BY ee.folio DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'empresa' => $empresa,
        'temporada' => $temporada,
        'empresa2' => $empresa
    ]);

    $folios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($folios);

} catch (PDOException $e) {
    error_log("get_folios_para_reembalaje error: " . $e->getMessage());
    echo json_encode(['error' => 'Error al buscar folios disponibles']);
}