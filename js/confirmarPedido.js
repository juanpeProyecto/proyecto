// Función para obtener la clave del carrito
function obtenerKeyCarrito() {
    // Usar 'carrito' como clave por defecto o combinarla con el ID de usuario si está disponible
    const userId = document.body.getAttribute('data-user-id') || '';
    return userId ? `carrito_${userId}` : 'carrito';
}

document.addEventListener('DOMContentLoaded', function() {
    // Limpio el carrito ahora que el DOM está listo y el script parseado
    localStorage.removeItem(obtenerKeyCarrito()); 
    
    // Actualizar contador si la función está disponible
    if(typeof actualizarContadorCarrito === 'function') {
        actualizarContadorCarrito();
    }

    const icono = document.querySelector('.material-symbols-outlined');
    icono.style.transform = 'scale(0.5)';
    icono.style.opacity = '0';
    icono.style.transition = 'all 0.5s ease-out';
    
    setTimeout(function() {
        icono.style.transform = 'scale(1)';
        icono.style.opacity = '1';
    }, 100);
});
