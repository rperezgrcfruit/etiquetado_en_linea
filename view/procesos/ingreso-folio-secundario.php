<?php
session_start();

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header('Location: /packing/index');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/api/get_proceso_secundario_detalle.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/api/get_parametros_folio_expo.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/view/layouts/header.php';
$numeroProceso = $_GET['proceso'];
$procesosSecundarios = obtenerDetalleProcesoSecundario($numeroProceso);
//print_r($procesosSecundarios);
$detalleProceso = obtenerDetalleProcesoSecundarioExpo($numeroProceso);
$detalleProcesoCom = obtenerDetalleProcesoSecundarioCom($numeroProceso);
$embalajes = GetEmbalaje($procesosSecundarios[0]['especie'], $_SESSION['empresa']);
$calibres = GetCalibres($procesosSecundarios[0]['especie'], $_SESSION['empresa']);
$etiquetas = GetEtiquetas($procesosSecundarios[0]['especie'], $_SESSION['empresa']);
$categorias = GetCategorias($procesosSecundarios[0]['especie'], $_SESSION['empresa']);
$plu = GetPlu($procesosSecundarios[0]['especie'], $_SESSION['empresa']);
$variedades = GetVariedades($procesosSecundarios[0]['especie'], $_SESSION['empresa']);
$csg = $procesosSecundarios[0]['csg'] ?? '';
$tipoProceso = "REPROCESO";
?>

<div class="row px-4 mb-2 align-items-center">
    <div class="col-auto">
        <a href="/packing/view/procesos/seleccionar-proceso-secundario" type="button" class="btn btn-outline-success">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-circle" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8m15 0A8 8 0 1 1 0 8a8 8 0 1 16 0m-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5z"></path>
            </svg>
            Volver a Procesos
        </a>
    </div>
    <div class="col">
        <h1 class="m-0 text-center text-primary">Ingreso Folios a Proceso Secundario</h1>
    </div>
    <div class="col-auto">
        <div class="process-info-card mb-0">
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <span><span class="info-label">N° Proceso:</span> <span class="info-value fw-bold text-primary"><?php echo htmlspecialchars($numeroProceso); ?></span></span>
                <span><span class="info-label">Productor:</span> <span class="info-value"><?php echo htmlspecialchars($procesosSecundarios[0]['productor'] ?? '-'); ?></span></span>
                <span><span class="info-label">Especie:</span> <span class="info-value"><?php echo htmlspecialchars($procesosSecundarios[0]['especie'] ?? '-'); ?></span></span>
                <span><span class="info-label">Variedad:</span> <span class="info-value"><?php echo htmlspecialchars($procesosSecundarios[0]['variedad'] ?? '-'); ?></span></span>
                <span><span class="info-label">SDP:</span> <span class="info-value"><?php echo htmlspecialchars($procesosSecundarios[0]['sdp'] ?? '-'); ?></span></span>
                <span><span class="info-label">CSG:</span> <span class="info-value"><?php echo htmlspecialchars($procesosSecundarios[0]['csg'] ?? '-'); ?></span></span>
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
                <a class="nav-link" href="#" onclick="showContent('binsPrimario')">Ingreso Cajas</a>
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
                    <th scope="col">Kilos</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <?php
                $totalkilosEntrada = 0;
                if (!empty($procesosSecundarios)) {
                    //print_r($procesosSecundarios);
                    foreach ($procesosSecundarios as $key => $value) :
                        $totalkilosEntrada += $value['kilos'];
                ?>
                        <tr>
                            <td><?= $value['folio'] ?></td>
                            <td><?= $value['productor'] ?></td>
                            <td><?= $value['kilos'] ?></td>
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
            <h3 class="text-center">Folios Ingresados</h3>
            <table class="table table-bordered sortable-table" id="table-folios-expo-completos">
                <thead class="table-success">
                    <tr>
                        <th scope="col">Folio</th>
                        <th scope="col">Tipo Pallet</th>
                        <th scope="col">Especie</th>
                        <th scope="col">Variedad</th>
                        <th scope="col">Embalaje</th>
                        <th scope="col">Calibre</th>
                        <th scope="col">Categoria</th>
                        <th scope="col">Cajas</th>
                        <th scope="col">Kilos</th>
                        <th scope="col">SDP</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody class="table-group-divider">
                    <?php
                    $totalkilosexpo = 0;
                    $deshidratacionTotal = 0;
                    foreach ($detalleProceso ?? [] as $key => $value) :
                        $totalkilosexpo += $value['kilosExportacion'];
                        $deshidratacionTotal += $value['deshidratacion_total'];
                    ?>
                        <tr>
                            <td><?= $value['folio'] ?></td>
                            <td><?= $value['tipo_pallet'] ?></td>
                            <td><?= $value['especie'] ?></td>
                            <td><?= $value['variedad'] ?></td>
                            <td><?= $value['embalaje'] ?></td>
                            <td><?= $value['calibre'] ?></td>
                            <td><?= $value['categoria'] ?></td>
                            <td><?= $value['cajas'] ?></td>
                            <td><?= $value['kilosExportacion'] ?></td>
                            <td style="display:none;"><?= $value['deshidratacion_total'] ?></td>
                            <td><?= $value['sdp'] ?></td>
                            <td><a href="" id="btnImprimirFolioSecundario" class="btnVer btn btn-success btn-sm btnImprimirFolioSecundario">Imprimir</a></i></td>
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
                    $totalKilosCom = 0;
                    foreach ($detalleProcesoCom ?? [] as $key => $value) :
                        $totalKilosCom += $value['kilos'];
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
                        <td><?php echo $totalkilosEntrada ?? 0;  ?></td>
                        <td><?php echo $totalkilosexpo ?? 0; ?></td>
                        <td><?php echo $totalKilosCom ?? 0; ?></td>
                        <td><?php echo $deshidratacionTotal ?? 0; ?></td>
                        <td><?php echo round($totalkilosexpo + $totalKilosCom + $deshidratacionTotal, 2) ?? 0; ?></td>
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



<!-- Modal Ingreso folio -->
<div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="staticBackdropLabel">Ingreso Nuevo Folio</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="row mt-1" method="post" id="form-ingreso-expo" action="/packing/backend/ingreso-folio?proceso=<?php echo $numeroProceso  ?>">
                <?= csrfField(); ?>
                    <input type="hidden" id="inputProductor" name="productor" value="<?php echo $procesosSecundarios[0]['productor'] ?? ''; ?>">
                    <input type="hidden" id="inputFecha" name="fecha" value="<?php echo $procesosSecundarios[0]['fecha'] ?? ''; ?>">
                    <input type="hidden" id="inputEspecie" name="especie" value="<?php echo $procesosSecundarios[0]['especie'] ?? ''; ?>">
                    <input type="hidden" id="inputVariedad" name="variedad" value="<?php echo $procesosSecundarios[0]['variedad'] ?? ''; ?>">
                    <input type="hidden" id="inputSdp" name="sdp" value="<?php echo $procesosSecundarios[0]['sdp'] ?? ''; ?>">
                    <input type="hidden" id="inputTipoProceso" name="tipo_proceso" value="<?php echo $tipoProceso; ?>">
                    <input type="hidden" id="deshidratacion" name="deshidratacion" value="">
                    <input type="hidden" id="cajasPallet" name="cajasPallet" value="">
                    <input type="hidden" id="inputCsg" name="csg" value="<?php echo $csg; ?>">

                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputSelect4" class="col-form-label">Variedad</label>
                        </div>
                        <div class="col-auto">
                            <select class="form-select" aria-label="Default select example" id="inputSelect4" name="cbVariedad">
                                <option selected>Seleccionar Variedad</option>
                                <?php
                                if (!empty($variedades)) {
                                    foreach ($variedades as $key => $value):
                                ?>
                                        <option value="<?= $value['variedad']; ?>"><?= $value['variedad']; ?></option>
                                <?php
                                    endforeach;
                                }
                                ?>
                            </select>
                        </div>
                    </div>


                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputSelect3" class="col-form-label">Embalaje</label>
                        </div>
                        <div class="col-auto">
                            <select class="form-select" aria-label="Default select example" id="inputSelect3" name="cbEmbalaje" onchange="
                             { document.getElementById('deshidratacion').value = this.options[this.selectedIndex].getAttribute('data-deshidratacion');
                              document.getElementById('cajasPallet').value = this.options[this.selectedIndex].getAttribute('data-cajasPallet');}">
                                <option selected>Seleccionar Embalaje</option>
                                <?php
                                if (!empty($embalajes)) {
                                    foreach ($embalajes as $key => $value):
                                ?>
                                        <option value="<?= $value['codigo']; ?>" data-deshidratacion="<?= $value['deshidratacion']; ?>" data-cajasPallet="<?= $value['cajas_pallet']; ?>"><?= $value['codigo'];  ?></option>
                                <?php
                                    endforeach;
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputSelect4" class="col-form-label">Calibre</label>
                        </div>
                        <div class="col-auto">
                            <select class="form-select" aria-label="Default select example" id="inputSelect4" name="cbCalibre">
                                <option selected>Seleccionar Calibre</option>
                                <?php
                                if (!empty($calibres)) {
                                    foreach ($calibres as $key => $value):
                                ?>
                                        <option value="<?= $value['calibre']; ?>"><?= $value['calibre']; ?></option>
                                <?php
                                    endforeach;
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputSelect5" class="col-form-label">Etiqueta</label>
                        </div>
                        <div class="col-auto">
                            <select class="form-select" aria-label="Default select example" id="inputSelect5" name="cbEtiqueta">
                                <option selected>Seleccionar Etiqueta</option>
                                <?php
                                if (!empty($etiquetas)) {
                                    foreach ($etiquetas as $key => $value):
                                ?>
                                        <option value="<?= $value['descripcion']; ?>"><?= $value['descripcion']; ?></option>
                                <?php
                                    endforeach;
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputSelect6" class="col-form-label">Categoria</label>
                        </div>
                        <div class="col-auto">
                            <select class="form-select" aria-label="Default select example" id="inputSelect6" name="cbCategoria">
                                <option selected>Seleccionar Categoria</option>
                                <?php
                                if (!empty($categorias)) {
                                    foreach ($categorias as $key => $value):
                                ?>
                                        <option value="<?= $value['CATEGORIA']; ?>"><?= $value['CATEGORIA']; ?></option>
                                <?php
                                    endforeach;
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputSelec7" class="col-form-label">PLU</label>
                        </div>
                        <div class="col-auto">
                            <select class="form-select" aria-label="Default select example" id="inputSelec7" name="cbPlu">
                                <option selected>Seleccionar PLU</option>
                                <?php
                                if (!empty($plu)) {
                                    foreach ($plu as $key => $value):
                                ?>
                                        <option value="<?= $value['plu']; ?>"><?= $value['plu']; ?></option>
                                <?php
                                    endforeach;
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputSelec7" class="col-form-label">Sdp</label>
                        </div>
                        <div class="col-auto">
                            <select class="form-select" aria-label="Default select example" id="inputSdp2" name="cbSdp">
                                <option selected>Seleccionar SDP</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputSelec8" class="col-form-label">Tipo Pallet</label>
                        </div>
                        <div class="col-auto">
                            <select class="form-select cbTipoPallet" aria-label="Default select example" id="inputSelec8" name="cbTipoPallet">
                                <option selected>Tipo Pallet</option>
                                <option value="COMPLETO">Completo</option>
                                <option value="SALDO">Saldo</option>
                                <option value="MUESTRA">Muestra</option>
                                <option value="DIFERENCIA">Diferencia</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="col-auto">
                            <label for="inputTextCajas" class="col-form-label">Cajas</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" class="form-control" name="txtCajas" id="inputTextCajas">
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
                    <input type="hidden" id="inputProductor" name="productor" value="<?php echo $procesosSecundarios[0]['productor'] ?? ''; ?>">
                    <input type="hidden" id="inputFecha" name="fecha" value="<?php echo $procesosSecundarios[0]['fecha'] ?? ''; ?>">
                    <input type="hidden" id="inputEspecie" name="especie" value="<?php echo $procesosSecundarios[0]['especie'] ?? ''; ?>">
                    <input type="hidden" id="inputVariedad" name="variedad" value="<?php echo $procesosSecundarios[0]['variedad'] ?? ''; ?>">
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
                                <option value="COM">COM</option>
                                <option value="COMESPE">COM ESPE</option>
                                <option value="PRECA">PRECA</option>>
                                <option value="COMMEDIANO">COM MEDIANO</option>>
                                <option value="COMGRANDE">COM GRANDE</option>>
                                <option value="COMCHICO">COM CHICO</option>>
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

    //VALIDAR FORMULARIO DE INGRESO DE FOLIO SECUNDARIO

    document.addEventListener('DOMContentLoaded', function() {
        const formIngresoExpo = document.getElementById('form-ingreso-expo');
        formIngresoExpo.addEventListener('submit', function(event) {
            let isValid = true;

            // Validar que se haya seleccionado un embalaje
            const embalaje = document.getElementById('inputSelect3').value;
            if (embalaje === "Seleccionar Embalaje") {
                alert('Por favor, seleccione un embalaje.');
                isValid = false;
            }

            // Validar que se haya seleccionado un calibre
            const calibre = document.getElementById('inputSelect4').value;
            if (calibre === "Seleccionar Calibre") {
                alert('Por favor, seleccione un calibre.');
                isValid = false;
            }

            // Validar que se haya seleccionado una categoría
            const categoria = document.getElementById('inputSelect6').value;
            if (categoria === "Seleccionar Categoria") {
                alert('Por favor, seleccione una categoría.');
                isValid = false;
            }

            // Validar que se haya seleccionado un PLU
            const plu = document.getElementById('inputSelec7').value;
            if (plu === "Seleccionar PLU") {
                alert('Por favor, seleccione un PLU.');
                isValid = false;
            }

            // Validar que se haya seleccionado un SDP
            const sdp = document.getElementById('inputSdp2').value;
            if (sdp === "Seleccionar SDP") {
                alert('Por favor, seleccione un SDP.');
                isValid = false;
            }

            // Validar que se haya seleccionado un tipo de pallet
            const tipoPallet = document.getElementById('inputSelec8').value;
            if (tipoPallet === "Tipo Pallet") {
                alert('Por favor, seleccione un tipo de pallet.');
                isValid = false;
            }

            // Validar que el número de cajas sea mayor a 0
            const cajas = parseInt(document.getElementById('inputTextCajas').value);
            if (isNaN(cajas) || cajas <= 0) {
                alert('Por favor, ingrese un número de cajas válido mayor a 0.');
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault(); // Evitar el envío del formulario si no es válido
            }
        });
    });


    $modalCloseButtons = document.querySelectorAll('.modal-close');
    $modalCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Limpiar formulario del modal de ingreso exportación
            document.getElementById('form-ingreso-expo').reset();
            // Limpiar formulario del modal de ingreso comercial
            document.getElementById('form-ingreso-comercial').reset();
        });
    });

    const cajasPallet = document.getElementById('cajasPallet');
    const tipoPallet = document.querySelector('.cbTipoPallet');
    const cajas = document.getElementById('inputTextCajas');
    tipoPallet.addEventListener('change', function() {
        if (this.value === "COMPLETO") {
            cajas.value = cajasPallet.value;
            cajas.readOnly = true;
        } else {
            cajasPallet.readOnly = false;
            cajasPallet.value = '';
        }

    });


    document.querySelectorAll('.btnImprimirComercial').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();

            // 1. Mostrar Spinner y Overlay
            document.getElementById("spinner").style.display = "block";
            document.getElementById("overlay").style.display = "block";

            const folio = this.closest('tr').querySelector('td:first-child').textContent;

            const xhr = new XMLHttpRequest();
            const tipoProceso = document.getElementById('inputTipoProceso').value;
            xhr.open('GET', '/packing/backend/impresion-etiqueta-comercial?folio=' + encodeURIComponent(folio) + '&tipoProceso=' + encodeURIComponent(tipoProceso), true);

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

    let pesoBrutoComInput = document.getElementById('inputTextKgBrutoCom');
    let pesoNetoComInput = document.getElementById('inputTextKgNetoCom');
    pesoBrutoComInput.addEventListener('input', function() {
        let pesoBruto = parseFloat(pesoBrutoComInput.value) || 0;
        let tara = <?php echo BIN_TARA; ?>;
        let pesoNeto = pesoBruto - tara;
        pesoNetoComInput.value = pesoNeto > 0 ? pesoNeto : 0;
    });

    // document.getElementById('form-ingreso-primario').addEventListener('submit', function() {
    //     document.getElementById("spinner").style.display = "block";
    //     document.getElementById("overlay").style.display = "block";
    // })

    document.getElementById('form-ingreso-comercial').addEventListener('submit', function() {
        document.getElementById("spinner").style.display = "block";
        document.getElementById("overlay").style.display = "block";

        //validar los campos del formulario antes de enviarlo
        let isValid = true;
        // Validar que se haya seleccionado una categoría
        const categoria = document.getElementById('inputSelect6').value;
        if (categoria === "Seleccionar Categoria") {
            alert('Por favor, seleccione una categoría.');
            isValid = false;
        }

        // Validar que se haya seleccionado un calibre
        const calibre = document.getElementById('inputSelect4').value;
        if (calibre === "Seleccionar Calibre") {
            alert('Por favor, seleccione un calibre.');
            isValid = false;
        }
        // Validar que el peso bruto sea mayor a 0
        const pesoBruto = parseFloat(document.getElementById('inputTextKgBrutoCom').value);
        if (isNaN(pesoBruto) || pesoBruto <= 0) {
            alert('Por favor, ingrese un peso bruto válido mayor a 0.');
            isValid = false;
        }
        if (!isValid) {
            // Si no es válido, ocultamos el spinner y el overlay
            document.getElementById("spinner").style.display = "none";
            document.getElementById("overlay").style.display = "none";
        }
        
    })

    // document.querySelectorAll('.btnImprimirPrimario').forEach(button => {
    //     button.addEventListener('click', function(event) {
    //         event.preventDefault();

    //         // 1. Mostrar Spinner y Overlay
    //         document.getElementById("spinner").style.display = "block";
    //         document.getElementById("overlay").style.display = "block";

    //         const folio = this.closest('tr').querySelector('td:first-child').textContent;
    //         const xhr = new XMLHttpRequest();

    //         xhr.open('GET', '/packing/backend/impresion-etiqueta-primario?folio=' + encodeURIComponent(folio), true);

    //         xhr.onreadystatechange = function() {
    //             if (xhr.readyState === 4) {
    //                 // Solo ocultamos el spinner cuando la petición terminó (ready 4)
    //                 setTimeout(function() {
    //                     document.getElementById("spinner").style.display = "none";
    //                     document.getElementById("overlay").style.display = "none";
    //                 }, 2000);

    //                 if (xhr.status === 200) {
    //                     console.log(xhr.responseText);
    //                     console.log('Etiqueta impresa correctamente');
    //                 } else {
    //                     console.error('Error al imprimir la etiqueta');
    //                 }
    //             }
    //         };

    //         xhr.send();
    //     });
    // });


    const sdp = document.getElementById('inputSdp2');
    const variedadSelect = document.getElementById('inputSelect4');
    variedadSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const variedad = selectedOption.value;
        const especie = document.getElementById('inputEspecie').value;
        const productor = document.getElementById('inputProductor').value;
        // 1. Mostrar Spinner y Overlay
        document.getElementById("spinner").style.display = "block";
        document.getElementById("overlay").style.display = "block";

        $data = {
            especie: especie,
            variedad: variedad,
            empresa: document.getElementById('inputEmpresa').value,
            productor: productor
        };

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/packing/backend/obtener-sdp', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                // Solo ocultamos el spinner cuando la petición terminó (ready 4)
                setTimeout(function() {
                    document.getElementById("spinner").style.display = "none";
                    document.getElementById("overlay").style.display = "none";
                }, 2000);

       if (xhr.status === 200) {
    try {
        const response = JSON.parse(xhr.responseText);
        
        // DEBUG: Verificamos si realmente lo transformó en un Array
        console.log("Respuesta parseada:", response);
        console.log("¿Es un array?", Array.isArray(response)); 

        // Limpiar opciones anteriores
        sdp.innerHTML = '<option selected>Seleccionar SDP</option>';

        // Agregar nuevas opciones
        if (Array.isArray(response)) {
            response.forEach(item => {
                const option = document.createElement('option');
                option.value = item.sdp;
                option.textContent = item.sdp;
                sdp.appendChild(option);
            });

        } else {
            console.error('El JSON no es un arreglo válido:', response);
        }
        
    } catch (e) {
        console.error("Error al parsear el JSON de respuesta:", e);
    }
} else {
    console.error('Error en la petición HTTP:', xhr.status);
}
            }
        };

        xhr.send(JSON.stringify($data));
    });



    document.querySelectorAll('.btnImprimirFolioSecundario').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();

            // 1. Mostrar Spinner y Overlay
            document.getElementById("spinner").style.display = "block";
            document.getElementById("overlay").style.display = "block";

            const folio = this.closest('tr').querySelector('td:first-child').textContent;
            const tipopallet = this.closest('tr').querySelector('td:nth-child(2)').textContent;

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/packing/etiquetado/api/zpl/impresion_ventanas', true);

            xhr.setRequestHeader('Content-Type', 'application/json');
            let data = {
                folio: folio,
                empresa: document.getElementById('inputEmpresa').value,
                tipopallet: tipopallet
            };

            let jsonData = JSON.stringify(data);

            xhr.responseType = 'blob'; // Recibimos todo en bruto

            xhr.onload = function() {
                if (xhr.status === 200) {
                    // Consultamos qué tipo de archivo mandó el servidor
                    const contentType = xhr.getResponseHeader('Content-Type');

                    if (contentType === 'application/pdf') {
                        // RUTA PDF: Es un saldo/muestra, abrimos la ventana
                        const blob = new Blob([xhr.response], {
                            type: 'application/pdf'
                        });
                        const pdfUrl = window.URL.createObjectURL(blob);
                        window.open(pdfUrl, '_blank');
                    } else {
                        // RUTA ZEBRA: Es COMPLETO, ya se imprimió en la máquina
                        alert('✅ Etiquetas de Pallet Completo enviadas a la Zebra.');
                    }
                } else {
                    alert('❌ Error de comunicación con el servidor.');
                }
            };
            document.getElementById("spinner").style.display = "none";
            document.getElementById("overlay").style.display = "none";
            xhr.send(jsonData);
        });
    });
</script>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/view/layouts/footer.php'; ?>