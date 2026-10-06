<?php
//session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/api/get_procesos_normales.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/view/layouts/header.php';
if (!isset($_SESSION['username'])) {
    header('Location: /packing/index');
    exit();
}
?>

<div class="container-fluid">
    <div class="row mb-2">
        <h1 class="m-0 text-center text-primary">Procesos Normales Abiertos Disponibles</h1>
    </div>
    <div class="container">
        <table class="table table-bordered">
            <thead class="table-success">
                <tr>
                    <th scope="col">N° Proceso</th>
                    <th scope="col">Tipo Proceso</th>
                    <th scope="col">Empresa</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <?php


                    foreach ($procesosNormales ?? [] as $key => $value) :
                ?>
                        <tr>
                            <td><?= htmlspecialchars($value['n_proceso']); ?></td>
                            <td><?= htmlspecialchars($value['tipo_proceso']); ?></td>
                            <td><?= htmlspecialchars($value['empresa']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/packing/view/procesos/ingreso-folio-normal?proceso=<?php echo htmlspecialchars($value['n_proceso']); ?>" class="btn btn-success">Seleccionar</a>
                                </div>
                            </td>
                        </tr>
                <?php
                    endforeach;
                
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/view/layouts/footer.php'; ?>