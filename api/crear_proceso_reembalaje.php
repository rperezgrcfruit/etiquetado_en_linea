<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Sesión expirada']);
    exit();
}

if (!isset($_POST['folio']) || empty($_POST['folio'])) {
    echo json_encode(['error' => 'Folio requerido']);
    exit();
}

$folioOrigen = strtoupper(trim($_POST['folio']));
$empresa = $_SESSION['empresa'] ?? '';
$temporada = TEMPORADA_ACTUAL;
$usuario = $_SESSION['username'];

if (empty($empresa)) {
    echo json_encode(['error' => 'Empresa no definida en sesión']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Obtener datos del folio de origen
    $queryFolio = "SELECT * FROM existencia_exportacion 
                   WHERE folio = :folio 
                   AND empresa = :empresa 
                   AND temporada = :temporada
                   AND estado = 'EXISTENCIA'
                   AND (reembalaje = '' OR reembalaje IS NULL)
                   LIMIT 1";
    $stmt = $pdo->prepare($queryFolio);
    $stmt->execute([
        'folio' => $folioOrigen,
        'empresa' => $empresa,
        'temporada' => $temporada
    ]);
    $folioData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$folioData) {
        $pdo->rollBack();
        echo json_encode(['error' => 'Folio no encontrado, ya está en reembalaje, o no está en estado EXISTENCIA']);
        exit();
    }

    // 2. Generar nuevo número de proceso REEMBALAJE
    // Usar el siguiente número disponible (MAX + 1) para REEMBALAJE
    $queryMax = "SELECT COALESCE(MAX(CAST(n_proceso AS UNSIGNED)), 0) + 1 as next_num 
                 FROM procesos 
                 WHERE tipo_proceso = 'REEMBALAJE' 
                 AND empresa = :empresa 
                 AND temporada = :temporada";
    $stmt = $pdo->prepare($queryMax);
    $stmt->execute(['empresa' => $empresa, 'temporada' => $temporada]);
    $nextNum = $stmt->fetchColumn();
    $nProceso = (string)$nextNum;

    // 3. Insertar en tabla procesos
    $planta = PLANTA_CENTRAL_CSP ?? '121293';
    $fechaHoy = date('Y-m-d');

    $sqlInsert = "INSERT INTO procesos (
        n_proceso, tipo_proceso, fecha, planta, lote, tipo_lote, 
        espe, varie, empresa, bins, kilos, cajas, kilos_expo, 
        kilos_com, deshidratacion, merma, desecho, estado, temporada
    ) VALUES (
        :n_proceso, 'REEMBALAJE', :fecha, :planta, :lote, 'PALLET',
        :espe, :varie, :empresa, :bins, :kilos, 0, 0, 0, 0, 0, 0, 'ABIERTO', :temporada
    )";

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        'n_proceso' => $nProceso,
        'fecha' => $fechaHoy,
        'planta' => $planta,
        'lote' => $folioOrigen,
        'espe' => $folioData['especie'] ?? '',
        'varie' => $folioData['variedad'] ?? '',
        'empresa' => $empresa,
        'bins' => $folioData['cajasExis'] ?? 0,
        'kilos' => $folioData['cajasExis'] * ($folioData['deshidratacion_caja'] > 0 ? $folioData['deshidratacion_caja'] : 0) ?? 0, // aproximado
        'temporada' => $temporada
    ]);

    // 4. Actualizar el folio de origen: marcar como REEMBALAJE
    $sqlUpdate = "UPDATE existencia_exportacion 
                  SET reembalaje = :n_proceso, estado = 'REEMBALAJE' 
                  WHERE folio = :folio AND empresa = :empresa AND temporada = :temporada";
    $stmt = $pdo->prepare($sqlUpdate);
    $stmt->execute([
        'n_proceso' => $nProceso,
        'folio' => $folioOrigen,
        'empresa' => $empresa,
        'temporada' => $temporada
    ]);

    // 5. Registrar en histórico
    $sqlHist = "INSERT INTO historico 
                (folio_lote, tipo, cantidad, n_operacion, tipo_operacion, fecha_hora, tipo_movimiento, usuario, empresa, temporada)
                VALUES (:folio, 'REEMBALAJE', 1, :n_proceso, 'REEMBALAJE', NOW(), 'CREACION_PROCESO', :usuario, :empresa, :temporada)";
    $stmt = $pdo->prepare($sqlHist);
    $stmt->execute([
        'folio' => $folioOrigen,
        'n_proceso' => $nProceso,
        'usuario' => $usuario,
        'empresa' => $empresa,
        'temporada' => $temporada
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'n_proceso' => $nProceso,
        'folio_origen' => $folioOrigen,
        'message' => "Proceso de reembalaje #{$nProceso} creado desde folio {$folioOrigen}"
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("crear_proceso_reembalaje error: " . $e->getMessage());
    echo json_encode(['error' => 'Error al crear el proceso de reembalaje']);
}