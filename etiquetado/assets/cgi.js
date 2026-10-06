
  async function fetchProcesos(empresa) {
    try {
      const response = await fetch(
        `api/obtener_num_proceso?empresa=${encodeURIComponent(empresa)}`,
      );

      const data = await response.json();

      const comboProceso = document.getElementById("comboProceso");
      comboProceso.innerHTML =
        '<option value="">Seleccionar Proceso...</option>';
      data.forEach((proceso) => {
        const option = document.createElement("option");
        option.value = proceso.n_proceso;
        option.textContent = proceso.dataProceso;
        option.dataset.tipoProceso = proceso.tipo_proceso; // Agregar el tipo de proceso como un atributo de datos
        comboProceso.appendChild(option);
      });
    } catch (error) {
      console.error("Error al obtener procesos:", error);
    }
  }

  async function buscarSalidas() {
    try {
      const response = await fetch("api/buscar_salidas");
      if (!response.ok) {
        throw new Error("Error en la respuesta del servidor");
      }
      const data = await response.json();
      return data;
    } catch (error) {
      alert("Error al buscar salidas: " + error.message);
    }
  }

  async function guardarSalida(idSalida, embalaje, calibre, empresa, nProceso, categoria, tipoProceso) {
    const response = await fetch(
      `api/configurar_salida?id_salida=${idSalida}&embalaje=${embalaje}&calibre=${calibre}&empresa=${encodeURIComponent(empresa)}
      &n_proceso=${encodeURIComponent(nProceso)}&categoria=${encodeURIComponent(categoria)}&tipo_proceso=${encodeURIComponent(tipoProceso)}`,
    );

    console.log(response);
    const data = await response.json();
    return data;

  }

  async function obtenerDetalleLote(nProceso, empresa, tipoProceso) {
    try {
      const response = await fetch(
        `api/obtener_lotes_procesos?n_proceso=${nProceso}&empresa=${encodeURIComponent(empresa)}&tipo_proceso=${encodeURIComponent(tipoProceso)}`,
      );
      if (!response.ok) {
        throw new Error("Error en la respuesta del servidor");
      }
      const data = await response.json();
      return data;
    } catch (error) {
      alert("Error al obtener detalle del lote: " + error.message);
    }
  }

  async function obtenerEmbalajeCalibre(especie, empresa) {
    try {
      const response = await fetch(
        `api/obtener_embalaje_calibre?especie=${encodeURIComponent(especie)}&empresa=${encodeURIComponent(empresa)}`
      );
      if (!response.ok) {
        throw new Error("Error en la respuesta del servidor");
      }
      const data = await response.json();
      return data;
    } catch (error) {
      alert("Error al obtener embalaje y calibre: " + error.message);
    }
  }

     function configurarSalida() {
      
    }

