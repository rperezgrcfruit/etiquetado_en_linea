<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/api/get_procesos_primarios_detalle.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/api/get_colores_primario.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/api/get_folios_primario_ingresados.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/view/layouts/header.php';
$numeroProceso = $_GET['proceso'];
$procesosPrimarios = obtenerDetalleProcesoPrimario($numeroProceso);
$tipoProceso = 'CALIBRADO';
?>


<div class="row px-4 mb-2 align-items-center">
    <div class="col-auto">
        <a href="/packing/view/procesos/seleccionar-proceso-primario" type="button" class="btn btn-outline-danger">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-circle" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 1 0 1 8m15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"></path>
            </svg>
            Volver a Procesos
        </a>
    </div>
    <div class="col">
        <h1 class="m-0 text-center text-primary">Ingreso Folios a Proceso Primario</h1>
    </div>
    <div class="col-auto">
        <div class="process-info-card mb-0">
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <span><span class="info-label">N° Proceso:</span> <span class="info-value fw-bold text-primary"><?php echo htmlspecialchars($numeroProceso); ?></span></span>
                <span><span class="info-label">Productor:</span> <span class="info-value"><?php echo htmlspecialchars($procesosPrimarios[0]['productor'] ?? '-'); ?></span></span>
                <span><span class="info-label">Especie:</span> <span class="info-value"><?php echo htmlspecialchars($procesosPrimarios[0]['especie'] ?? '-'); ?></span></span>
                <span><span class="info-label">Variedad:</span> <span class="info-value"><?php echo htmlspecialchars($procesosPrimarios[0]['variedad'] ?? '-'); ?></span></span>
                <span><span class="info-label">Lote:</span> <span class="info-value"><?php echo htmlspecialchars($procesosPrimarios[0]['lote'] ?? '-'); ?></span></span>
                <span><span class="info-label">SDP:</span> <span class="info-value"><?php echo htmlspecialchars($procesosPrimarios[0]['sdp'] ?? '-'); ?></span></span>
            </div>
        </div>
    </div>
</div>

<div class="row p-4">
    <input type="hidden" id="inputEmpresa" value="<?php echo $_SESSION['empresa']; ?>">
    <div class="row mt-4">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showContent('datosProceso')">Datos Proceso</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showContent('binsPrimario')">Bins Primario</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showContent('ingresoComercial')">Ingreso Comercial</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showContent('resumenProceso')">Resumen Proceso</a>
            </li>
        </ul>
    </div>

    <!-- Contenidos -->
    <div id="datosProceso" class="tab-content p-2">
        <h3>Contenido de Datos Proceso</h3>
        <table class="table table-bordered sortable-table">
            <thead class="table-success">
                <tr>
                    <th scope="col">Lote</th>
                    <th scope="col">Productor</th>
                    <th scope="col">Bins</th>
                    <th scope="col">Kilos</th>
                    <th scope="col">Guia</th>
                    <th scope="col">Fecha Recepcion</th>
                    <th scope="col">CSG</th>
                    <th scope="col">SDP</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <?php

                if (!empty($procesosPrimarios)) {
                    //print_r($procesosPrimarios);
                    foreach ($procesosPrimarios as $key => $value) :
                ?>
                        <tr>
                            <td><?= $value['lote'] ?></td>
                            <td><?= $value['productor'] ?></td>
                            <td><?= $value['cant_bins'] ?></td>
                            <td><?= $value['kilos_recep'] ?></td>
                            <td><?= $value['guia'] ?></td>
                            <td><?= $value['fecha'] ?></td>
                            <td><?= $value['csg'] ?></td>
                            <td><?= $value['sdp'] ?></td>
                        </tr>
                <?php
                    endforeach;
                } else {
                    echo 'No hay datos';
                }
                ?>
            </tbody>
        </table>
    </div>

    <div id="binsPrimario" class="tab-content p-2" style="display:none;">
        <!-- Button trigger modal -->
        <div class="row mt-2">
            <div class="col-sm-4">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                    Agregar Folio
                </button>
            </div>
            <div class="col-sm-4 d-flex justify-content-center align-items-center">
                <h5 class="text-success">Total Kilos: <?php echo $totalKilosPrimario ?? 0; ?></h5>
            </div>
        </div>

        <div id="" class="row mt-2">
            <h3 class="text-center">Bins Ingresados</h3>
            <table class="table table-bordered sortable-table" id="table-folios-expo-completos">
                <thead class="table-success">
                    <tr>
                        <th scope="col">Folio</th>
                        <th scope="col">Especie</th>
                        <th scope="col">Variedad</th>
                        <th scope="col">Color</th>
                        <th scope="col">Categoria</th>
                        <th scope="col">Kilos</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody class="table-group-divider">
                    <?php
                    foreach ($primarioIngresados ?? [] as $key => $value) :
                    ?>
                        <tr>
                            <td><?= $value['folio'] ?></td>
                            <td><?= $value['especie'] ?></td>
                            <td><?= $value['variedad'] ?></td>
                            <td><?= $value['calibre'] ?></td>
                            <td><?= $value['categoria'] ?></td>
                            <td><?= $value['kilos'] ?></td>
                            <td><a href="" id="btnImprimirPrimario" class="btnVer btn btn-success btn-sm btnImprimirPrimario">Imprimir</a></i></td>
                        </tr>
                    <?php
                    endforeach;

                    ?>
                </tbody>
            </table>
        </div>

    </div>

    <div id="ingresoComercial" class="tab-content p-2" style="display:none;">
        <!-- Button trigger modal -->
        <div class="row mt-2">
            <div class="col-sm-4">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#staticBackdrop2">
                    Agregar Folio
                </button>
            </div>
        </div>

        <div id="" class="row">
            <h3 class="text-center">Folios Ingresados</h3>
            <h4 class="text-center text-success">Total Kilos: <?php echo $totalKilosCom ?? 0; ?></h4>
            <table class="table table-bordered sortable-table">
                <thead class="table-success">
                    <tr>
                        <th scope="col">Folio</th>
                        <th scope="col">Envase</th>
                        <th scope="col">Calibre</th>
                        <th scope="col">Unidad</th>
                        <th scope="col">Kilos</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody class="table-group-divider">
                    <?php
                    foreach ($primarioIngresadosComercial ?? [] as $key => $value) :
                    ?>
                        <tr>
                            <td><?= $value['folio'] ?></td>
                            <td><?= $value['envase'] ?></td>
                            <td><?= $value['calibre'] ?></td>
                            <td><?= $value['unidad'] ?></td>
                            <td><?= $value['kilos'] ?></td>
                            <td><a href="" class="btnImprimirComercial btn btn-success btn-sm" id="btnImprimirComercial">Imprimir</a></i></td>
                        </tr>
                    <?php
                    endforeach;
                    ?>
                </tbody>
            </table>
        </div>

    </div>

    <div id="resumenProceso" class="tab-content" style="display:none;">
        <div id="" class="row">
            <h3 class="text-center mt-2">Resumen Procesos</h3>
            <table class="table table-bordered">
                <thead class="table-success">
                    <tr>
                        <th scope="col">Kilos Entrada</th>
                        <th scope="col">Kilos Exportacion</th>
                        <th scope="col">Kilos Comercial</th>
                        <th scope="col">Deshidratacion Expo.</th>
                        <th scope="col">Total</th>
                        <th scope="col">Merma</th>
                        <th scope="col">% Merma</th>
                    </tr>
                </thead>
                <tbody class="table-group-divider">
                    <tr>
                        <td><?php echo $detalleProceso['kilosEntrada'] ?? 0;  ?></td>
                        <td><?php echo $detalleProceso['kilosPrimario'] ?? 0; ?></td>
                        <td><?php echo $detalleProceso['kilosComercial'] ?? 0; ?></td>
                        <td><?php echo $detalleProceso['deshidratacion'] ?? 0; ?></td>
                        <td><?php echo $detalleProceso['kilosPrimario'] ?? 0; ?></td>
                        <td class="text-danger"><?php //echo $merma 
                                                ?></td>
                        <td class="text-danger"><?php //echo $porcentajeMerma 
                                                ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

</div>



<!-- Modal Ingreso primario -->
<div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="staticBackdropLabel">Ingreso Nuevo Bin</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="row mt-1" method="post" id="form-ingreso-primario" action="/packing/backend/nuevo-folio-primario">
                <?= csrfField(); ?>
                    <input type="hidden" id="inputNumeroProceso" name="numeroProceso" value="<?php echo $numeroProceso; ?>">
                    <input type="hidden" id="inputProductor" name="productor" value="<?php echo $procesosPrimarios[0]['productor'] ?? ''; ?>">
                    <input type="hidden" id="inputFecha" name="fecha" value="<?php echo $procesosPrimarios[0]['fecha'] ?? ''; ?>">
                    <input type="hidden" id="inputEspecie" name="especie" value="<?php echo $procesosPrimarios[0]['especie'] ?? ''; ?>">
                    <input type="hidden" id="inputVariedad" name="variedad" value="<?php echo $procesosPrimarios[0]['variedad'] ?? ''; ?>">
                    <input type="hidden" id="inputSdp" name="sdp" value="<?php echo $procesosPrimarios[0]['sdp'] ?? ''; ?>">
                    <!-- <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputSelect3" class="col-form-label">Categoria</label>
                        </div>
                        <div class="col-auto">
                            <select class="form-select" aria-label="Default select example" id="inputSelect3" name="cbEmbalaje">
                                <option selected>Seleccionar Categoria</option>
                                <option value="CAT1">CAT1</option>
                            </select>
                        </div>
                    </div> -->

                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputSelect4" class="col-form-label">Color</label>
                        </div>
                        <div class="col-auto">
                            <select class="form-select" aria-label="Default select example" id="inputSelect4" name="cbColor">
                                <option selected>Seleccionar Color</option>
                                <?php

                                foreach ($colores ?? [] as $key => $value):
                                ?>
                                    <option value="<?= htmlspecialchars($value['color']); ?>"><?= htmlspecialchars($value['color']); ?></option>
                                <?php
                                endforeach;
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputTextKgBruto" class="col-form-label">Kg Bruto</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" class="form-control" name="txtKgBruto" id="inputTextKgBruto" value="0">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputTextKgNeto" class="col-form-label">Kg Neto</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" class="form-control" name="txtKgNeto" id="inputTextKgNeto" value="0">
                        </div>
                    </div>

                    <div class="col-md-12 mt-2">
                        <div class="col-auto">
                            <button type="submit" class="btn btn-success form-control btn-form-send">Ingresar</button>
                        </div>

                    </div>


                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="staticBackdrop2" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="staticBackdropLabel">Ingreso Nuevo Folio Comercial</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="row mt-1" id="form-ingreso-comercial" method="post" action="/packing/backend/nuevo-folio-comercial">
                <?= csrfField(); ?>
                    <input type="hidden" id="inputNumeroProceso" name="numeroProceso" value="<?php echo $numeroProceso; ?>">
                    <input type="hidden" id="inputProductor" name="productor" value="<?php echo $procesosPrimarios[0]['productor'] ?? ''; ?>">
                    <input type="hidden" id="inputFecha" name="fecha" value="<?php echo $procesosPrimarios[0]['fecha'] ?? ''; ?>">
                    <input type="hidden" id="inputEspecie" name="especie" value="<?php echo $procesosPrimarios[0]['especie'] ?? ''; ?>">
                    <input type="hidden" id="inputVariedad" name="variedad" value="<?php echo $procesosPrimarios[0]['variedad'] ?? ''; ?>">
                    <input type="hidden" id="inputSdp" name="sdp" value="<?php echo $procesosPrimarios[0]['sdp'] ?? ''; ?>">
                    <input type="hidden" id="inputTipoProceso" name="tipo_proceso" value="<?php echo $tipoProceso; ?>">
                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputSelect6" class="col-form-label">Categoria</label>
                        </div>
                        <div class="col-auto">
                            <select class="form-select" aria-label="Default select example" id="inputSelect6" name="cbCategoriaComercial">
                                <option selected>Seleccionar Categoria</option>
                                <option value="COM">COMERCIAL</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputSelect4" class="col-form-label">Calibre</label>
                        </div>
                        <div class="col-auto">
                            <select class="form-select" aria-label="Default select example" id="inputSelect4" name="cbCalibreComercial">
                                <option selected>Seleccionar Calibre</option>
                                <option value="COM1">COM1</option>
                                <option value="COM2">COM2</option>
                                <option value="ANARANJADO">ANARANJADO</option>
                                <option value="SOBRE CALIBRE">SOBRE CALIBRE</option>
                                <option value="PRE CALIBRE">PRE CALIBRE</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputTextCajas" class="col-form-label">Kg Bruto</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" class="form-control inputTextKgBrutoCom" name="inputTextKgBrutoCom" id="inputTextKgBrutoCom" value="0">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputTextCajas" class="col-form-label">Kg Neto</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" class="form-control inputTextKgNetoCom" name="inputTextKgNetoCom" id="inputTextKgNetoCom" value="0" readonly>
                        </div>
                    </div>

                    <div class="col-md-12 mt-2">
                        <div class="col-auto">
                            <button type="submit" class="btn btn-success form-control">Ingresar</button>
                        </div>

                    </div>


                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

</div><!-- /.container-fluid -->

<script>
    $modalCloseButtons = document.querySelectorAll('.modal-close');
    $modalCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Limpiar formulario del modal de ingreso exportación
            document.getElementById('form-ingreso-expo').reset();
            // Limpiar formulario del modal de ingreso comercial
            document.getElementById('form-ingreso-comercial').reset();
        });
    });

    let pesoBrutoInput = document.getElementById('inputTextKgBruto');
    let pesoNetoInput = document.getElementById('inputTextKgNeto');
    let tara = <?php echo BIN_TARA; ?>; // Tara fija para el bin
    pesoBrutoInput.addEventListener('input', function() {
        let pesoBruto = parseFloat(pesoBrutoInput.value) || 0;
        let tara = parseFloat(<?php echo BIN_TARA; ?>);
        let pesoNeto = pesoBruto - tara;
        pesoNetoInput.value = pesoNeto > 0 ? pesoNeto : 0;
    });


    let pesoBrutoComInput = document.getElementById('inputTextKgBrutoCom');
    let pesoNetoComInput = document.getElementById('inputTextKgNetoCom');
    pesoBrutoComInput.addEventListener('input', function() {
        let pesoBruto = parseFloat(pesoBrutoComInput.value) || 0;
        let tara = <?php echo BIN_TARA; ?>;
        let pesoNeto = pesoBruto - tara;
        pesoNetoComInput.value = pesoNeto > 0 ? pesoNeto : 0;
    });

    document.getElementById('form-ingreso-primario').addEventListener('submit', function() {
        document.getElementById("spinner").style.display = "block";
        document.getElementById("overlay").style.display = "block";
    })

    document.getElementById('form-ingreso-comercial').addEventListener('submit', function() {
        document.getElementById("spinner").style.display = "block";
        document.getElementById("overlay").style.display = "block";
    })

    document.querySelectorAll('.btnImprimirPrimario').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();

            // 1. Mostrar Spinner y Overlay
            document.getElementById("spinner").style.display = "block";
            document.getElementById("overlay").style.display = "block";

            const folio = this.closest('tr').querySelector('td:first-child').textContent;
            const xhr = new XMLHttpRequest();

            xhr.open('GET', '/packing/backend/impresion-etiqueta-primario?folio=' + encodeURIComponent(folio), true);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    // Solo ocultamos el spinner cuando la petición terminó (ready 4)
                    setTimeout(function() {
                        document.getElementById("spinner").style.display = "none";
                        document.getElementById("overlay").style.display = "none";
                    }, 2000);

                    if (xhr.status === 200) {
                        console.log(xhr.responseText);
                        console.log('Etiqueta impresa correctamente');
                    } else {
                        console.error('Error al imprimir la etiqueta');
                    }
                }
            };

            xhr.send();
        });
    });

    document.querySelectorAll('.btnImprimirComercial').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();

            // 1. Mostrar Spinner y Overlay
            document.getElementById("spinner").style.display = "block";
            document.getElementById("overlay").style.display = "block";

            const folio = this.closest('tr').querySelector('td:first-child').textContent;
            const xhr = new XMLHttpRequest();

            xhr.open('GET', '/packing/backend/impresion-etiqueta-comercial?folio=' + encodeURIComponent(folio), true);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    // Solo ocultamos el spinner cuando la petición terminó (ready 4)
                    setTimeout(function() {
                        document.getElementById("spinner").style.display = "none";
                        document.getElementById("overlay").style.display = "none";
                    }, 2000);

                    if (xhr.status === 200) {
                        console.log(xhr.responseText);
                        console.log('Etiqueta impresa correctamente');
                    } else {
                        console.error('Error al imprimir la etiqueta');
                    }
                }
            };

            xhr.send();
        });
    });
</script>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/view/layouts/footer.php'; ?>