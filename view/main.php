<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header('Location: /packing/');
    exit();
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/view/layouts/header.php';
?>

<div class="container py-4">
    <h1 class="m-0 text-center text-primary mb-4">Administracion de Procesos Packing</h1>

    <div class="row g-3 justify-content-center">
        <div class="col-lg-3 col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center p-4">
                    <i class="fas fa-boxes fa-2x text-secondary mb-2"></i>
                    <h5 class="card-title mb-3">Proceso Primario</h5>
                    <a href="/packing/view/procesos/seleccionar-proceso-primario" class="btn btn-secondary w-100">Ingresar Folios</a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center p-4">
                    <i class="fas fa-boxes fa-2x text-primary mb-2"></i>
                    <h5 class="card-title mb-3">Proceso Secundario</h5>
                    <a href="/packing/view/procesos/seleccionar-proceso-secundario" class="btn btn-primary w-100">Ingresar Folios</a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center p-4">
                    <i class="fas fa-boxes fa-2x text-warning mb-2"></i>
                    <h5 class="card-title mb-3">Proceso Normal</h5>
                    <a href="/packing/view/procesos/seleccionar-proceso-normal" class="btn btn-warning w-100">Ingresar Folios</a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-4 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-boxes fa-2x text-info mb-2"></i>
                            <h5 class="card-title mb-3">Proceso de Reembalaje</h5>
                            <a href="/packing/view/procesos/seleccionar-proceso-reembalaje" class="btn btn-info w-100">Ingresar Folios</a>
                        </div>
                    </div>
                </div>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/view/layouts/footer.php'; ?>