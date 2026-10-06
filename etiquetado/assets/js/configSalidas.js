/**
 * configSalidas.js — UI moderna para configuración de salidas
 * Compatible con index.php refactorizado
 */

(function() {
    'use strict';

    // ===== ESTADO GLOBAL =====
    const state = {
        salidas: [],
        salidasFiltradas: [],
        empresaSeleccionada: '',
        procesoSeleccionado: '',
        tipoProcesoSeleccionado: '',
        especieActual: '',
        impresorasDisponibles: [],
        embalajesDisponibles: [],
        calibresDisponibles: [],
        categoriasDisponibles: []
    };

    // ===== ELEMENTOS DOM =====
    const els = {};

    function cacheElements() {
        const ids = [
            'comboEmpresa', 'comboProceso', 'btn-bloquear', 'btn-desbloquear',
            'contenedorSalidas', 'overlay', 'btnResetFiltros',
            'stat-activo', 'stat-pendiente', 'stat-error', 'stat-total',
            'modalSalidas', 'modalSalidasLabel', 'idSalida', 'inputTipoProceso',
            'inputLote', 'embalaje', 'calibre', 'categoria', 'tipoProcesoDisplay',
            'impresoraZebra', 'validationIndicator', 'validationMsg', 'btGuardarSalidas'
        ];

        for (const id of ids) {
            const el = document.getElementById(id);
            if (!el) {
                console.warn(`[cacheElements] Elemento no encontrado: #${id}`);
            }
            els[id.replace(/-([a-z])/g, (_, l) => l.toUpperCase())] = el;
        }

        // Alias para compatibilidad
        els.comboEmpresa = els.comboEmpresa;
        els.comboProceso = els.comboProceso;
        els.btnBloquear = els.btnBloquear;
        els.btnDesbloquear = els.btnDesbloquear;
        els.contenedorSalidas = els.contenedorSalidas;
        els.overlay = els.overlay;
        els.btnResetFiltros = els.btnResetFiltros;
        els.statActivo = els.statActivo;
        els.statPendiente = els.statPendiente;
        els.statError = els.statError;
        els.statTotal = els.statTotal;
        els.modal = els.modalSalidas;
        els.modalTitle = els.modalSalidasLabel;
        els.idSalida = els.idSalida;
        els.inputTipoProceso = els.inputTipoProceso;
        els.inputLote = els.inputLote;
        els.embalaje = els.embalaje;
        els.calibre = els.calibre;
        els.categoria = els.categoria;
        els.tipoProcesoDisplay = els.tipoProcesoDisplay;
        els.impresoraZebra = els.impresoraZebra;
        els.validationIndicator = els.validationIndicator;
        els.validationMsg = els.validationMsg;
        els.btGuardarSalidas = els.btGuardarSalidas;
    }

    // ===== UTILIDADES =====
    function showOverlay(show) {
        els.overlay.style.display = show ? 'flex' : 'none';
    }

    function toast(msg, type = 'info') {
        const bg = type === 'error' ? '#dc2626' : type === 'success' ? '#16a34a' : '#2563eb';
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed; bottom: 20px; right: 20px; z-index: 9999;
            background: ${bg}; color: white; padding: 12px 20px;
            border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-weight: 500; animation: slideIn 0.3s ease;
        `;
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }

    // ===== INICIALIZACIÓN =====
    document.addEventListener('DOMContentLoaded', async function() {
        cacheElements();
        bindEvents();
        await cargarImpresoras();
    });

    function bindEvents() {
        // Empresa
        els.comboEmpresa.addEventListener('change', onEmpresaChange);

        // Proceso
        els.comboProceso.addEventListener('change', onProcesoChange);

        // Bloquear / Desbloquear
        els.btnBloquear.addEventListener('click', onBloquearClick);
        els.btnDesbloquear.addEventListener('click', onDesbloquearClick);

        // Reset
        els.btnResetFiltros.addEventListener('click', () => renderSalidas(state.salidas));

        // Modal - Guardar
        els.btGuardarSalidas.addEventListener('click', onGuardarSalida);

        // Modal - abrir
        els.modal.addEventListener('show.bs.modal', onModalShow);
    }

    // ===== CARGA INICIAL DE IMPRESORAS =====
    async function cargarImpresoras() {
        try {
            const resp = await fetch('api/obtener_impresoras.php');
            const data = await resp.json();
            if (data.error) throw new Error(data.error);
            state.impresorasDisponibles = data;
        } catch (e) {
            console.warn('No se pudieron cargar impresoras:', e);
        }
    }

    // ===== EVENTOS PRINCIPALES =====
    async function onEmpresaChange() {
        state.empresaSeleccionada = els.comboEmpresa.value;
        state.procesoSeleccionado = '';
        state.tipoProcesoSeleccionado = '';

        els.comboProceso.innerHTML = '<option value="">Seleccionar Proceso...</option>';
        els.comboProceso.disabled = true;
        els.btnBloquear.disabled = true;

        if (state.empresaSeleccionada) {
            showOverlay(true);
            try {
                await fetchProcesos(state.empresaSeleccionada);
                els.comboProceso.disabled = false;
            } catch (e) {
                toast('Error al cargar procesos: ' + e.message, 'error');
            } finally {
                showOverlay(false);
            }
        }
    }

    async function onProcesoChange() {
        const select = els.comboProceso;
        if (!select) {
            console.error('[onProcesoChange] comboProceso no encontrado');
            return;
        }
        state.procesoSeleccionado = select.value;
        state.tipoProcesoSeleccionado = select.options[select.selectedIndex]?.dataset?.tipoProceso || '';

        if (state.procesoSeleccionado) {
            showOverlay(true);
            try {
                const data = await obtenerDetalleLote(
                    state.procesoSeleccionado,
                    state.empresaSeleccionada,
                    state.tipoProcesoSeleccionado
                );
                if (data?.especie) {
                    state.especieActual = data.especie;
                    if (els.inputLote) els.inputLote.value = data.especie;
                    if (els.inputTipoProceso) els.inputTipoProceso.value = state.tipoProcesoSeleccionado;

                    // Cargar embalajes/calibres/categorías para la especie
                    await cargarConfigEspecie(state.especieActual, state.empresaSeleccionada);
                } else {
                    toast('No se encontró especie para este proceso', 'warning');
                }
                if (els.btnBloquear) els.btnBloquear.disabled = false;
            } catch (e) {
                console.error('[onProcesoChange] Error:', e);
                toast('Error al obtener lote: ' + e.message, 'error');
                if (els.btnBloquear) els.btnBloquear.disabled = true;
            } finally {
                showOverlay(false);
            }
        } else {
            els.btnBloquear.disabled = true;
        }
    }

    async function onBloquearClick() {
        if (!state.empresaSeleccionada || !state.procesoSeleccionado) {
            toast('Selecciona empresa y proceso primero', 'warning');
            return;
        }

        // Deshabilitar combos
        if (els.comboEmpresa) els.comboEmpresa.disabled = true;
        if (els.comboProceso) els.comboProceso.disabled = true;
        if (els.btnBloquear) els.btnBloquear.hidden = true;
        if (els.btnDesbloquear) els.btnDesbloquear.hidden = false;

        showOverlay(true);
        try {
            const data = await buscarSalidas();
            state.salidas = data;
            renderSalidas(data);
            actualizarStats(data);
        } catch (e) {
            toast('Error al cargar salidas: ' + e.message, 'error');
        } finally {
            showOverlay(false);
        }
    }

    function onDesbloquearClick() {
        if (els.comboEmpresa) els.comboEmpresa.disabled = false;
        if (els.comboProceso) els.comboProceso.disabled = false;
        if (els.btnBloquear) els.btnBloquear.hidden = false;
        if (els.btnDesbloquear) els.btnDesbloquear.hidden = true;
        if (els.contenedorSalidas) {
            els.contenedorSalidas.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-box-open fa-2x mb-2"></i>
                    <p>Selecciona una empresa y proceso, luego haz clic en <strong>Bloquear</strong> para cargar las salidas.</p>
                </div>
            `;
        }
        limpiarFiltros();
    }

    // ===== API CALLS =====
    async function fetchProcesos(empresa) {
        const resp = await fetch(`api/obtener_num_proceso?empresa=${encodeURIComponent(empresa)}`);
        const data = await resp.json();
        if (data.error) throw new Error(data.error);

        els.comboProceso.innerHTML = '<option value="">Seleccionar Proceso...</option>';
        data.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.n_proceso;
            opt.textContent = p.dataProceso;
            opt.dataset.tipoProceso = p.tipo_proceso;
            els.comboProceso.appendChild(opt);
        });
    }

    async function obtenerDetalleLote(nProceso, empresa, tipoProceso) {
        const resp = await fetch(
            `api/obtener_lotes_procesos?n_proceso=${encodeURIComponent(nProceso)}` +
            `&empresa=${encodeURIComponent(empresa)}` +
            `&tipo_proceso=${encodeURIComponent(tipoProceso)}`
        );
        const data = await resp.json();
        if (data.error) throw new Error(data.error);
        return data;
    }

    async function cargarConfigEspecie(especie, empresa) {
        const resp = await fetch(
            `api/obtener_embalaje_calibre?especie=${encodeURIComponent(especie)}` +
            `&empresa=${encodeURIComponent(empresa)}`
        );
        const data = await resp.json();
        if (data.error) throw new Error(data.error);

        state.embalajesDisponibles = data.embalaje || [];
        state.calibresDisponibles = data.calibre || [];
        state.categoriasDisponibles = data.categoria || [];

        // Ya no hay filtro de calibre en la UI
    }

    async function buscarSalidas() {
        const resp = await fetch('api/buscar_salidas');
        const data = await resp.json();
        if (data.error) throw new Error(data.error);
        return data;
    }

    async function onGuardarSalida() {
        if (!els.idSalida || !els.embalaje || !els.calibre) {
            console.error('[onGuardarSalida] Elementos faltantes');
            toast('Error interno: faltan elementos del formulario', 'error');
            return;
        }
        const idSalida = els.idSalida.value;
        const embalaje = els.embalaje.value;
        const calibre = els.calibre.value;
        const categoria = els.categoria?.value || '';
        const impresora = els.impresoraZebra?.value || '';
        const tipoProceso = state.tipoProcesoSeleccionado || 'NORMAL';

        if (!embalaje || !calibre) {
            toast('Embalaje y Calibre son obligatorios', 'warning');
            return;
        }

        showOverlay(true);
        try {
            await guardarSalida(idSalida, embalaje, calibre, state.empresaSeleccionada,
                               state.procesoSeleccionado, categoria, tipoProceso, impresora);

            toast('Salida configurada correctamente', 'success');
            if (els.modal) bootstrap.Modal.getInstance(els.modal).hide();

            // Refrescar grid
            const data = await buscarSalidas();
            state.salidas = data;
            renderSalidas(data);
            actualizarStats(data);
        } catch (e) {
            toast('Error al guardar: ' + e.message, 'error');
        } finally {
            showOverlay(false);
        }
    }

    async function guardarSalida(idSalida, embalaje, calibre, empresa, nProceso, categoria, tipoProceso, impresora) {
        const params = new URLSearchParams({
            id_salida: idSalida,
            embalaje,
            calibre,
            empresa,
            n_proceso: nProceso,
            categoria,
            tipo_proceso: tipoProceso,
            impresora_zebra: impresora
        });

        const resp = await fetch(`api/configurar_salida?${params.toString()}`);
        const data = await resp.json();
        if (data.error) throw new Error(data.error);
        return data;
    }

    // ===== MODAL =====
    async function onModalShow(event) {
        const button = event.relatedTarget;
        if (!button) {
            console.error('[onModalShow] No relatedTarget');
            return;
        }
        const descripcion = button.getAttribute('data-descripcion');
        const idSalida = button.getAttribute('data-id-salida');

        if (els.idSalida) els.idSalida.value = idSalida;
        if (els.modalTitle) els.modalTitle.textContent = `Configurar ${descripcion}`;
        if (els.tipoProcesoDisplay) els.tipoProcesoDisplay.value = state.tipoProcesoSeleccionado || 'NORMAL';

        // Poblar selects del modal
        if (els.embalaje) poblarSelect(els.embalaje, state.embalajesDisponibles, 'Seleccionar Embalaje...');
        if (els.calibre) poblarSelect(els.calibre, state.calibresDisponibles, 'Seleccionar Calibre...');
        if (els.categoria) poblarSelect(els.categoria, state.categoriasDisponibles, 'Seleccionar Categoría...');
        if (els.impresoraZebra) poblarSelect(els.impresoraZebra, state.impresorasDisponibles.map(i => i.nombre), 'Seleccionar impresora...');

        // Cargar valores actuales de la salida
        const salidaActual = state.salidas.find(s => s.codigo === idSalida);
        if (salidaActual) {
            if (els.embalaje) els.embalaje.value = salidaActual.embalaje || '';
            if (els.calibre) els.calibre.value = salidaActual.calibre || '';
            if (els.categoria) els.categoria.value = salidaActual.categoria || '';
            if (els.impresoraZebra) els.impresoraZebra.value = salidaActual.impresora_zebra || '';
        }

        // Validación en tiempo real
        [els.embalaje, els.calibre].filter(Boolean).forEach(el => {
            el.addEventListener('change', validarFormulario);
        });
        validarFormulario();
    }

    function validarFormulario() {
        const ok = els.embalaje?.value && els.calibre?.value;
        const msg = ok
            ? { icon: 'fa-check-circle', text: 'Listo para guardar', type: 'success' }
            : { icon: 'fa-exclamation-triangle', text: 'Complete Embalaje y Calibre', type: 'warning' };

        if (els.validationMsg) {
            els.validationMsg.innerHTML = `<i class="fas ${msg.icon} me-1"></i>${msg.text}`;
        }
        if (els.validationIndicator) {
            els.validationIndicator.style.display = 'block';
        }
        if (els.validationMsg) {
            els.validationMsg.className = `alert alert-sm mb-0 py-2 alert-${msg.type === 'success' ? 'success' : 'warning'}`;
        }
        if (els.btGuardarSalidas) {
            els.btGuardarSalidas.disabled = !ok;
        }
    }

    // ===== RENDER GRID =====
    function renderSalidas(data) {
        state.salidasFiltradas = data;

        if (data.length === 0) {
            els.contenedorSalidas.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-box-open fa-2x mb-2"></i>
                    <p>No hay salidas configuradas.</p>
                </div>
            `;
            return;
        }

        els.contenedorSalidas.innerHTML = data.map(salida => renderCard(salida)).join('');
    }

    function renderCard(s) {
        const estado = s.estado || (s.calibre && s.embalaje && s.impresora_zebra ? 'activo' : 'sin_configurar');
        const estadoClass = estado === 'activo' ? 'estado-ok' :
                           estado === 'sin_configurar' ? 'estado-pendiente' : 'estado-error';
        const estadoTexto = estado === 'activo' ? 'Configurada' : estado === 'sin_configurar' ? 'Sin configurar' : 'Error';
        const badgeIcon = estado === 'activo' ? 'fa-check' : estado === 'sin_configurar' ? 'fa-clock' : 'fa-exclamation-triangle';

        return `
            <div class="cs-card-salida ${estadoClass}" data-salida="${s.codigo}" data-calibre="${s.calibre || ''}">
                <span class="cs-badge-estado" title="${estadoTexto}"></span>

                <div class="cs-codigo">${escapeHtml(s.codigo)}</div>
                <div class="cs-descripcion">${escapeHtml(s.descripcion)}</div>

                <div class="cs-datos">
                    <div class="cs-dato"><span class="etiqueta">Calibre:</span> <span class="valor">${escapeHtml(s.calibre || '—')}</span></div>
                    <div class="cs-dato"><span class="etiqueta">Embalaje:</span> <span class="valor">${escapeHtml(s.embalaje || '—')}</span></div>
                    <div class="cs-dato"><span class="etiqueta">Cat:</span> <span class="valor">${escapeHtml(s.categoria || '—')}</span></div>
                </div>

                <button class="cs-btn-config"
                        data-bs-toggle="modal"
                        data-bs-target="#modalSalidas"
                        data-descripcion="${escapeHtml(s.descripcion)}"
                        data-id-salida="${escapeHtml(s.codigo)}">
                    <i class="fas fa-cog me-1"></i>Configurar
                </button>
            </div>
        `;
    }

    function actualizarStats(data) {
        const activo = data.filter(s => s.estado === 'activo' || (s.calibre && s.embalaje && s.impresora_zebra)).length;
        const pendiente = data.filter(s => s.estado === 'sin_configurar' || !(s.calibre && s.embalaje)).length;
        const error = data.filter(s => s.estado === 'error').length;

        els.statActivo.textContent = activo;
        els.statPendiente.textContent = pendiente;
        els.statError.textContent = error;
        els.statTotal.textContent = data.length;
    }

    // ===== HELPERS =====
    function poblarSelect(select, items, placeholder) {
        const current = select.value;
        select.innerHTML = `<option value="">${placeholder}</option>`;
        items.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item;
            opt.textContent = item;
            select.appendChild(opt);
        });
        if (current && items.includes(current)) select.value = current;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function debounce(fn, wait) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), wait); };
    }

    // Exponer funciones globales usadas en onclick inline
    window.abrirModalConfigurar = function(codigo) {
        // El modal se abre via data-bs-target, pero podemos precargar si queremos
    };

    window.probarImpresion = async function(codigo) {
        try {
            const resp = await fetch(`api/zpl/impresion_etiqueta_linea.php?salida=${encodeURIComponent(codigo)}&cantidad=1`);
            const text = await resp.text();
            if (text.includes('OK')) {
                toast('Etiqueta de prueba enviada', 'success');
            } else {
                toast('Error en prueba: ' + text, 'error');
            }
        } catch (e) {
            toast('Error: ' + e.message, 'error');
        }
    };

    window.copiarZPL = async function() {
        // Implementar si se genera preview ZPL
    };

    window.generarZPLPreview = async function() {
        // Implementar preview ZPL
    };

})();