"use strict";
// Función para obtener la clave del carrito
function obtenerKeyCarrito() {
    
    const idUsuario = document.body.getAttribute('data-user-id') || '';
    return idUsuario ? `carrito_${idUsuario}` : 'carrito';
}

document.addEventListener('DOMContentLoaded', function() {
    // Limpio el carrito
    localStorage.removeItem(obtenerKeyCarrito()); 
    
    const icono = document.querySelector('.material-symbols-outlined');
    icono.style.transform = 'scale(0.5)';
    icono.style.opacity = '0';
    icono.style.transition = 'all 0.5s ease-out';
    
    setTimeout(function() {
        icono.style.transform = 'scale(1)';
        icono.style.opacity = '1';
    }, 100);
});
