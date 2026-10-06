<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: /packing/index');
    exit();
}
include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/view/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="m-0 text-primary">Crear Nuevo Proceso de Reembalaje</h1>
                <a href="/packing/view/procesos/seleccionar-proceso-reembalaje" class="btn btn-outline-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                    Volver a Procesos
                </a>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Buscar Pallet de Exportación para Reembalar</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="searchFolio" class="form-label">Buscar por Folio</label>
                    <input type="text" class="form-control" id="searchFolio" placeholder="Ingrese número de folio...">
                </div>
                <div class="col-md-4">
                    <label for="searchEspecie" class="form-label">Filtrar por Especie</label>
                    <select class="form-select" id="searchEspecie">
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="searchVariedad" class="form-label">Filtrar por Variedad</label>
                    <select class="form-select" id="searchVariedad">
                        <option value="">Todas</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="tableFolios">
                    <thead class="table-success">
                        <tr>
                            <th>Folio</th>
                            <th>Especie</th>
                            <th>Variedad</th>
                            <th>Productor</th>
                            <th>Embalaje</th>
                            <th>Calibre</th>
                            <th>Categoría</th>
                            <th>Cajas</th>
                            <th>Kilos</th>
                            <th>Tipo Pallet</th>
                            <th>SDP</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="foliosBody">
                        <tr>
                            <td colspan="12" class="text-center text-muted">Cargando folios disponibles...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="confirmModalLabel">Confirmar Creación de Proceso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Desea crear un proceso de <strong>Reembalaje</strong> a partir del siguiente pallet?</p>
                <div class="alert alert-info">
                    <strong>Folio:</strong> <span id="modalFolio"></span><br>
                    <strong>Especie:</strong> <span id="modalEspecie"></span><br>
                    <strong>Variedad:</strong> <span id="modalVariedad"></span><br>
                    <strong>Productor:</strong> <span id="modalProductor"></span><br>
                    <strong>Cajas:</strong> <span id="modalCajas"></span><br>
                    <strong>Kilos:</strong> <span id="modalKilos"></span>
                </div>
                <p class="text-muted small">El pallet original quedará marcado como "REEMBALAJE" y no estará disponible para otros procesos.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btnConfirmCreate">Crear Proceso</button>
            </div>
        </div>
    </div>
</div>

<script>
let foliosData = [];
let selectedFolio = null;

document.addEventListener('DOMContentLoaded', function() {
    cargarFolios();
    setupFilters();
});

async function cargarFolios() {
    try {
        const response = await fetch('/packing/api/get_folios_para_reembalaje.php');
        const data = await response.json();

        if (data.error) {
            document.getElementById('foliosBody').innerHTML = 
                `<tr><td colspan="12" class="text-center text-danger">${data.error}</td></tr>`;
            return;
        }

        foliosData = data;
        renderTable(foliosData);
        populateFilters(foliosData);
    } catch (error) {
        console.error('Error cargando folios:', error);
        document.getElementById('foliosBody').innerHTML = 
            '<tr><td colspan="12" class="text-center text-danger">Error al cargar folios</td></tr>';
    }
}

function renderTable(folios) {
    const tbody = document.getElementById('foliosBody');
    
    if (!folios || folios.length === 0) {
        tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted">No hay folios disponibles para reembalaje</td></tr>';
        return;
    }

    tbody.innerHTML = folios.map(f => `
        <tr data-folio="${f.folio}">
            <td class="fw-bold">${f.folio}</td>
            <td>${f.especie || '-'}</td>
            <td>${f.variedad || '-'}</td>
            <td>${f.productor_nombre || f.productor || '-'}</td>
            <td>${f.embalaje || '-'}</td>
            <td>${f.calibre || '-'}</td>
            <td>${f.categoria || '-'}</td>
            <td>${f.cajas || 0}</td>
            <td>${Number(f.kilos || 0).toLocaleString('es-CL')}</td>
            <td>${f.tipo_pallet || '-'}</td>
            <td>${f.sdp || '-'}</td>
            <td>
                <button class="btn btn-sm btn-warning btn-crear-reembalaje" 
                        data-folio="${f.folio}"
                        data-especie="${f.especie || ''}"
                        data-variedad="${f.variedad || ''}"
                        data-productor="${f.productor_nombre || f.productor || ''}"
                        data-cajas="${f.cajas || 0}"
                        data-kilos="${f.kilos || 0}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-box-arrow-in-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M6 3.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 0-1 0v2A1.5 1.5 0 0 0 6.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-8A1.5 1.5 0 0 0 5 3.5v2a.5.5 0 0 0 1 0v-2z"/>
                        <path fill-rule="evenodd" d="M11.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H1.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                    </svg>
                    Reembalar
                </button>
            </td>
        </tr>
    `).join('');

    // Event listeners para botones
    document.querySelectorAll('.btn-crear-reembalaje').forEach(btn => {
        btn.addEventListener('click', function() {
            selectedFolio = {
                folio: this.dataset.folio,
                especie: this.dataset.especie,
                variedad: this.dataset.variedad,
                productor: this.dataset.productor,
                cajas: this.dataset.cajas,
                kilos: this.dataset.kilos
            };
            showConfirmModal(selectedFolio);
        });
    });
}

function populateFilters(folios) {
    const especies = [...new Set(folios.map(f => f.especie).filter(Boolean))].sort();
    const variedades = [...new Set(folios.map(f => f.variedad).filter(Boolean))].sort();

    const selEspecie = document.getElementById('searchEspecie');
    const selVariedad = document.getElementById('searchVariedad');

    especies.forEach(e => {
        const opt = document.createElement('option');
        opt.value = e;
        opt.textContent = e;
        selEspecie.appendChild(opt);
    });

    variedades.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v;
        opt.textContent = v;
        selVariedad.appendChild(opt);
    });
}

function setupFilters() {
    const searchFolio = document.getElementById('searchFolio');
    const searchEspecie = document.getElementById('searchEspecie');
    const searchVariedad = document.getElementById('searchVariedad');

    function applyFilters() {
        const folioFilter = searchFolio.value.toUpperCase();
        const especieFilter = searchEspecie.value;
        const variedadFilter = searchVariedad.value;

        const filtered = foliosData.filter(f => {
            const matchFolio = !folioFilter || f.folio.toUpperCase().includes(folioFilter);
            const matchEspecie = !especieFilter || f.especie === especieFilter;
            const matchVariedad = !variedadFilter || f.variedad === variedadFilter;
            return matchFolio && matchEspecie && matchVariedad;
        });

        renderTable(filtered);
    }

    searchFolio.addEventListener('input', applyFilters);
    searchEspecie.addEventListener('change', applyFilters);
    searchVariedad.addEventListener('change', applyFilters);
}

function showConfirmModal(folio) {
    document.getElementById('modalFolio').textContent = folio.folio;
    document.getElementById('modalEspecie').textContent = folio.especie || '-';
    document.getElementById('modalVariedad').textContent = folio.variedad || '-';
    document.getElementById('modalProductor').textContent = folio.productor || '-';
    document.getElementById('modalCajas').textContent = folio.cajas;
    document.getElementById('modalKilos').textContent = Number(folio.kilos).toLocaleString('es-CL');

    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
}

document.getElementById('btnConfirmCreate').addEventListener('click', async function() {
    if (!selectedFolio) return;

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creando...';

    try {
        const response = await fetch('/packing/api/crear_proceso_reembalaje.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'folio=' + encodeURIComponent(selectedFolio.folio)
        });

        const result = await response.json();

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
            
            // Usar SweetAlert si está disponible, sino alert nativo
            if (typeof Swal !== 'undefined') {
                await Swal.fire({
                    icon: 'success',
                    title: 'Proceso Creado',
                    text: `Proceso de Reembalaje #${result.n_proceso} creado desde folio ${result.folio_origen}`,
                    confirmButtonColor: '#28a745'
                });
            } else {
                alert(`Proceso de Reembalaje #${result.n_proceso} creado correctamente`);
            }

            // Redirigir al ingreso de folios del nuevo proceso
            window.location.href = `/packing/view/procesos/ingreso-folio-reembalaje?proceso=${result.n_proceso}`;
        } else {
            throw new Error(result.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                confirmButtonColor: '#dc3545'
            });
        } else {
            alert('Error: ' + error.message);
        }
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Crear Proceso';
    }
});
</script>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/view/layouts/footer.php'; ?>