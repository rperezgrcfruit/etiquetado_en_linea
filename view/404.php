<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/view/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-2">
        <h1 class="m-0 text-center text-danger">Error 404 - Página No Encontrada</h1>
    </div>
    <div class="container text-center">
        <p class="lead">Lo sentimos, la página que estás buscando no existe.</p>
        <a href="/packing/view/main" class="btn btn-primary">Volver al Inicio</a>
    </div>  
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/view/layouts/footer.php'; ?>

