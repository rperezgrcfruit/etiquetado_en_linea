document.addEventListener("DOMContentLoaded", function () {
  document.querySelector("#btn-desbloquear").hidden = true;
  // Generar dinámicamente las 28 tarjetas
  const contenedor = document.getElementById("contenedorSalidas");

  // for (let i = 1; i <= 28; i++) {
  //   const card = document.createElement("div");
  //   card.className = "card";
  //   card.innerHTML = `
  //               <h3>SALIDA ${i}</h3>
  //               <h4>Calibre: 0</h4>
  //               <h4>Embalaje: 0</h4>
  //               <button class="btn-configurar" onclick="configurar(${i})" disabled="true" style="background-color: #8a8a8a; color: white; border: none; padding: 10px 20px; cursor: pointer;">
  //                   CONFIGURAR
  //               </button>
  //           `;
  //   contenedor.appendChild(card);
  // }

  const spinner = document.querySelector(".spinner-border");

  document
    .getElementById("comboEmpresa")
    .addEventListener("change", function () {
      const empresaSeleccionada = this.value;
      if (empresaSeleccionada) {
        spinner.style.display = "inline-block"; // Mostrar el spinner
        fetchProcesos(empresaSeleccionada);
        spinner.style.display = "none"; // Ocultar el spinner después de cargar los procesos
      } else {
        document.getElementById("comboProceso").innerHTML =
          '<option value="">Seleccionar Proceso...</option>';
      }
    });

  document
    .getElementById("comboProceso")
    .addEventListener("change", function () {
      const procesoSeleccionado = this.value;
      const tipoProcesoSeleccionado = this.options[this.selectedIndex].dataset.tipoProceso; // Obtener el tipo de proceso del atributo de datos
      if (procesoSeleccionado) {
        let empresaSeleccionada = document.getElementById("comboEmpresa").value;
        console.log("Proceso seleccionado:", procesoSeleccionado);
        console.log("Tipo de proceso seleccionado:", tipoProcesoSeleccionado);
        //return;
        obtenerDetalleLote(procesoSeleccionado, empresaSeleccionada, tipoProcesoSeleccionado)
          .then((data) => {
            console.log(data);
            if (data) {
              const especie = data.especie;
              document.getElementById("inputLote").value = especie;
              document.getElementById("inputTipoProceso").value = tipoProcesoSeleccionado;
            } else {
              document.getElementById("inputLote").value =
                "No se encontró especie";
            }
          })
          .catch((error) => {
            console.error("Error al obtener detalle del lote:", error);
            document.getElementById("inputLote").value =
              "Error al obtener el lote";
          });
        document.getElementById("inputLote").value = "";
        document.querySelector("#btn-bloquear").disabled = false;
      } else {
        document.querySelector("#btn-bloquear").disabled = true;
      }
    });

  document
    .querySelector("#btn-bloquear")
    .addEventListener("click", function pintarSalidas() {
      const comboEmpresa = document.getElementById("comboEmpresa");
      const comboProceso = document.getElementById("comboProceso");
      const empresaSeleccionada = comboEmpresa.value;
      const procesoSeleccionado = comboProceso.value;
      if (empresaSeleccionada && procesoSeleccionado) {
        comboEmpresa.disabled = true;
        comboProceso.disabled = true;
        document.querySelector(".grid-salidas").innerHTML = "";
        document.getElementById("overlay").style.display = "block";
        spinner.style.display = "inline-block";
        buscarSalidas()
          .then((data) => {
            document.querySelector(".btn-desbloquear").hidden = false;
            document.querySelector(".btn-bloquear").hidden = true;
            for (let id in data) {
              //console.log(data[id]);
              const card = document.createElement("div");
              card.className = "cardSalidas";
              card.style = `
            
                  background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
          
          
          `;
              card.innerHTML = "";
              card.innerHTML = `
                <h3>${data[id].descripcion}</h3>
                <h5>Calibre: ${data[id].calibre}</h5>
                <h5>Embalaje: ${data[id].embalaje}</h5>
                <h5>Categoria: ${data[id].categoria}</h5>
                <input type="text" id="idSalida" name="idSalida" value="${data[id].codigo}" hidden>
                <button id="btn-configurar" class="btn-configurar" data-bs-toggle="modal" data-bs-target="#modalSalidas"
                data-descripcion="${data[id].descripcion}" data-id-salida="${data[id].codigo}"
                 style="background-color: #007bff; color: white; border: none; padding: 10px 20px; cursor: pointer;">
                   CONFIGURAR
                </button>
            `;
              contenedor.appendChild(card);
            }
            document.getElementById("overlay").style.display = "none";
            spinner.style.display = "none";
          })
          .catch((error) => {
            console.error("Error al buscar salidas:", error);
          });
      } else {
        alert(
          "Por favor, seleccione una empresa y un proceso antes de bloquear.",
        );
      }
    });

  const modalSalidas = document.getElementById("modalSalidas");
  modalSalidas.addEventListener("show.bs.modal", function (event) {
    const button = event.relatedTarget;
    modalTitle = modalSalidas.querySelector(".modal-title");
    const descripcion = button.getAttribute("data-descripcion");
    const idSalida = button.getAttribute("data-id-salida");
    document.getElementById("idSalida").value = idSalida;
    let empresaSeleccionada = document.getElementById("comboEmpresa").value;
    let especie = document.getElementById("inputLote").value;
    obtenerEmbalajeCalibre(especie, empresaSeleccionada)
      .then((data) => {
        let jsonData = JSON.stringify(data);
        console.log(data);
        const embalajeSelect = document.getElementById("embalaje");
        const calibreSelect = document.getElementById("calibre");
        const categoriaSelect = document.getElementById("categoria");
        // Limpiar opciones anteriores
        embalajeSelect.innerHTML =
          '<option value="">Seleccionar Embalaje...</option>';
        calibreSelect.innerHTML =
          '<option value="">Seleccionar Calibre...</option>';
        // Agregar nuevas opciones
        data.embalaje.forEach((item) => {
          console.log(item);
          const optionEmbalaje = document.createElement("option");
          optionEmbalaje.value = item;
          optionEmbalaje.textContent = item;
          embalajeSelect.appendChild(optionEmbalaje);
        });

        data.calibre.forEach((item) => {
          console.log(item);
          const optionCalibre = document.createElement("option");
          optionCalibre.value = item;
          optionCalibre.textContent = item;
          calibreSelect.appendChild(optionCalibre);
        });
        
        data.categoria.forEach((item) => {
          console.log(item);
          const optionCategoria = document.createElement("option");
          optionCategoria.value = item;
          optionCategoria.textContent = item;
          categoriaSelect.appendChild(optionCategoria);
        });

      })
      .catch((error) => {
        console.error("Error al obtener embalaje y calibre:", error);
      });
    modalTitle.textContent = `Configurar ${descripcion}`;
  });

  function pintarSalidas() {
    const comboEmpresa = document.getElementById("comboEmpresa");
    const comboProceso = document.getElementById("comboProceso");
    const empresaSeleccionada = comboEmpresa.value;
    const procesoSeleccionado = comboProceso.value;
    if (empresaSeleccionada && procesoSeleccionado) {
      comboEmpresa.disabled = true;
      comboProceso.disabled = true;
      document.querySelector(".grid-salidas").innerHTML = "";
      buscarSalidas()
        .then((data) => {
          document.querySelector(".btn-desbloquear").hidden = false;
          document.querySelector(".btn-bloquear").hidden = true;
          for (let id in data) {
            //console.log(data[id]);
            const card = document.createElement("div");
            card.className = "cardSalidas";
            card.style = `
            
                  background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
          
          
          `;
            card.innerHTML = "";
            card.innerHTML = `
                <h3>${data[id].descripcion}</h3>
                <h5>Calibre: ${data[id].calibre}</h5>
                <h5>Embalaje: ${data[id].embalaje}</h5>
                <h5>Categoria: ${data[id].categoria}</h5>
                <input type="text" id="idSalida" name="idSalida" value="${data[id].codigo}" hidden>
                <button id="btn-configurar" class="btn-configurar" data-bs-toggle="modal" data-bs-target="#modalSalidas"
                data-descripcion="${data[id].descripcion}" data-id-salida="${data[id].codigo}"
                 style="background-color: #007bff; color: white; border: none; padding: 10px 20px; cursor: pointer;">
                   CONFIGURAR
                </button>
            `;
            contenedor.appendChild(card);
          }
        })
        .catch((error) => {
          console.error("Error al buscar salidas:", error);
        });
    }
  }

  document
    .getElementById("btGuardarSalidas")
    .addEventListener("click", function () {
      const idSalida = document.getElementById("idSalida").value;
      const embalaje = document.getElementById("embalaje").value;
      const calibre = document.getElementById("calibre").value;
      const tipoProceso = document.getElementById("inputTipoProceso").value;
      let empresaSeleccionada = document.getElementById("comboEmpresa").value;
      let procesoSeleccionado = document.getElementById("comboProceso").value;
      const categoria = document.getElementById("categoria").value;
      if (embalaje && calibre) {
        guardarSalida(idSalida, embalaje, calibre, empresaSeleccionada, procesoSeleccionado, categoria, tipoProceso)
          .then((data) => {
            console.log(data);
            alert("Salida configurada correctamente");
            pintarSalidas();
            modalSalidas.querySelector(".btn-close").click();
          })
          .catch((error) => {
            console.error("Error al guardar salida:", error);
            alert("Error al configurar la salida: " + error.message);
          });
      } else {
        alert("Por favor, seleccione un embalaje y calibre antes de guardar.");
      }
    });

  document
    .querySelector("#btn-desbloquear")
    .addEventListener("click", function () {
      const comboEmpresa = document.getElementById("comboEmpresa");
      const comboProceso = document.getElementById("comboProceso");
      comboEmpresa.disabled = false;
      comboProceso.disabled = false;
      document.querySelector(".btn-bloquear").hidden = false;
      document.querySelector(".btn-desbloquear").hidden = true;
      location.reload();
    });
});
