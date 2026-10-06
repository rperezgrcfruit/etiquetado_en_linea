<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: /packing/login');
    exit();
}
include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/view/layouts/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';

// Variables de entorno (Simuladas, asumiendo que vienen de tu sesión o config)
$empresa_actual = $_SESSION['empresa'] ?? 'EXPORTADORA_DE_PRUEBA';
$temporada_actual = TEMPORADA_ACTUAL;

// Carga de Plantas para el selec
$plantas = [];
/* Descomentar cuando conectes a la BD real
try {
    $stmt = $pdo->query("SELECT csp, descripcion FROM planta ORDER BY descripcion");
    $plantas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }
*/
// Dato de prueba para que visualices el select
$plantas[] = ['csp' => '<?php echo PLANTA_CENTRAL_CSP; ?>', 'descripcion' => 'Planta Central'];
?>

<style>
    body { background-color: #f4f7f6; font-size: 0.9rem; }
    .sidebar-action { width: 100%; margin-bottom: 10px; height: 75px; display: flex; flex-direction: column; align-items: center; justify-content: center; font-weight: bold; font-size: 0.8rem; }
    .form-label-custom { font-weight: bold; color: #555; margin-bottom: 2px; font-size: 0.85rem;}
    .status-badge { padding: 5px 15px; font-weight: bold; border-radius: 4px; display: inline-block; min-width: 100px; text-align: center;}
    .nav-tabs .nav-link { color: #495057; font-weight: bold; text-transform: uppercase; font-size: 0.85rem; border-radius: 0; background: #f1f1f1;}
    .nav-tabs .nav-link.active { background: #fff; border-bottom-color: transparent; }
</style>

<div class="container-fluid mt-3">
    <div class="row g-3">
        <div class="col-md-10">
            <div class="card shadow-sm mb-2">
                <div class="card-body p-3">
                    
                    <input type="hidden" id="select_exportadora" value="<?php echo htmlspecialchars($empresa_actual); ?>">
                    <input type="hidden" id="input_temporada" value="<?php echo TEMPORADA_ACTUAL; ?>">
                    <input type="hidden" id="lbl_tipo" value="">
                    
                    <div class="row g-2 mb-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label-custom">N° Proceso</label>
                            <input type="text" id="input_nproceso" class="form-control form-control-sm border-primary" style="text-transform: uppercase;" placeholder="Enter p/ buscar">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-custom">Planta</label>
                            <select id="select_planta" class="form-select form-select-sm" disabled>
                                <option value="0">Seleccionar Planta</option>
                                <?php foreach($plantas as $planta): ?>
                                    <option value="<?php echo $planta['csp']; ?>">
                                        <?php echo $planta['csp'] . '   ' . $planta['descripcion']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-custom">Exportadora</label>
                            <input type="text" class="form-control form-control-sm bg-light" value="<?php echo htmlspecialchars($empresa_actual); ?>" readonly>
                        </div>
                        <div class="col-md-3 text-end">
                            <label class="form-label-custom">Fecha</label>
                            <input type="date" id="input_fecha" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>" disabled>
                        </div>
                    </div>

                    <div class="mb-3">
                        <span class="form-label-custom me-2">Estado:</span>
                        <span id="badge_estado" class="status-badge border bg-light text-muted border-secondary">INACTIVO</span>
                    </div>

                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab">Lotes de Entrada</button></li>
                        <li class="nav-item"><button class="nav-link disabled" data-bs-toggle="tab">Calibrado</button></li>
                        <li class="nav-item"><button class="nav-link disabled" data-bs-toggle="tab">Comercial</button></li>
                        <li class="nav-item"><button class="nav-link disabled" data-bs-toggle="tab">Resumen</button></li>
                    </ul>

                    <div class="p-3 border border-top-0 bg-white">
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-md-2">
                                <label class="form-label-custom">Lote</label>
                                <input type="text" id="input_lote" class="form-control form-control-sm border-info" disabled placeholder="Enter p/ buscar">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label-custom">N° Guía</label>
                                <input type="text" id="input_guia" class="form-control form-control-sm bg-light" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom text-primary">Productor</label>
                                <input type="text" id="input_productor" class="form-control form-control-sm bg-light" readonly>
                                <input type="hidden" id="hidden_cod_prod">
                            </div>
                            <div class="col-md-4 text-end">
                                <button id="btn_agregar_lote" class="btn btn-success btn-sm" disabled><i class="bi bi-plus-lg"></i> Agregar</button>
                            </div>
                        </div>

                        <div class="row g-2 p-2 mb-3 bg-light rounded border">
                            <div class="col-md-2">
                                <label class="form-label-custom">Especie</label>
                                <input type="text" id="input_especie" class="form-control form-control-sm bg-white" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label-custom">Variedad</label>
                                <input type="text" id="input_variedad" class="form-control form-control-sm bg-white" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label-custom">Bins Entrada</label>
                                <input type="number" id="input_bins_entrada" class="form-control form-control-sm" disabled>
                                <input type="hidden" id="hidden_promedio_bin">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label-custom">Kilos</label>
                                <input type="number" id="input_kilos_entrada" class="form-control form-control-sm bg-white" readonly>
                            </div>
                        </div>

                        <div style="height: 250px; border: 1px solid #dee2e6; background: #fff; overflow-y:auto;">
                            <table class="table table-sm table-bordered table-hover mb-0" id="tabla_lotes" style="font-size:0.85rem;">
                                <thead class="table-secondary sticky-top">
                                    <tr>
                                        <th>Lote</th><th>Bins</th><th>Kilos</th><th>Guía</th><th>Productor</th><th>Especie</th><th>Variedad</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-secondary bg-opacity-25 p-2 rounded fw-bold text-dark border" style="font-size: 0.85rem;">
                PRESIONE F4 PARA MOSTRAR PROCESOS
            </div>
        </div>

        <div class="col-md-2">
            <button id="btn_nuevo" class="btn btn-outline-primary sidebar-action border-secondary text-dark shadow-sm bg-white">
                <i class="bi bi-file-earmark-plus text-warning fs-3"></i> Nuevo
            </button>
            <button id="btn_crear_proceso" class="btn btn-outline-secondary sidebar-action shadow-sm bg-white" disabled>
                <i class="bi bi-floppy fs-3"></i> Crear Proceso
            </button>
            <button id="btn_cerrar_proceso" class="btn btn-outline-secondary sidebar-action shadow-sm bg-white" disabled>
                <i class="bi bi-lock fs-3"></i> Cerrar Proceso
            </button>
            <button class="btn btn-outline-danger sidebar-action text-dark shadow-sm bg-white mt-4" onclick="window.close()">
                <i class="bi bi-box-arrow-right text-danger fs-3"></i> Salir
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

const inputLote = document.getElementById('input_lote');
    const btnAgregarLote = document.getElementById('btn_agregar_lote');

    // --- EVENTO: Enter en Campo Lote ---
    inputLote.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            let loteValor = this.value.trim().toUpperCase();

            if (loteValor !== '') {
                document.body.style.cursor = 'wait';
                let formData = new FormData();
                formData.append('lote', loteValor);
                formData.append('exportadora', document.getElementById('select_exportadora').value);

                fetch('backend/buscar_lote.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    document.body.style.cursor = 'default';
                    if (res.status === 'OK') {
                        // Llenar datos visuales
                        document.getElementById('input_guia').value = res.data.guia;
                        document.getElementById('input_productor').value = res.data.full_prod;
                        document.getElementById('hidden_cod_prod').value = res.data.cod_prod;
                        document.getElementById('input_especie').value = res.data.especie;
                        document.getElementById('input_variedad').value = res.data.variedad;
                        
                        // Llenar inputs que el usuario modificará y la variable de promedio
                        document.getElementById('input_bins_entrada').value = res.data.cant_bins;
                        document.getElementById('input_kilos_entrada').value = res.data.kilos_recep;
                        document.getElementById('hidden_promedio_bin').value = res.data.promedio_bin;
                        
                        document.getElementById('input_bins_entrada').disabled = false;
                        btnAgregarLote.disabled = false;
                        document.getElementById('input_bins_entrada').focus();
                    } else {
                        alert(res.mensaje);
                        btnAgregarLote.disabled = true;
                    }
                }).catch(err => {
                    document.body.style.cursor = 'default';
                    console.error("Error BD:", err);
                });
            }
        }
    });

    // --- EVENTO: Click en Agregar Lote ---
    btnAgregarLote.addEventListener('click', function(e) {
        e.preventDefault();

        // Validaciones básicas antes de agregar
        let lote = inputLote.value.trim().toUpperCase();
        let bins = document.getElementById('input_bins_entrada').value;
        let kilos = document.getElementById('input_kilos_entrada').value;
        let tipo = document.getElementById('lbl_tipo').value;

        if (lote === "" || bins === "" || kilos === "") {
            alert("Faltan datos o cantidades para agregar este lote.");
            return;
        }

        if (tipo === "NUEVO") {
            // LÓGICA DE PROCESO NUEVO (Solo DOM, sin base de datos aún)
            agregarLoteAGrilla();
            sumarTotalesCabecera();
            document.getElementById('btn_crear_proceso').disabled = false; // Habilitar guardado final
            limpiarCamposLote();
            inputLote.focus();

        } else if (tipo === "EXISTENTE") {
            // LÓGICA DE PROCESO EXISTENTE (Guardado directo)
            if (confirm("¿Esta Seguro de Agregar este Lote al Proceso?")) {
                
                document.body.style.cursor = 'wait';
                let formData = new FormData();
                formData.append('n_proceso', document.getElementById('input_nproceso').value.toUpperCase());
                formData.append('lote', lote);
                formData.append('bins', bins);
                formData.append('kilos', kilos);
                formData.append('planta', document.getElementById('select_planta').value);
                formData.append('fecha', document.getElementById('input_fecha').value);
                formData.append('estado', document.getElementById('badge_estado').innerText);
                formData.append('exportadora', document.getElementById('select_exportadora').value);

                fetch('backend/agregar_lote_existente.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    document.body.style.cursor = 'default';
                    if (data.status === 'OK') {
                        alert(data.mensaje);
                        agregarLoteAGrilla(); // Visualizamos el lote sin recargar
                        sumarTotalesCabecera();
                        limpiarCamposLote();
                        inputLote.focus();
                        // El btn_crear_proceso sigue deshabilitado intencionalmente
                    } else {
                        alert("Error al insertar: " + data.mensaje);
                    }
                })
                .catch(err => {
                    document.body.style.cursor = 'default';
                    console.error("Error guardando lote directo:", err);
                });
            }
        }
    });

    // --- Funciones de Apoyo (Equivalentes a tu código VB) ---
    function agregarLoteAGrilla() {
        const tbody = document.querySelector('#tabla_lotes tbody');
        let tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${inputLote.value.toUpperCase()}</td>
            <td class="col-bins">${document.getElementById('input_bins_entrada').value}</td>
            <td class="col-kilos">${document.getElementById('input_kilos_entrada').value}</td>
            <td>${document.getElementById('input_guia').value}</td>
            <td>${document.getElementById('input_productor').value}</td>
            <td>${document.getElementById('input_especie').value}</td>
            <td>${document.getElementById('input_variedad').value}</td>
        `;
        tbody.appendChild(tr);
    }

    function sumarTotalesCabecera() {
        let sumBins = 0, sumKilos = 0;
        document.querySelectorAll('#tabla_lotes tbody tr').forEach(row => {
            sumBins += parseFloat(row.querySelector('.col-bins').innerText) || 0;
            sumKilos += parseFloat(row.querySelector('.col-kilos').innerText) || 0;
        });
        // Si tuvieras campos de "Total de Entrada" arriba del formulario, los actualizarías aquí.
    }

    function limpiarCamposLote() {
        inputLote.value = '';
        document.getElementById('input_guia').value = '';
        document.getElementById('input_productor').value = '';
        document.getElementById('hidden_cod_prod').value = '';
        document.getElementById('input_especie').value = '';
        document.getElementById('input_variedad').value = '';
        document.getElementById('input_bins_entrada').value = '';
        document.getElementById('input_kilos_entrada').value = '';
        document.getElementById('hidden_promedio_bin').value = '';
        
        document.getElementById('input_bins_entrada').disabled = true;
        btnAgregarLote.disabled = true;
    }
    
    const inputNProceso = document.getElementById('input_nproceso');
    
    inputNProceso.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            let n_proceso = this.value.trim().toUpperCase();

            if (n_proceso === '') {
                alert("Debe Ingresar un Numero de Proceso");
                return;
            }

            document.body.style.cursor = 'wait';
            
            // Asumo que tienes estos campos en tu HTML (como los definimos antes)
            let formData = new FormData();
            formData.append('n_proceso', n_proceso);
            formData.append('exportadora', document.getElementById('select_exportadora').value);
            formData.append('temporada', document.getElementById('input_temporada').value);

            fetch('/packing/api/busqueda-proceso', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                document.body.style.cursor = 'default';
                                    
                if (data.status === 'ERROR') {
                    // Validar_nproceso (Existe en otro lado)
                    alert(data.mensaje);
                    inputNProceso.value = '';
                    inputNProceso.focus();
                } 
                else if (data.status === 'NUEVO') {
                    // Validar_nproceso (Libre)
                    document.getElementById('lbl_tipo').value = 'NUEVO';
                    document.getElementById('badge_estado').innerText = 'ABIERTO';
                    
                    document.getElementById('select_planta').disabled = false;
                    document.getElementById('input_fecha').disabled = false;
                    
                    inputNProceso.disabled = true;
                    document.getElementById('input_lote').disabled = false;
                    document.getElementById('input_lote').focus();
                } 
                else if (data.status === 'EXISTENTE') {
                    // Busqueda_proceso (Encontrado)
                    alert("Registro Encontrado");
                    
                    document.getElementById('lbl_tipo').value = 'EXISTENTE';
                    document.getElementById('badge_estado').innerText = data.cabecera.Estado;
                    
                    // Llenar cabeceras con la fila 0
                    document.getElementById('select_planta').value = data.cabecera.planta;
                    document.getElementById('input_fecha').value = data.cabecera.Fecha_Proceso;
                    
                    // Textboxes informativos de la cabecera
                    document.getElementById('hidden_cod_prod').value = data.cabecera.cod_prod;
                    document.getElementById('input_productor').value = data.cabecera.Productor;
                    document.getElementById('input_especie').value = data.cabecera.Especie;
                    document.getElementById('input_variedad').value = data.cabecera.Variedad;
                    
                    // Totales calculados en el backend
                    document.getElementById('input_bins_entrada').value = data.totales.bins;
                    document.getElementById('input_kilos_entrada').value = data.totales.kilos;

                    // Llenar la grilla HTML simulando el dgv_datos
                    const tbody = document.querySelector('#tabla_lotes tbody');
                    tbody.innerHTML = ''; // Limpiar tabla
                    
                    data.detalle.forEach(fila => {
                        let tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${fila.Lote}</td>
                            <td>${fila.Bins}</td>
                            <td>${fila.Kilos}</td>
                            <td>${fila.Guia}</td>
                            <td>${fila.Productor}</td>
                            <td>${fila.Especie}</td>
                            <td>${fila.Variedad}</td>
                        `;
                        tbody.appendChild(tr);
                    });

                    // Lógica de botones según el código original
                    inputNProceso.disabled = true;
                    document.getElementById('btn_agregar_lote').disabled = false;
                    document.getElementById('btn_cerrar_proceso').disabled = false;
                    document.getElementById('btn_crear_proceso').disabled = true;
                    
                    if (data.cabecera.Estado === 'CERRADO') {
                        document.getElementById('input_lote').disabled = true;
                        document.getElementById('btn_agregar_lote').disabled = true;
                    } else {
                        document.getElementById('input_lote').disabled = false;
                    }
                }
                else if (data.status === 'EXCEPTION') {
                    alert("Error en BD: " + data.mensaje);
                }
            })
            .catch(error => {
                document.body.style.cursor = 'default';
                alert("Error de comunicación con el servidor.");
                console.error(error);
            });
        }
    });

    document.getElementById('btn_nuevo').addEventListener('click', function() {
        location.reload();
    });
});
</script>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/view/layouts/footer.php'; ?>