function showContent(tabId) {
  // 1. Ocultar todas las secciones
  document.querySelectorAll(".tab-content").forEach(function (content) {
    content.style.display = "none";
  });

  // 2. Mostrar la sección seleccionada
  let seccionDestino = document.getElementById(tabId);
  if (seccionDestino) {
      seccionDestino.style.display = "block";
  }

  // 3. Guardar en memoria qué tab se acaba de abrir
  sessionStorage.setItem('tabActivo', tabId);

  // 4. Limpiar TODOS los tabs (quitar la clase active y reiniciar sus colores)
  document.querySelectorAll(".nav-link").forEach(function (tab) {
    tab.classList.remove("active");
    tab.style.backgroundColor = "transparent";
    tab.style.color = "#0d6efd";
  });

  // 5. Buscar el tab que corresponde a este ID y aplicarle el nuevo color
  let tabActivado = document.querySelector(`.nav-link[onclick*="${tabId}"]`);

  if (tabActivado) {
      tabActivado.classList.add("active");

      tabActivado.style.backgroundColor = "#198754";
      tabActivado.style.color = "white";
      tabActivado.style.borderRadius = "5px 5px 0 0";
  }
}

// 6. Escuchar cuando la página termine de cargar para recuperar la memoria
document.addEventListener("DOMContentLoaded", function() {
    let tabGuardado = sessionStorage.getItem('tabActivo');

    if (tabGuardado && document.getElementById(tabGuardado)) {
        showContent(tabGuardado);
    }
});