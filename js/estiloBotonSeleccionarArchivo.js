"use strict";
//utiliza el evento DOMContentLoaded para asegurarse de que el DOM esté completamente cargado antes de ejecutar el código
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('foto');
    const label = document.getElementById('nombreArchivo');
    if (input && label) {//si el input y el label existen
        input.addEventListener('change', function() {//cuando se selecciona un archivo
            label.textContent = this.files[0]?.name || 'Ningún archivo seleccionado';//si no hay archivo seleccionado, muestra por defecto "Ningún archivo seleccionado". si hay archivo seleccionado muestra el nombre del archivo seleccionado
        });
    }
});
