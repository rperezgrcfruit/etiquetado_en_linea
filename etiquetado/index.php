<?php
require_once 'config/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    $sql = 'SELECT R_SOCIAL FROM empresa WHERE temporada = :temporada AND estado = 1 ORDER BY R_SOCIAL';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['temporada' => TEMPORADA_ACTUAL]);
    $empresas = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (\Throwable $th) {
    error_log("etiquetado/index error: " . $th->getMessage());
    echo 'Error al cargar la configuración. Contacte al administrador.';
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Salidas - GRC Fruit Packing</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/configSalidas.css?v=<?php echo filemtime(__DIR__ . '/assets/css/configSalidas.css'); ?>">
</head>
<body>

    <header class="cs-header">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-cube fs-1"></i>
                <div>
                    <div class="cs-header-logo">Configuración de Salidas</div>
                    <div class="cs-header-subtitle">Packing Line - Estaciones de Impresión ZPL</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-light text-dark fs-12">
                    Temporada: <?php echo htmlspecialchars(TEMPORADA_ACTUAL ?? date('Y')); ?>
                </span>
                <a href="/packing/view/main.php" class="text-white text-decoration-none fs-14">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </header>

    <div class="container-fluid py-3" style="max-width: 1400px; margin: 0 auto;">

        <div class="cs-toolbar">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <label class="mb-0">Empresa:</label>
                    <select id="comboEmpresa" class="form-select form-select-sm" style="min-width: 180px;">
                        <option value="">Seleccionar Empresa...</option>
                        <?php foreach ($empresas as $emp): ?>
                            <option value="<?= htmlspecialchars($emp) ?>">
                                <?= htmlspecialchars($emp) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <label class="mb-0">Proceso:</label>
                    <select id="comboProceso" class="form-select form-select-sm" style="min-width: 200px;" disabled>
                        <option value="">Seleccionar Proceso...</option>
                    </select>
                </div>

                <button id="btn-bloquear" class="cs-btn-action btn-refresh" type="button" disabled>
                    <i class="fas fa-lock"></i> Bloquear
                </button>

                <button id="btn-desbloquear" class="cs-btn-action btn-reset" type="button" hidden>
                    <i class="fas fa-unlock"></i> Desbloquear
                </button>
            </div>

            <div class="d-flex align-items-center gap-3 flex-wrap">
                            <button id="btnResetFiltros" class="cs-btn-action btn-reset">
                                <i class="fas fa-redo"></i> Limpiar
                            </button>
                        </div>
        </div>

        <div class="cs-stats" id="stats-container">
            <div class="cs-stat activo">
                <div class="num" id="stat-activo">0</div>
                <div class="lbl">Configuradas</div>
            </div>
            <div class="cs-stat sin-config">
                <div class="num" id="stat-pendiente">0</div>
                <div class="lbl">Sin Configurar</div>
            </div>
            <div class="cs-stat error">
                <div class="num" id="stat-error">0</div>
                <div class="lbl">Error</div>
            </div>
            <div class="cs-stat">
                <div class="num" id="stat-total">0</div>
                <div class="lbl">Total</div>
            </div>
        </div>

        <div class="cs-grid-salidas" id="contenedorSalidas">
            <div class="text-center py-5 text-muted">
                <i class="fas fa-box-open fa-2x mb-2"></i>
                <p>Selecciona una empresa y proceso, luego haz clic en <strong>Bloquear</strong> para cargar las salidas.</p>
            </div>
        </div>

    </div>

    <div class="cs-overlay" id="overlay">
        <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>

    <div class="modal fade cs-modal" id="modalSalidas" tabindex="-1" aria-labelledby="modalSalidasLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalSalidasLabel">
                        <i class="fas fa-cog me-2"></i>Configurar Salida
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="idSalida" name="idSalida">
                    <input type="hidden" id="inputTipoProceso" name="inputTipoProceso">
                    <input type="hidden" id="inputLote" name="inputLote">

                    <div class="alert alert-light py-2 mb-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Edita los campos y guarda la configuración.
                        </small>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <label for="embalaje" class="form-label">
                                <i class="fas fa-boxes-stacked me-1"></i>Embalaje
                            </label>
                            <select id="embalaje" class="form-select"></select>
                        </div>
                        <div class="form-col">
                            <label for="calibre" class="form-label">
                                <i class="fas fa-ruler-combined me-1"></i>Calibre
                            </label>
                            <select id="calibre" class="form-select"></select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="categoria" class="form-label">
                            <i class="fas fa-tag me-1"></i>Categoría
                        </label>
                        <select id="categoria" class="form-select"></select>
                    </div>

                    <div class="mt-3">
                        <label for="tipoProcesoDisplay" class="form-label">
                            <i class="fas fa-sitemap me-1"></i>Tipo de Proceso
                        </label>
                        <input type="text" id="tipoProcesoDisplay" class="form-control" readonly value="NORMAL">
                    </div>

                    <div class="mt-3">
                        <label for="impresoraZebra" class="form-label">
                            <i class="fas fa-print me-1"></i>Impresora Zebra
                        </label>
                        <select id="impresoraZebra" class="form-select"></select>
                    </div>

                    <div class="mt-3" id="validationIndicator" style="display: none;">
                        <div class="alert alert-sm mb-0 py-2" id="validationMsg">
                            <i class="fas me-1"></i><span></span>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-success" id="btGuardarSalidas">
                        <i class="fas fa-save me-1"></i>Guardar
                    </button>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>
    <script src="assets/js/configSalidas.js?v=<?php echo filemtime(__DIR__ . '/assets/js/configSalidas.js'); ?>"></script>
</body>
</html>