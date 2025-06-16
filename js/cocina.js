"use strict";

// Función para mostrar notificaciones al usuario
function mostrarNotificacion(tipo, mensaje) {
    // Creo el contenedor de notificaciones si no existe
    let notificaciones = document.getElementById('notificaciones');
    if (!notificaciones) {
        notificaciones = document.createElement('div');
        notificaciones.id = 'notificaciones';
        notificaciones.style.position = 'fixed';
        notificaciones.style.top = '20px';
        notificaciones.style.right = '20px';
        notificaciones.style.zIndex = '1000';
        document.body.appendChild(notificaciones);
    }
    
    // Creo la notificación
    const notificacion = document.createElement('div');
    notificacion.className = `p-4 mb-2 rounded-lg shadow-lg ${tipo === 'error' ? 'bg-red-100 text-red-800 border-l-4 border-red-500' : 
                             tipo === 'success' ? 'bg-green-100 text-green-800 border-l-4 border-green-500' :
                             'bg-blue-100 text-blue-800 border-l-4 border-blue-500'}`;
    notificacion.innerHTML = `
        <div class="flex items-center">
            <span class="mr-2">${mensaje}</span>
            <button class="ml-auto text-gray-500 hover:text-gray-700" onclick="this.parentElement.parentElement.remove()">
                &times;
            </button>
        </div>
    `;
    
    // Añado la notificación al contenedor
    notificaciones.appendChild(notificacion);
    
    // Elimino autoaicamente después de 5 segundos
    setTimeout(() => {
        if (notificacion.parentNode) {
            notificacion.style.opacity = '0';
            notificacion.style.transition = 'opacity 0.5s';
            setTimeout(() => notificacion.remove(), 500);
        }
    }, 5000);
}

// Función para formatear fechas en un formato legible (he tenido que hacerlo asi por que si no la fecha me saliaco o null ,mo se por que)
function formatearFechaHora(fechaString) {
    try {
        const fecha = new Date(fechaString);
        if (isNaN(fecha.getTime())) {
            return 'Fecha no disponible';
        }
        
        // Formato: HH:MM - DD/MM/YYYY
        const horas = fecha.getHours().toString().padStart(2, '0');
        const minutos = fecha.getMinutes().toString().padStart(2, '0');
        const dia = fecha.getDate().toString().padStart(2, '0');
        const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
        const año = fecha.getFullYear();
        
        return `${horas}:${minutos} - ${dia}/${mes}/${año}`;
    } catch (error) {
        return 'Fecha no disponible';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Mostrar mensaje de "sin pedidos" inmediatamente al cargar la página
    mostrarMensajeSinPedidos();
    inicializar();
});

function mostrarMensajeSinPedidos() {
    const contenedorPedidos = document.getElementById('contenedorPedidos');
    if (!contenedorPedidos) return;
    
    let sinPedidos = document.getElementById('sin-pedidos');
    
    // Si no existe el elemento, lo creamos
    if (!sinPedidos) {
        sinPedidos = document.createElement('div');
        sinPedidos.id = 'sin-pedidos';
        sinPedidos.className = 'text-center py-12 px-4';
        sinPedidos.innerHTML = `
            <span class="material-symbols-outlined text-gray-400 text-5xl mb-3">kitchen</span>
            <p class="text-xl text-gray-700 font-medium mb-2">No hay pedidos pendientes</p>
            <p class="text-gray-500">Los pedidos pendientes aparecerán aquí</p>
        `;
        contenedorPedidos.appendChild(sinPedidos);
    } else {
        sinPedidos.classList.remove('hidden');
    }
}

function inicializar() {
    
    
    // Inicializo los botones de estado
    inicializarBotones();
    
    // Cargo los pedidos pendientes al iniciar - Usamos nuestra propia versión
    // para evitar conflictos con gestionPedidos.js
    cargarPedidosCocina();
    
    // Configuro la conexión WebSocket
    iniciarWebSocket();
    
    // Actualizo los contadores
    actualizarContadores();
    
    // Oculto la carga cuando todo esté listo
    window.addEventListener('load', ocultarCarga);
    
    // Por si acaso, oculto la carga después de 3 segundos como máximo
    setTimeout(ocultarCarga, 3000);
}

// Función para actualizar el estado de un producto individual
function actualizarEstadoProducto(boton, estado, codPedido, codProducto, numMesa) {
    // Obtengo el contenedor del producto y del pedido
    const elementoProducto = boton.closest('.producto');
    const elementoPedido = boton.closest('.pedido-card');
    
    if (!elementoProducto || !elementoPedido) {
        const error = new Error('No se pudo encontrar el contenedor del producto o pedido');
        return Promise.reject(error);
    }
    
    // Obtengo el estado actual del producto
    const estadoActual = elementoProducto.getAttribute('data-estado') || 'pendiente';
    
    // Si el estado no cambia, no hacemos nada
    if (estado === estadoActual) {
        return Promise.resolve({ success: true, mensaje: 'El estado ya estaba actualizado' });
    }
    
    // Guardo el HTML original del botón para restaurarlo en caso de error
    const botonHTML = boton.innerHTML;
    const botonClases = boton.className;
    
    // Muestro el indicador de carga en el botón
    boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    boton.disabled = true;
    
    // Actualizo la interfaz inmediatamente para mejor experiencia de usuario
    actualizarEstadoProductoUI(codProducto, estado);
    
    // Si el estado es 'listo', eliminamos el producto inmediatamente
    if (estado === 'listo' && elementoProducto && elementoProducto.parentNode) {
        elementoProducto.remove();
        actualizarContadores();
        // Si no hay más productos en el pedido, eliminamos el pedido
        if (elementoPedido) {
            const productosRestantes = elementoPedido.querySelectorAll('.producto');
            if (productosRestantes.length === 0) {
                const mesaContenedor = elementoPedido.closest('.mb-8');
                elementoPedido.remove();
                if (mesaContenedor) {
                    verificarYocultarMesaSiVaciaCocina(mesaContenedor);
                }
            }
        }
    }
    
    // Creo una promesa para manejar la actualización
    return new Promise((resolve, reject) => {
        // Función para restaurar el botón a su estado original
        const restaurarBoton = () => {
            boton.innerHTML = botonHTML;
            boton.className = botonClases;
            boton.disabled = false;
        };

        // Envio la solicitud al servidor
        fetch('cocina.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'actualizarEstadoProducto',
                codPedido: codPedido,
                codProducto: codProducto,
                estado: estado,
                area: 'cocina',
                codEmpleado: 1, 
                numMesa: numMesa || '0',
                estadoAnterior: estadoActual 
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data) {
                throw new Error('No se recibio respuesta del servidor');
            }

            // Verifico si el estado no cambio en el servidor
            if (data.mensaje && data.mensaje.includes('no se afectaron filas')) {
                // Recargamos los pedidos para sincronizar con el servidor
                return cargarPedidosCocina().then(() => {
                    restaurarBoton();
                    return resolve({ success: true, mensaje: 'Estado sincronizado con el servidor' });
                }).catch(error => {
                    restaurarBoton();
                    return resolve({ success: true, mensaje: 'El estado ya estaba actualizado' });
                });
            }
            
            if (data.success) {
                // Notificación WebSocket si un producto está listo
                if (estado === 'listo') {
                    if (window.ws && window.ws.readyState === WebSocket.OPEN) {
                        const notificacion = {
                            tipo: 'productoListo',
                            mensaje: `¡Producto listo para la mesa ${numMesa || 'sin mesa'}!`,
                            codProducto: codProducto,
                            codPedido: codPedido,
                            numMesa: numMesa
                        };
                        window.ws.send(JSON.stringify(notificacion));
                    } else {
                    }
                }

                // Verifico si el pedido está completo
                if (data.pedidoCompleto) {
                    // El producto y posiblemente el pedido ya se eliminaron en la UI
                    actualizarContadores();
                } else {
                    actualizarEstadoProductoUI(codProducto, estado);
                    if (elementoPedido) {
                        const estadoPedidoElement = elementoPedido.querySelector('.estado-pedido');
                        if (data.estadoPedido && estadoPedidoElement) {
                            estadoPedidoElement.textContent = data.estadoPedido.charAt(0).toUpperCase() + data.estadoPedido.slice(1);
                            estadoPedidoElement.className = 'px-2 py-1 text-xs font-medium rounded-full ';
                            if (data.estadoPedido === 'preparando') {
                                estadoPedidoElement.classList.add('bg-yellow-100', 'text-yellow-800', 'border-yellow-500');
                                elementoPedido.classList.remove('border-blue-500');
                            } else {
                                estadoPedidoElement.classList.add('bg-blue-100', 'text-blue-800', 'border-blue-500');
                                elementoPedido.classList.remove('border-yellow-500', 'border-green-500');
                            }
                        }
                    }
                }
                
                actualizarContadores();
                resolve(data);
            } else {
                // Si hay un mensaje específico, usarlo, de lo contrario mensaje genérico
                const errorMensaje = data.mensaje || 'Error desconocido al actualizar el estado';
                throw new Error(errorMensaje);
            }
        })
        .catch(error => {
            // Restauro el botón a su estado original
            restaurarBoton();
            
            // Muestro notificación de error
            mostrarNotificacion('error', 'Error al actualizar el estado del producto: ' + (error.message || 'Error desconocido'));
            
            // Rechazo la promesa con el error
            reject(error);
        });
    });
}

// Inicializar los botones de cambio de estado de todos los productos
function inicializarBotones() {
    // Inicializo botones de completar pedido
    inicializarBotonesCompletarPedido();
    
    // Selecciono todos los botones de estado
    const botones = document.querySelectorAll('.btn-estado');
    
    // Elimino manejadores de eventos anteriores para evitar duplicados
    botones.forEach(boton => {
        const newBoton = boton.cloneNode(true);
        boton.parentNode.replaceChild(newBoton, boton); // Reemplazo el botón original con el clon
    });
    
    // Vuelvo a seleccionar los botones después del clonado
    const botonesActualizados = document.querySelectorAll('.btn-estado');
    
    botonesActualizados.forEach(boton => {
        // Función para obtener atributos con diferentes formatos
        const getDataAttribute = (elemento, claves) => {
            if (!elemento) return null;
            
            for (const clave of claves) {
                try {
                    // Pruebo con prefijos de datos
                    const dataKey = clave.startsWith('data-') ? clave : `data-${clave}`;
                    let value = elemento.getAttribute(dataKey);
                    if (value !== null && value !== '' && value !== 'undefined') {
                        return value;
                    }
                    
                    // Pruebo sin prefijo
                    value = elemento.getAttribute(clave);
                    if (value !== null && value !== '' && value !== 'undefined') {
                        return value;
                    }
                    
                    // Pruebo con camelCase
                    const camelCaseKey = clave.replace(/-([a-z])/g, (_, char) => char.toUpperCase());
                    value = elemento.getAttribute(camelCaseKey);
                    if (value !== null && value !== '' && value !== 'undefined') {
                        return value;
                    }
                } catch (error) {
                }
            }
            return null;
        };
        
        let codPedido = getDataAttribute(boton, ['data-cod-pedido', 'codPedido', 'pedido-id', 'data-pedido-id']);
        let codProducto = getDataAttribute(boton, ['data-cod-producto', 'codProducto', 'producto-id', 'data-producto-id']);
        let numMesa = getDataAttribute(boton, ['data-num-mesa', 'numMesa', 'mesa', 'data-mesa']);
        
        // Intento obtener los datos del DOM si no están en el botón o son inválidos
        const producto = boton.closest('.producto');
        if (producto) {
            // Obtengo el ID del producto del contenedor del producto
            const productoId = getDataAttribute(producto, ['data-cod-producto', 'codProducto', 'producto-id', 'data-producto-id']);
            
            // Obtengo el ID del pedido del contenedor del pedido
            const pedidoElement = producto.closest('.pedido-card');
            
            const pedidoId = pedidoElement ? getDataAttribute(pedidoElement, ['data-cod-pedido', 'codPedido', 'pedido-id', 'data-pedido-id']) : null;
            
            // Obtengo el número de mesa del contenedor del pedido si no está en el botón
            if (pedidoElement && (!numMesa || numMesa === '0' || numMesa === 'undefined')) {
                numMesa = getDataAttribute(pedidoElement, ['data-num-mesa', 'numMesa', 'mesa', 'data-mesa']) ||
                         (pedidoElement.closest('.mb-8')?.querySelector('h2')?.textContent?.match(/Mesa\s*(\d+)/i)?.[1]) ||
                         '0';
            }
            
            // Actualizo valores si se encontraron en el DOM
            if (pedidoId && (!codPedido || codPedido === '0' || codPedido === 'undefined')) {
                codPedido = pedidoId;
                boton.setAttribute('data-cod-pedido', codPedido);
            }
            
            if (productoId && (!codProducto || codProducto === '0' || codProducto === 'undefined')) {
                codProducto = productoId;
                boton.setAttribute('data-cod-producto', codProducto);
            }
            
            if (!numMesa || numMesa === '0' || numMesa === 'undefined') {
                numMesa = '0';
                boton.setAttribute('data-num-mesa', numMesa);
            }
        }
        
        // Valido que los datos requeridos estén presentes
        if (!codPedido || !codProducto) {
            boton.disabled = true;
            boton.title = 'Error: Faltan datos del pedido o producto';
            boton.classList.add('opacity-50', 'cursor-not-allowed');
            return;
        }
        
        // Configuro el evento click
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Obtengo los datos del botón usando la función getDataAttribute para mayor robustez
            const estado = getDataAttribute(this, ['data-estado', 'estado']);
            const codPedido = getDataAttribute(this, ['data-cod-pedido', 'codPedido', 'pedido-id', 'data-pedido-id']);
            const codProducto = getDataAttribute(this, ['data-cod-producto', 'codProducto', 'producto-id', 'data-producto-id']);
            const numMesa = getDataAttribute(this, ['data-num-mesa', 'numMesa', 'mesa', 'data-mesa']) || '0';
            
            // Valido que los datos requeridos estén presentes
            if (!codPedido || !codProducto) {
                // Muestro un mensaje de error en la interfaz
                const errorMsg = document.createElement('div');
                errorMsg.className = 'text-red-600 text-sm mt-1';
                errorMsg.textContent = 'Error: Faltan datos del pedido o producto';
                this.parentNode.appendChild(errorMsg);
                
                // Elimino el mensaje después de 3 segundos
                setTimeout(() => {
                    if (errorMsg.parentNode) {
                        errorMsg.parentNode.removeChild(errorMsg);
                    }
                }, 3000);
                
                return;
            }
            
            // Deshabilito temporalmente el botón para evitar múltiples clics
            this.disabled = true;
            this.classList.add('opacity-50', 'cursor-wait');
            
            // Llamo a la función para actualizar el estado del producto
            actualizarEstadoProducto(this, estado, codPedido, codProducto, numMesa)
                .finally(() => {
                    // Re-habilito el botón después de un breve retraso
                    setTimeout(() => {
                        this.disabled = false;
                        this.classList.remove('opacity-50', 'cursor-wait');
                    }, 1000);
                });
        });
        
        // Marco el botón como inicializado
        boton.setAttribute('data-inicializado', 'true');
        
        // Actualizo el estado visual del botón
        const productoElemento = boton.closest('.producto');
        const estadoActual = productoElemento ? productoElemento.getAttribute('data-estado') || 'pendiente' : 'pendiente';
        const estadoBoton = boton.getAttribute('data-estado');
        
        if (estadoBoton === 'preparando' && estadoActual === 'preparando') {
            boton.classList.add('bg-yellow-500', 'text-white');
            boton.classList.remove('bg-gray-100', 'text-gray-800');
        } else if (estadoBoton === 'listo' && estadoActual === 'listo') {
            boton.classList.add('bg-green-500', 'text-white');
            boton.classList.remove('bg-gray-100', 'text-gray-800');
        }
    });
}

// Cargo ñlos pedidos pendientes al iniciar (versión específica para cocina)
function cargarPedidosCocina() {
    mostrarCarga();
    
    fetch('cocina.php?action=obtenerPedidosPendientes&area=cocina')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success || !data.pedidos) {
                throw new Error('Formato de respuesta inválido');
            }
            
            // Renderizo los pedidos
            renderizarPedidos(data.pedidos);
            actualizarContadores();
        })
        .catch(error => {
            console.error('Error al cargar pedidos:', error);
            contenedorPedidos.innerHTML = `
                <div class="text-center py-8">
                    <p class="text-red-600">Error al cargar los pedidos. Por favor, recarga la página.</p>
                </div>`;
        });
}

function iniciarWebSocket() {
    const wsUrl = 'ws://localhost:8080';

    // Primero, verifico si el navegador soporta WebSockets
    if (!('WebSocket' in window)) {
        mostrarNotificacion('error', 'Tu navegador no soporta WebSockets, las actualizaciones en tiempo real no funcionarán.');
        return;
    }

    // Prevengo múltiples conexiones si ya está abierta o conectando
    if (window.ws && (window.ws.readyState === WebSocket.OPEN || window.ws.readyState === WebSocket.CONNECTING)) {
        return;
    }

    window.ws = new WebSocket(wsUrl);

    window.ws.onopen = function() {
        // Registro este cliente como 'cocina'
        const registro = {
            tipo: 'registro',
            tipoCliente: 'cocina'
        };
        window.ws.send(JSON.stringify(registro));

        // Habilito/deshabilito botones según el estado de los productos
        document.querySelectorAll('.producto').forEach(producto => {
            const productoId = producto.getAttribute('data-cod-producto') || 'N/A';
            const estado = producto.getAttribute('data-estado');

            const botonPreparar = producto.querySelector('.btn-preparar'); 
            if (botonPreparar) {
                if (estado === 'pendiente') {
                    botonPreparar.disabled = false;
                } else {
                    botonPreparar.disabled = true;
                }
            }

            const botonListo = producto.querySelector('.btn-listo');
            if (botonListo) {
                if (estado === 'preparando') {
                    botonListo.disabled = false;
                } else {
                    botonListo.disabled = true;
                }
            }
        });
    };

    window.ws.onmessage = function(event) {
        try {
            const data = JSON.parse(event.data);

            switch (data.tipo) {
                case 'nuevoPedido':
                    mostrarNotificacion('info', `Nuevo pedido para la mesa ${data.numMesa || ''}. Actualizando...`);
                    cargarPedidosCocina();
                    break;

                case 'productoListo':
                    actualizarEstadoProductoUI(data.codProducto, 'listo');
                    
                    const pedidoCard = document.querySelector(`.pedido-card[data-cod-pedido='${data.codPedido}']`);
                    if (pedidoCard) {
                        const productos = pedidoCard.querySelectorAll('.producto');
                        const todosListos = Array.from(productos).every(p => p.getAttribute('data-estado') === 'listo');
                        
                        if (todosListos) {
                            const mesaContenedor = pedidoCard.closest('.mb-8');
                            pedidoCard.remove();
                            actualizarContadores();
                            if (mesaContenedor) {
                                verificarYocultarMesaSiVaciaCocina(mesaContenedor);
                            }
                        }
                    }
                    break;
                default:
            }
        } catch (error) {
        }
    };

    window.ws.onclose = function(event) {
        window.ws = null;

        // Deshabilito todos los botones de acción
        document.querySelectorAll('.btn-preparando, .btn-listo').forEach(boton => {
            boton.disabled = true;
        });

        // Reintento la conexión
        setTimeout(iniciarWebSocket, 3000);
    };

    window.ws.onerror = function(error) {
        mostrarNotificacion('error', 'Error en la conexión en tiempo real. Intentando reconectar...');
        window.ws.close(); 
    };
}

// Función para actualizar la interfaz de usuario cuando cambia el estado de un producto
function actualizarEstadoProductoUI(codProducto, estado) {
    // Busco el producto específico usando el ID del producto
    const elementoProducto = document.querySelector(`.producto[data-cod-producto="${codProducto}"]`);
    
    if (!elementoProducto) {
        return;
    }
    
    // Actualizo el atributo data-estado
    elementoProducto.setAttribute('data-estado', estado);
    
    // Actualizo las clases del contenedor del producto
    elementoProducto.classList.remove('bg-yellow-50', 'bg-green-50', 'bg-gray-50', 'border-yellow-200', 'border-green-200', 'border-gray-200');
    if (estado === 'preparando') {
        elementoProducto.classList.add('bg-yellow-50', 'border-yellow-200');
    } else if (estado === 'listo') {
        elementoProducto.classList.add('bg-green-50', 'border-green-200');
    } else {
        elementoProducto.classList.add('bg-gray-50', 'border-gray-200');
    }
    
    // Actualizo el texto del estado
    let estadoElement = elementoProducto.querySelector('.estado-producto');
    if (!estadoElement) {
        estadoElement = document.createElement('div');
        estadoElement.className = 'estado-producto text-sm font-medium px-2 py-1 rounded mt-1';
        const btnContainer = elementoProducto.querySelector('.flex.space-x-2');
        if (btnContainer) {
            btnContainer.after(estadoElement);
        } else {
            elementoProducto.appendChild(estadoElement);
        }
    }
    
    // Configuro actualización del estado
    const estadoMostrar = estado.charAt(0).toUpperCase() + estado.slice(1);
    estadoElement.textContent = `Estado: ${estadoMostrar}`;
    
    // Actualizo clases de estilo según el estado
    estadoElement.className = 'estado-producto text-sm font-medium px-2 py-1 rounded mt-1 ';
    
    if (estado === 'preparando') {
        estadoElement.classList.add('bg-yellow-100', 'text-yellow-800');
        elementoProducto.classList.add('bg-yellow-50');
    } else if (estado === 'listo') {
        estadoElement.classList.add('bg-green-100', 'text-green-800');
        elementoProducto.classList.add('bg-green-50');
    } else {
        estadoElement.classList.add('bg-gray-100', 'text-gray-800');
    }
    
    // Actualizo los botones de acción
    const btnPreparar = elementoProducto.querySelector('.btn-preparar');
    const btnListo = elementoProducto.querySelector('.btn-listo');
    
    // Actualizo el botón de preparar
    if (btnPreparar) {
        btnPreparar.disabled = estado !== 'pendiente';
        btnPreparar.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-yellow-100', 'text-yellow-800');
        
        if (estado === 'pendiente') {
            btnPreparar.classList.add('bg-gray-100', 'text-gray-800');
        } else if (estado === 'preparando') {
            btnPreparar.classList.add('opacity-50', 'cursor-not-allowed', 'bg-yellow-100', 'text-yellow-800');
        } else {
            btnPreparar.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }
    
    // Actualizo el botón de listo
    if (btnListo) {
        btnListo.disabled = estado !== 'preparando';
        btnListo.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-green-100', 'text-green-800');
        
        if (estado === 'preparando') {
            btnListo.classList.add('bg-green-100', 'text-green-800');
        } else if (estado === 'listo') {
            btnListo.classList.add('opacity-50', 'cursor-not-allowed', 'bg-green-100', 'text-green-800');
        } else {
            btnListo.classList.add('bg-gray-100', 'text-gray-800');
        }
    }
    
    // Verifico si todos los productos del pedido están listos
    const tarjetaPedido = elementoProducto.closest('.pedido-card');
    if (tarjetaPedido) {
        const productos = tarjetaPedido.querySelectorAll('.producto');
        const todosListos = Array.from(productos).every(p => p.getAttribute('data-estado') === 'listo');
        
        actualizarEstadoPedido(tarjetaPedido, todosListos);
    }
    
    // Actualizo contadores
    actualizarContadores();
}

// Función auxiliar para actualizar el estado visual del pedido
function actualizarEstadoPedido(tarjetaPedido, productosListos) {
    const btnCompletar = tarjetaPedido.querySelector('.btnCompletado');
    let estadoPedido = tarjetaPedido.querySelector('.estado-pedido');
    
    if (productosListos) {
        // Habilito el  botón de completar si todos los productos están listos
        if (btnCompletar) {
            btnCompletar.disabled = false;
            btnCompletar.classList.remove('opacity-50', 'cursor-not-allowed');
            btnCompletar.classList.add('bg-green-500', 'hover:bg-green-600', 'text-white');
        }
        
        // Crear o actualizar estado visual del pedido
        if (!estadoPedido) {
            estadoPedido = document.createElement('span');
            estadoPedido.className = 'estado-pedido text-xs font-medium px-2 py-1 rounded-full';
            const header = tarjetaPedido.querySelector('.pedido-header');
            if (header) {
                header.appendChild(estadoPedido);
            }
        }
        
        estadoPedido.textContent = 'Listo para servir';
        estadoPedido.className = 'estado-pedido text-xs font-medium px-2 py-1 rounded-full bg-green-100 text-green-800';
        
        // Actualizo el borde de la tarjeta
        tarjetaPedido.classList.remove('border-yellow-500', 'border-red-500');
        tarjetaPedido.classList.add('border-green-500');
    } else {
        // Deshabilito el botón de completar si no todos los productos están listos
        if (btnCompletar) {
            btnCompletar.disabled = true;
            btnCompletar.classList.add('opacity-50', 'cursor-not-allowed');
            btnCompletar.classList.remove('bg-green-500', 'hover:bg-green-600', 'text-white');
        }
        
        // Actualizo el estado visual del pedido a "En preparación"
        if (estadoPedido) {
            estadoPedido.textContent = 'En preparación';
            estadoPedido.className = 'estado-pedido text-xs font-medium px-2 py-1 rounded-full bg-yellow-100 text-yellow-800';
        }
        
        // Actualizo el borde de la tarjeta
        tarjetaPedido.classList.remove('border-green-500', 'border-red-500');
        tarjetaPedido.classList.add('border-yellow-500');
    }
}

// Función para renderizar la lista de pedidos
function renderizarPedidos(pedidos) {
    const contenedorPedidos = document.getElementById('contenedorPedidos');
    if (!contenedorPedidos) return;
    
    // Si no hay pedidos, muestro mensaje usando el div sin-pedidos que ya existe en el HTML
    if (!pedidos || pedidos.length === 0) {
        // Muestro el mensaje directamente en el contenedor
        contenedorPedidos.innerHTML = `
            <div id="sin-pedidos" class="text-center py-12 px-4 bg-white rounded-lg shadow-md">
                <span class="material-symbols-outlined text-[#2563EB] text-5xl mb-3">kitchen</span>
                <p class="text-xl text-gray-700 font-medium mb-2">No hay pedidos pendientes</p>
                <p class="text-gray-500">Los pedidos pendientes aparecerán aquí</p>
            </div>
        `;
        return;
    } else {
        contenedorPedidos.innerHTML = ''; // Limpiamos para añadir los nuevos pedidos
    }
    
    // Agrupo los pedidos por mesa
    const pedidosPorMesa = {};
    pedidos.forEach(pedido => {
        const mesa = pedido.numMesa || 'Sin mesa';
        if (!pedidosPorMesa[mesa]) {
            pedidosPorMesa[mesa] = [];
        }
        pedidosPorMesa[mesa].push(pedido);
    });
    
    // Construyo el HTML de los pedidos
    let html = '';
    
    // Recorro cada mesa
    for (const [numMesa, pedidosMesa] of Object.entries(pedidosPorMesa)) {
        html += `
            <div class="mb-8">
                <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">Mesa ${numMesa}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">`;
        
        // Recorro los pedidos de esta mesa
        pedidosMesa.forEach(pedido => {
            const estadoPedido = pedido.estado || 'pendiente';
            const estadoClase = estadoPedido === 'preparando' ? 'bg-yellow-100 text-yellow-800' :
                                estadoPedido === 'listo' ? 'bg-green-100 text-green-800' :
                                'bg-blue-100 text-blue-800';

            let productosHtml = '';
            if (pedido.productos && pedido.productos.length > 0) {
                pedido.productos.forEach(producto => {
                    let estadoProducto = 'pendiente';
                    if (producto.estado) {
                        estadoProducto = producto.estado.toLowerCase();
                    } else if (producto.estadoProducto) {
                        estadoProducto = producto.estadoProducto.toLowerCase();
                    } else if (producto.estado_producto) {
                        estadoProducto = producto.estado_producto.toLowerCase();
                    }

                    const pedidoIdParaProducto = pedido.codPedido || ''; 
                    const productoId = (producto.codProducto || producto.id || producto.IDProducto || producto.cod) || ''; 
                    const mesaIdParaProducto = numMesa && numMesa !== 'undefined' ? numMesa : '0';
                    
                    // Compruebo múltiples posibles campos para observaciones
                    const tieneObservaciones = (
                        (producto.observaciones && producto.observaciones.trim() !== '') || 
                        (producto.Observaciones && producto.Observaciones.trim() !== '') || 
                        (producto.obs && producto.obs.trim() !== '') || 
                        (producto.comentarios && producto.comentarios.trim() !== '') ||
                        (producto.Comentarios && producto.Comentarios.trim() !== '')
                    );
                    
                    const observacionesTexto = producto.observaciones || producto.Observaciones || producto.obs || producto.comentarios || producto.Comentarios || '';

                    productosHtml += `
                        <div class="producto bg-white rounded-lg p-3 mb-2 border border-gray-100" 
                             data-cod-producto="${productoId}"
                             data-estado="${estadoProducto}"
                             title="Estado: ${estadoProducto.charAt(0).toUpperCase() + estadoProducto.slice(1)}">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-medium text-gray-800">${producto.nombre || 'Producto sin nombre'}</div>
                                    <div class="text-sm text-gray-500 mt-1">Cant: ${producto.cantidad || 1}</div>
                                    ${tieneObservaciones ? `
                                    <div class="text-xs bg-amber-100 text-amber-800 p-1.5 rounded mt-1">
                                        <span class="material-symbols-outlined text-amber-600 mr-1" style="font-size: 0.75rem;">comment</span>
                                        ${observacionesTexto}
                                    </div>
                                    ` : ''}
                                </div>
                                <div class="flex gap-2 flex-shrink-0">
                                    <span class="estado-producto text-xs font-medium py-1 px-2 rounded-full inline-flex items-center ${estadoProducto === 'preparando' ? 'bg-blue-100 text-blue-800' : estadoProducto === 'listo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                        <span class="material-symbols-outlined mr-1" style="font-size: 0.75rem;">${estadoProducto === 'preparando' ? 'hourglass_top' : estadoProducto === 'listo' ? 'check_circle' : 'pending'}</span>
                                        ${estadoProducto.charAt(0).toUpperCase() + estadoProducto.slice(1)}
                                    </span>
                                </div>
                            </div>
                            <div class="flex gap-2 mt-2">
                                <button class="btn-estado btn-preparar flex-1 p-2 rounded ${estadoProducto === 'preparando' || estadoProducto === 'listo' ? 'bg-gray-100 text-gray-400 opacity-50 cursor-not-allowed' : 'bg-blue-100 text-blue-800 hover:bg-blue-200'}" 
                                        data-estado="preparando" 
                                        data-cod-pedido="${pedidoIdParaProducto}" 
                                        data-cod-producto="${productoId}" 
                                        data-num-mesa="${mesaIdParaProducto}"
                                        ${estadoProducto !== 'pendiente' ? 'disabled' : ''}
                                        ${!productoId ? 'title="Error: Faltan datos (codProducto)"' : ''}
                                        ${!productoId ? 'disabled' : ''}>
                                    <span class="material-symbols-outlined">cooking</span>
                                </button>
                                <button class="btn-estado btn-listo flex-1 p-2 rounded ${estadoProducto === 'listo' ? 'bg-gray-100 text-gray-400 opacity-50 cursor-not-allowed' : estadoProducto === 'preparando' ? 'bg-[#10B981] text-white hover:bg-green-600' : 'bg-gray-100 text-gray-400 cursor-not-allowed'}" 
                                        data-estado="listo" 
                                        data-cod-pedido="${pedidoIdParaProducto}" 
                                        data-cod-producto="${productoId}" 
                                        data-num-mesa="${mesaIdParaProducto}"
                                        ${estadoProducto !== 'preparando' ? 'disabled' : ''}
                                        ${!productoId ? 'title="Error: Faltan datos (codProducto)"' : ''}>
                                    <span class="material-symbols-outlined">check_circle</span>
                                </button>
                            </div>
                        </div>
                        `;
                });
            } else {
                productosHtml = '<p class="text-sm text-gray-500">No hay productos en este pedido</p>';
            }

            const fechaPedido = pedido.Fecha || pedido.fecha || new Date().toISOString();
            
           

            // Compruebo si el pedido tiene observaciones generales
            const tieneObservacionesGenerales = Boolean(
                (pedido.observaciones && pedido.observaciones.toString().trim() !== '') ||
                (pedido.Observaciones && pedido.Observaciones.toString().trim() !== '') ||
                (pedido.observacionesGenerales && pedido.observacionesGenerales.toString().trim() !== '') ||
                (pedido.obs && pedido.obs.toString().trim() !== '')
            );
            
            const observacionesGeneralesTexto = pedido.observacionesGenerales || pedido.observaciones || pedido.Observaciones || pedido.obs || '';
            
            html += `
                <div class="pedido-card bg-white rounded-lg shadow-md mb-4 border-l-4 border-${estadoPedido === 'preparando' ? 'yellow' : estadoPedido === 'listo' ? 'green' : 'blue'}-500" data-cod-pedido="${pedido.codPedido || pedido.cod}" data-num-mesa="${numMesa}">
                    <div class="p-3 flex justify-between items-center">
                        <div class="flex items-center">
                            <span class="material-symbols-outlined text-gray-500 mr-2">receipt_long</span>
                            <h3 class="text-base font-semibold">Pedido #${pedido.codPedido || pedido.cod}</h3>
                        </div>
                        <div class="estado-pedido px-2.5 py-0.5 ${estadoClase} text-xs font-medium rounded-full">
                            ${estadoPedido.charAt(0).toUpperCase() + estadoPedido.slice(1)}
                        </div>
                    </div>
                    
                    ${tieneObservacionesGenerales ? `
                    <div class="observacionGeneralPedido px-3 py-2 mt-2 mb-2 bg-amber-50 text-amber-800 text-sm rounded-md mx-3">
                        <div class="flex items-start">
                            <span class="material-symbols-outlined text-amber-600 mr-1">priority_high</span>
                            <span>${observacionesGeneralesTexto}</span>
                        </div>
                    </div>
                    ` : ''}
                    
                    <div class="productos-container px-3 pb-3">
                        ${productosHtml}
                    </div>
                    
                    <div class="px-3 pb-3 flex items-center justify-between">
                        <div class="flex items-center text-gray-500 text-xs">
                            <span class="material-symbols-outlined text-gray-400 mr-1" style="font-size: 16px;">schedule</span>
                            <span>${formatearFechaHora(fechaPedido)}</span>
                        </div>
                    </div>
                </div>`;
        });
        
        // Cierro la sección de la mesa
        html += `
                </div>
            </div>`;
    }
    
    // Actualizo el DOM
    contenedorPedidos.innerHTML = html;
    
    // Inicializo los botones de los pedidos renderizados
    inicializarBotones();
}

// Actualizo los contadores de pedidos y productos
function actualizarContadores() {
    requestAnimationFrame(() => {
        const totalPedidosEl = document.getElementById('totalPedidos');
        const totalProductosPendientesEl = document.getElementById('totalProductosPendientes');
        const totalProductosListosEl = document.getElementById('totalProductosListos');

        const numPedidos = document.querySelectorAll('.pedido-card').length;
        const numProductosPendientes = document.querySelectorAll('.producto[data-estado="pendiente"]').length + document.querySelectorAll('.producto[data-estado="preparando"]').length;
        const numProductosListos = document.querySelectorAll('.producto[data-estado="listo"]').length;

        if (totalPedidosEl) totalPedidosEl.textContent = numPedidos;
        if (totalProductosPendientesEl) totalProductosPendientesEl.textContent = numProductosPendientes;
        if (totalProductosListosEl) totalProductosListosEl.textContent = numProductosListos;
    });
}

function verificarYocultarMesaSiVaciaCocina(mesaContenedor) {
    if (mesaContenedor && mesaContenedor.querySelector('.pedido-card') === null) {
        mesaContenedor.remove();
    }
}

function formatearFechaHora(fechaISO) {
    const fecha = new Date(fechaISO);
    return fecha.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
}

function mostrarNotificacion(tipo, mensaje) {
    const contenedor = document.getElementById('notificaciones-container');
    if (!contenedor) return;

    const colores = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };

    const notificacion = document.createElement('div');
    notificacion.className = `text-white px-4 py-2 rounded-md shadow-lg mb-2 ${colores[tipo] || 'bg-gray-500'} transition-transform transform translate-y-full`;
    notificacion.textContent = mensaje;

    contenedor.appendChild(notificacion);

    // Animación de entrada
    setTimeout(() => {
        notificacion.classList.remove('translate-y-full');
    }, 100);

    // Oculto la notificación después de 5 segundos
    setTimeout(() => {
        notificacion.style.opacity = '0';
        notificacion.style.transform = 'translateY(-20px)';
        setTimeout(() => notificacion.remove(), 300);
    }, 5000);
}

function inicializarBotonesCompletarPedido() {
    document.querySelectorAll('.btnCompletado').forEach(boton => {
        boton.addEventListener('click', function() {
            const pedidoCard = this.closest('.pedido-card');
            if (!pedidoCard) return;
            // Lógica para marcar el pedido como completado y eliminarlo INMEDIATAMENTE
            const mesaContenedor = pedidoCard.closest('.mb-8');
            pedidoCard.remove();
            actualizarContadores();
            if (mesaContenedor) {
                verificarYocultarMesaSiVaciaCocina(mesaContenedor);
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    cargarPedidosCocina();
    iniciarWebSocket();
    
    // Actualizo contadores periódicamente como fallback
    setInterval(actualizarContadores, 30000); 
});