"use strict";

// Capturo errores globales de JavaScript
window.onerror = function(mensaje, url, linea, columna, error) {
    console.error(`Error: ${mensaje} en ${url} línea ${linea}:${columna}`, error);
    return false;
};

console.log("Iniciando conexión WebSocket...");
document.addEventListener('DOMContentLoaded', function() {
    // Creo la conexión WebSocket
    let wsUrl;
if (window.location.hostname === "localhost") {
    wsUrl = window.location.hostname === "localhost" ? "ws://localhost:8081" : "wss://websocket-u5s9.onrender.com";
} else {
    wsUrl = "wss://ws-proyecto-trk1.onrender.com"; // Cambia este dominio si tu WebSocket Render tiene otro nombre
}
const ws = new WebSocket(wsUrl);
    
    // Cuando se abre la conexión, detecto en qué página estamos y registro al cliente
    ws.onopen = function() {
        console.log('Conexión WebSocket establecida');
        
        // Detecto la página actual según los elementos del DOM
        let tipoCliente = 'visitante';
        
        if (document.getElementById('contenedorPedidos')) {
            // Estamos en la página del camarero
            tipoCliente = 'camarero';
            console.log('Registrando como camarero');
        } else if (document.getElementById('contenedorPedidosCocina')) {
            // Estamos en la página de cocina
            tipoCliente = 'cocina';
            console.log('Registrando como cocina');
        }
        
        // Registro este cliente según el tipo detectado
        ws.send(JSON.stringify({
            tipoCliente: tipoCliente
        }));
    };
    
    // Manejador de mensajes recibidos
    ws.onmessage = function(evento) {
        console.log('Mensaje recibido del servidor:', evento.data);
        const mensaje = JSON.parse(evento.data);
        
        // Detecto en qué página estamos
        const esPaginaCamarero = document.getElementById('contenedorPedidos') !== null;
        const esPaginaCocina = document.getElementById('contenedorPedidosCocina') !== null;
        
        // Si es un nuevo pedido
        if (mensaje.tipo === 'nuevoPedido') {
            console.log('Nuevo pedido recibido:', mensaje.datos);
            // En página de cocina
            if (esPaginaCocina && mensaje.datos && mensaje.datos.area === 'cocina') {
                if (typeof agregarNuevoPedido === 'function') {
                    agregarNuevoPedido(mensaje.datos);
                }
            }
            // En página de camarero, recargo pedidos si hay algún producto listo
            else if (esPaginaCamarero && mensaje.datos && mensaje.datos.productos && 
                     mensaje.datos.productos.some(p => p.estado === 'listo')) {
                if (typeof agregarNuevoPedidoListo === 'function') {
                    agregarNuevoPedidoListo(mensaje.datos);
                }
            }
        }
        
        // Si es una actualización de estado de producto
        else if (mensaje.tipo === 'actualizacionEstadoProducto' || 
                 mensaje.tipo === 'productoListo' || 
                 mensaje.tipo === 'productoServido') {
            console.log('Actualización de producto recibida:', mensaje);
            
            // En página de cocina
            if (esPaginaCocina) {
                const elementoProducto = document.querySelector(`li[data-cod-producto="${mensaje.codProducto}"]`);
                
                if (elementoProducto) {
                    const estadoElement = elementoProducto.querySelector('.estadoProducto');
                    if (estadoElement) {
                        estadoElement.textContent = mensaje.estado || '';
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
                    } 
                    else if (mensaje.estado === 'listo') {
                        elementoProducto.classList.remove('bg-yellow-50');
                        elementoProducto.classList.add('bg-green-50');
                        // Desactivo ambos botones
                        const btnPreparando = elementoProducto.querySelector('.btnPreparando');
                        const btnListo = elementoProducto.querySelector('.btnListo');
                        if (btnPreparando) {
                            btnPreparando.disabled = true;
                        }
                        
                        // Busco el pedido completo
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
           
            else if (esPaginaCamarero) {
                if (mensaje.estado === 'listo') {
                    // Recargo para mostrar el nuevo producto listo
                    if (typeof cargarPedidosListos === 'function') {
                        cargarPedidosListos();
                    }
                }
                else if (mensaje.tipo === 'productoServido') {
                    // Actualizo la UI eliminando el producto servido
                    if (typeof actualizarProductoServido === 'function') {
                        actualizarProductoServido(mensaje);
                    }
                }
            }
        }
        
        // Si es una actualización de estado de pedido completo
        else if (mensaje.tipo === 'pedidoServido' || mensaje.tipo === 'pedidoCompletado') {
            if (esPaginaCamarero && typeof cargarPedidosListos === 'function') {
                cargarPedidosListos();
            }
            else if (esPaginaCocina) {
                // Actualizo el estado del pedido en la cocina si es necesario
                const pedidoElement = document.querySelector(`div[data-cod-pedido="${mensaje.codPedido}"]`);
                if (pedidoElement) {
                    pedidoElement.classList.add('border-green-500');
                }
            }
        }
    }
    // Manejo de errores
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
