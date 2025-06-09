/**
 * Funcionalidades WebSocket para comunicación en tiempo real
 */

// Capturar errores globales de JavaScript
window.onerror = function(msg, url, line, col, error) {
    console.error(`Error: ${msg} en ${url} línea ${line}:${col}`, error);
    return false;
};

console.log("Iniciando conexión WebSocket...");
document.addEventListener('DOMContentLoaded', function() {
    // Creamos la conexión WebSocket
    const ws = new WebSocket('ws://localhost:8080');
    
    // Cuando se abre la conexión, nos registramos como "cocina"
    ws.onopen = function() {
        console.log('Conexión WebSocket establecida');
        // Registramos este cliente como de tipo cocina
        ws.send(JSON.stringify({
            tipoCliente: 'cocina'
        }));
    };
    
    // Manejador de mensajes recibidos
    ws.onmessage = function(event) {
        console.log('Mensaje recibido del servidor:', event.data);
        const mensaje = JSON.parse(event.data);
        
        // Si es un nuevo pedido, lo agregamos a la interfaz
        if (mensaje.tipo === 'nuevoPedido') {
            console.log('Nuevo pedido recibido:', mensaje.datos);
            if (mensaje.datos && mensaje.datos.area === 'cocina') {
                agregarNuevoPedido(mensaje.datos);
            }
        }
        
        // Si es una actualización de estado de producto
        if (mensaje.tipo === 'actualizacionEstadoProducto') {
            console.log('Actualización de producto recibida:', mensaje);
            const elementoProducto = document.querySelector(`li[data-cod-producto="${mensaje.codProducto}"]`);
            
            if (elementoProducto) {
                const estadoElement = elementoProducto.querySelector('.estadoProducto');
                if (estadoElement) {
                    estadoElement.textContent = mensaje.estado;
                }
                
                if (mensaje.estado === 'preparando') {
                    elementoProducto.classList.add('bg-yellow-50');
                    if (estadoElement) estadoElement.style.cssText = 'background-color:#fef3c7; color:#92400e; border-radius:9999px; padding:2px 8px;';
                    
                    // Desactivamos el botón de preparando
                    const btnPreparando = elementoProducto.querySelector('.btnPreparando');
                    if (btnPreparando) {
                        btnPreparando.disabled = true;
                        btnPreparando.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                } else if (mensaje.estado === 'listo') {
                    elementoProducto.classList.remove('bg-yellow-50');
                    elementoProducto.classList.add('bg-green-50');
                    // Desactivamos ambos botones
                    const btnPreparando = elementoProducto.querySelector('.btnPreparando');
                    const btnListo = elementoProducto.querySelector('.btnListo');
                    if (btnPreparando) {
                        btnPreparando.disabled = true;
                    }
                    
                    // Buscar el pedido completo
                    const pedido = elementoProducto.closest('.pedido');
                    if (pedido) {
                        pedido.classList.add('border-green-500');
                        const btnCompletado = pedido.querySelector('.btnCompletado');
                        if (btnCompletado) {
                            btnCompletado.classList.remove('bg-[#72E8AC]', 'text-[#256353]');
                            btnCompletado.classList.add('bg-green-500', 'text-white');
                            btnCompletado.innerHTML = `
                                <span class="material-symbols-outlined text-sm align-middle mr-1">check_circle</span> Listo para servir
                            `;
                        }
                    }
                }
            }
        }
    };
    
    // Manejo de errores y reconexión
    ws.onclose = function() {
        console.log('Conexión cerrada. Intentando reconexión en 5 segundos...');
        setTimeout(function() {
            window.location.reload();
        }, 5000);
    };
    
    ws.onerror = function(error) {
        console.error('Error en la conexión WebSocket:', error);
    };
});
