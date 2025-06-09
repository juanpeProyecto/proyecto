"use strict";

// Función para mostrar notificaciones al usuario
function mostrarNotificacion(tipo, mensaje) {
    // Crear el contenedor de notificaciones si no existe
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
    
    // Crear la notificación
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
    
    // Añadir la notificación al contenedor
    notificaciones.appendChild(notificacion);
    
    // Eliminar automáticamente después de 5 segundos
    setTimeout(() => {
        if (notificacion.parentNode) {
            notificacion.style.opacity = '0';
            notificacion.style.transition = 'opacity 0.5s';
            setTimeout(() => notificacion.remove(), 500);
        }
    }, 5000);
}

// Función para formatear fechas en un formato legible
function formatearFechaHora(fechaString) {
    try {
        const fecha = new Date(fechaString);
        if (isNaN(fecha.getTime())) {
            console.error('Fecha inválida:', fechaString);
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
        console.error('Error al formatear fecha:', error);
        return 'Fecha no disponible';
    }
}

// Mostrar el indicador de carga
function mostrarCarga() {
    const cargando = document.getElementById('cargando');
    if (cargando) {
        cargando.classList.remove('oculto');
    }
}

// Ocultar el indicador de carga
function ocultarCarga() {
    const cargando = document.getElementById('cargando');
    if (cargando) {
        setTimeout(() => {
            cargando.classList.add('oculto');
        }, 300); // Pequeño retraso para evitar parpadeo
    }
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando página de cocina...');
    
    // Mostrar carga al iniciar
    mostrarCarga();
    
    // Inicializar los botones de estado
    inicializarBotones();
    
    // Cargar pedidos pendientes al iniciar - Usamos nuestra propia versión
    // para evitar conflictos con gestionPedidos.js
    cargarPedidosCocina();
    
    // Configurar conexión WebSocket
    iniciarWebSocket();
    
    // Actualizar contadores
    actualizarContadores();
    
    // Ocultar carga cuando todo esté listo
    window.addEventListener('load', ocultarCarga);
    
    // Por si acaso, ocultar después de 3 segundos como máximo
    setTimeout(ocultarCarga, 3000);
});

// Función para actualizar el estado de un producto individual
function actualizarEstadoProducto(boton, estado, codPedido, codProducto, numMesa) {
    console.log(`Actualizando estado de producto ${codProducto} a ${estado}`);
    
    // Obtener el contenedor del producto y del pedido
    const elementoProducto = boton.closest('.producto');
    const elementoPedido = boton.closest('.pedido-card');
    
    if (!elementoProducto || !elementoPedido) {
        const error = new Error('No se pudo encontrar el contenedor del producto o pedido');
        console.error(error.message, boton);
        return Promise.reject(error);
    }
    
    // Obtener el estado actual del producto
    const estadoActual = elementoProducto.getAttribute('data-estado') || 'pendiente';
    
    // Si el estado no cambia, no hacemos nada
    if (estado === estadoActual) {
        console.log('El estado no ha cambiado, ignorando...');
        return Promise.resolve({ success: true, mensaje: 'El estado ya estaba actualizado' });
    }
    
    console.log(`Actualizando producto ${codProducto} del pedido ${codPedido} a estado: ${estado}`);
    
    // Guardar el HTML original del botón para restaurarlo en caso de error
    const botonHTML = boton.innerHTML;
    const botonClases = boton.className;
    
    // Mostrar indicador de carga en el botón
    boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    boton.disabled = true;
    
    // Actualizar la interfaz inmediatamente para mejor experiencia de usuario
    actualizarEstadoProductoUI(codProducto, estado);
    
    // Crear una promesa para manejar la actualización
    return new Promise((resolve, reject) => {
        // Función para restaurar el botón a su estado original
        const restaurarBoton = () => {
            boton.innerHTML = botonHTML;
            boton.className = botonClases;
            boton.disabled = false;
        };

        // Enviar la solicitud al servidor
        fetch('cocina.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'actualizar_estado_producto',
                codPedido: codPedido,
                codProducto: codProducto,
                estado: estado,
                area: 'cocina',
                codEmpleado: 1, // Debería venir de la sesión
                numMesa: numMesa || '0',
                estadoAnterior: estadoActual // Usar el estado actual como referencia
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
                throw new Error('No se recibió respuesta del servidor');
            }

            console.log('Respuesta del servidor:', data);
            
            // Verificar si el estado no cambió en el servidor
            if (data.mensaje && data.mensaje.includes('no se afectaron filas')) {
                console.log('El estado no cambió en el servidor, recargando datos...');
                // Recargamos los pedidos para sincronizar con el servidor
                return cargarPedidosCocina().then(() => {
                    restaurarBoton();
                    return resolve({ success: true, mensaje: 'Estado sincronizado con el servidor' });
                }).catch(error => {
                    console.error('Error al sincronizar estados:', error);
                    restaurarBoton();
                    return resolve({ success: true, mensaje: 'El estado ya estaba actualizado' });
                });
            }
            
            if (data.success) {
                console.log('Estado actualizado correctamente:', data);
                
                // Verificar primero si el pedido está completo (todos los productos listos)
                if (data.pedidoCompleto === 1 || data.estadoPedido === 'listo') {
                    console.log('Pedido completo, actualizando interfaz...');
                    // Actualizar la interfaz con los nuevos datos
                    actualizarEstadoProductoUI(codProducto, 'listo');
                    
                    // Si el pedido está listo, eliminarlo de la interfaz después de un breve retraso
                    setTimeout(() => {
                        if (elementoPedido && elementoPedido.parentNode) {
                            elementoPedido.remove();
                            actualizarContadores();
                            mostrarNotificacion('success', '¡Pedido completado! Se ha eliminado de la lista.');
                        }
                    }, 500);
                } else {
                    // Si el pedido no está completo, actualizar la interfaz normalmente
                    actualizarEstadoProductoUI(codProducto, estado);
                    
                    // Actualizar la UI del estado del pedido
                    if (elementoPedido) {
                        const estadoPedidoElement = elementoPedido.querySelector('.estado-pedido');
                        
                        if (data.estadoPedido && estadoPedidoElement) {
                            estadoPedidoElement.textContent = data.estadoPedido.charAt(0).toUpperCase() + data.estadoPedido.slice(1);
                            estadoPedidoElement.className = 'px-2 py-1 text-xs font-medium rounded-full ';
                            
                            if (data.estadoPedido === 'preparando') {
                                estadoPedidoElement.classList.add('bg-yellow-100', 'text-yellow-800');
                                elementoPedido.classList.add('border-yellow-500');
                                elementoPedido.classList.remove('border-blue-500');
                            } else {
                                estadoPedidoElement.classList.add('bg-blue-100', 'text-blue-800');
                                elementoPedido.classList.add('border-blue-500');
                                elementoPedido.classList.remove('border-yellow-500', 'border-green-500');
                            }
                        }
                    }
                }
                
                // Actualizar contadores
                actualizarContadores();
                
                // Mostrar notificación de éxito
                mostrarNotificacion('success', 'Estado actualizado correctamente');
                
                // Resolver la promesa con los datos de la respuesta
                resolve(data);
            } else {
                // Si hay un mensaje específico, usarlo, de lo contrario mensaje genérico
                const errorMsg = data.mensaje || 'Error desconocido al actualizar el estado';
                throw new Error(errorMsg);
            }
        })
        .catch(error => {
            console.error('Error al actualizar el estado del producto:', error);
            
            // Restaurar el botón a su estado original
            restaurarBoton();
            
            // Mostrar notificación de error
            mostrarNotificacion('error', 'Error al actualizar el estado del producto: ' + (error.message || 'Error desconocido'));
            
            // Rechazar la promesa con el error
            reject(error);
        });
    });
}

// Inicializar los botones de cambio de estado de todos los productos
function inicializarBotones() {
    console.log('Inicializando botones...');
    // Inicializar botones de completar pedido
    inicializarBotonesCompletarPedido();
    
    // Seleccionar todos los botones de estado
    const botones = document.querySelectorAll('.btn-estado');
    
    // Eliminar manejadores de eventos anteriores para evitar duplicados
    botones.forEach(boton => {
        const newBoton = boton.cloneNode(true);
        boton.parentNode.replaceChild(newBoton, boton);
    });
    
    // Volver a seleccionar los botones después del clonado
    const botonesActualizados = document.querySelectorAll('.btn-estado');
    
    botonesActualizados.forEach(boton => {
        // Función para obtener atributos con diferentes formatos
        const getDataAttribute = (element, keys) => {
            if (!element) return null;
            
            for (const key of keys) {
                try {
                    // Probar con prefijos de datos
                    const dataKey = key.startsWith('data-') ? key : `data-${key}`;
                    let value = element.getAttribute(dataKey);
                    if (value !== null && value !== '' && value !== 'undefined') {
                        console.log(`Atributo encontrado: ${dataKey} = ${value}`);
                        return value;
                    }
                    
                    // Probar sin prefijo
                    value = element.getAttribute(key);
                    if (value !== null && value !== '' && value !== 'undefined') {
                        console.log(`Atributo encontrado: ${key} = ${value}`);
                        return value;
                    }
                    
                    // Probar con camelCase
                    const camelCaseKey = key.replace(/-([a-z])/g, (_, char) => char.toUpperCase());
                    value = element.getAttribute(camelCaseKey);
                    if (value !== null && value !== '' && value !== 'undefined') {
                        console.log(`Atributo encontrado: ${camelCaseKey} = ${value}`);
                        return value;
                    }
                } catch (error) {
                    console.error(`Error al obtener atributo ${key}:`, error);
                }
            }
            console.error('No se pudo encontrar ningún atributo válido para las claves:', keys, 'en el elemento:', element);
            return null;
        };
        
        // Obtener los datos del botón con múltiples formatos posibles
        const estado = getDataAttribute(boton, ['data-estado', 'estado']);
        let codPedido = getDataAttribute(boton, ['data-cod-pedido', 'codPedido', 'pedido-id', 'data-pedido-id']);
        let codProducto = getDataAttribute(boton, ['data-cod-producto', 'codProducto', 'producto-id', 'data-producto-id']);
        let numMesa = getDataAttribute(boton, ['data-num-mesa', 'numMesa', 'mesa', 'data-mesa']);
        
        console.log('Datos iniciales del botón:', { 
            estado, 
            codPedido, 
            codProducto, 
            numMesa,
            html: boton.outerHTML 
        });
        
        // Intentar obtener los datos del DOM si no están en el botón o son inválidos
        const producto = boton.closest('.producto');
        if (producto) {
            console.log('Producto encontrado:', producto);
            
            // Obtener el ID del producto del contenedor del producto
            const productoId = getDataAttribute(producto, ['data-cod-producto', 'codProducto', 'producto-id', 'data-producto-id']);
            console.log('ID de producto del contenedor:', productoId);
            
            // Obtener el ID del pedido del contenedor del pedido
            const pedidoElement = producto.closest('.pedido-card');
            console.log('Elemento de pedido encontrado:', pedidoElement);
            
            const pedidoId = pedidoElement ? getDataAttribute(pedidoElement, ['data-cod-pedido', 'codPedido', 'pedido-id', 'data-pedido-id']) : null;
            console.log('ID de pedido del contenedor:', pedidoId);
            
            // Obtener número de mesa del contenedor del pedido si no está en el botón
            if (pedidoElement && (!numMesa || numMesa === '0' || numMesa === 'undefined')) {
                numMesa = getDataAttribute(pedidoElement, ['data-num-mesa', 'numMesa', 'mesa', 'data-mesa']) ||
                         (pedidoElement.closest('.mb-8')?.querySelector('h2')?.textContent?.match(/Mesa\s*(\d+)/i)?.[1]) ||
                         '0';
                console.log('Número de mesa obtenido del DOM:', numMesa);
            }
            
            // Actualizar valores si se encontraron en el DOM
            if (pedidoId && (!codPedido || codPedido === '0' || codPedido === 'undefined')) {
                codPedido = pedidoId;
                boton.setAttribute('data-cod-pedido', codPedido);
                console.log('Actualizado codPedido desde el DOM:', codPedido);
            }
            
            if (productoId && (!codProducto || codProducto === '0' || codProducto === 'undefined')) {
                codProducto = productoId;
                boton.setAttribute('data-cod-producto', codProducto);
                console.log('Actualizado codProducto desde el DOM:', codProducto);
            }
            
            if (!numMesa || numMesa === '0' || numMesa === 'undefined') {
                numMesa = '0';
                boton.setAttribute('data-num-mesa', numMesa);
                console.log('Establecido numMesa por defecto:', numMesa);
            }
        }
        
        console.log('Inicializando botón con datos:', { 
            estado, 
            codPedido, 
            codProducto, 
            numMesa,
            html: boton.outerHTML 
        });
        
        // Verificar que los datos requeridos estén presentes
        const datosFaltantes = [];
        if (!codPedido || codPedido === '0' || codPedido === 'undefined') datosFaltantes.push('codPedido');
        if (!codProducto || codProducto === '0' || codProducto === 'undefined') datosFaltantes.push('codProducto');
        
        if (datosFaltantes.length > 0) {
            const errorInfo = { 
                html: boton.outerHTML,
                dataAttributes: {
                    'data-estado': boton.getAttribute('data-estado'),
                    'data-cod-pedido': boton.getAttribute('data-cod-pedido'),
                    'data-cod-producto': boton.getAttribute('data-cod-producto'),
                    'data-num-mesa': boton.getAttribute('data-num-mesa')
                },
                valoresObtenidos: {
                    codPedido,
                    codProducto,
                    numMesa: numMesa || 'No definido',
                    estado: estado || 'No definido'
                },
                datosFaltantes,
                parentElement: boton.parentElement ? {
                    tagName: boton.parentElement.tagName,
                    className: boton.parentElement.className,
                    outerHTML: boton.parentElement.outerHTML
                } : null,
                closestProducto: producto ? {
                    tagName: producto.tagName,
                    className: producto.className,
                    attributes: Array.from(producto.attributes).map(attr => ({
                        name: attr.name,
                        value: attr.value
                    }))
                } : null
            };
            
            console.error('Datos faltantes o inválidos en el botón:', errorInfo);
            
            // Deshabilitar el botón si faltan datos críticos
            boton.disabled = true;
            boton.classList.add('opacity-50', 'cursor-not-allowed');
            boton.title = `Error: Faltan datos (${datosFaltantes.join(', ')})`;
            
            // Mostrar un mensaje de error en la consola con más detalles
            console.error('No se pudo inicializar el botón. Datos faltantes:', {
                boton: boton.outerHTML,
                datosFaltantes,
                valoresActuales: errorInfo.valoresObtenidos
            });
            
            return; // No continuar con la inicialización de este botón
        }
        
        // Validar que los datos requeridos estén presentes
        if (!codPedido || !codProducto) {
            console.error('No se pudieron obtener los datos necesarios para el botón');
            boton.disabled = true;
            boton.title = 'Error: Faltan datos del pedido o producto';
            boton.classList.add('opacity-50', 'cursor-not-allowed');
            return;
        }
        
        // Configurar el evento click
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Obtener los datos del botón usando la función getDataAttribute para mayor robustez
            const estado = getDataAttribute(this, ['data-estado', 'estado']);
            const codPedido = getDataAttribute(this, ['data-cod-pedido', 'codPedido', 'pedido-id', 'data-pedido-id']);
            const codProducto = getDataAttribute(this, ['data-cod-producto', 'codProducto', 'producto-id', 'data-producto-id']);
            const numMesa = getDataAttribute(this, ['data-num-mesa', 'numMesa', 'mesa', 'data-mesa']) || '0';
            
            console.log('Botón clickeado:', { 
                estado, 
                codPedido, 
                codProducto, 
                numMesa,
                html: this.outerHTML 
            });
            
            // Validar que los datos requeridos estén presentes
            if (!codPedido || !codProducto) {
                console.error('Faltan datos requeridos para actualizar el estado', {
                    codPedido,
                    codProducto,
                    numMesa,
                    estado,
                    html: this.outerHTML,
                    dataAttributes: {
                        'data-estado': this.getAttribute('data-estado'),
                        'data-cod-pedido': this.getAttribute('data-cod-pedido'),
                        'data-cod-producto': this.getAttribute('data-cod-producto'),
                        'data-num-mesa': this.getAttribute('data-num-mesa')
                    }
                });
                
                // Mostrar un mensaje de error en la interfaz
                const errorMsg = document.createElement('div');
                errorMsg.className = 'text-red-600 text-sm mt-1';
                errorMsg.textContent = 'Error: Faltan datos del pedido o producto';
                this.parentNode.appendChild(errorMsg);
                
                // Eliminar el mensaje después de 3 segundos
                setTimeout(() => {
                    if (errorMsg.parentNode) {
                        errorMsg.parentNode.removeChild(errorMsg);
                    }
                }, 3000);
                
                return;
            }
            
            // Deshabilitar temporalmente el botón para evitar múltiples clics
            this.disabled = true;
            this.classList.add('opacity-50', 'cursor-wait');
            
            // Llamar a la función para actualizar el estado del producto
            actualizarEstadoProducto(this, estado, codPedido, codProducto, numMesa)
                .finally(() => {
                    // Re-habilitar el botón después de un breve retraso
                    setTimeout(() => {
                        this.disabled = false;
                        this.classList.remove('opacity-50', 'cursor-wait');
                    }, 1000);
                });
        });
        
        // Marcar el botón como inicializado
        boton.setAttribute('data-inicializado', 'true');
        console.log('Botón inicializado correctamente:', { 
            estado, 
            codPedido, 
            codProducto, 
            numMesa 
        });
        
        // Actualizar estado visual del botón
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

// Cargar pedidos pendientes al iniciar (versión específica para cocina)
function cargarPedidosCocina() {
    console.log('Cargando pedidos pendientes...');
    mostrarCarga();
    
    // Limpiar el contenedor de pedidos
    const contenedorPedidos = document.getElementById('contenedorPedidos');
    if (!contenedorPedidos) {
        console.error('No se encontró el contenedor de pedidos');
        return;
    }
    
    // Mostrar indicador de carga
    contenedorPedidos.innerHTML = `
        <div class="text-center py-8">
            <div class="spinner mx-auto"></div>
            <p class="mt-4 text-gray-600">Cargando pedidos...</p>
        </div>`;
    
    // Obtener los pedidos pendientes del servidor
    fetch('cocina.php?action=obtenerPedidosPendientes&area=cocina')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Pedidos pendientes cargados:', data);
            
            if (!data.success || !data.pedidos) {
                throw new Error('Formato de respuesta inválido');
            }
            
            // Depuración: Ver la estructura de los productos del primer pedido
            if (data.pedidos.length > 0 && data.pedidos[0].productos) {
                console.log('Estructura del primer producto del primer pedido:', data.pedidos[0].productos[0]);
            }
            
            // Renderizar los pedidos
            renderizarPedidos(data.pedidos);
            actualizarContadores();
        })
        .catch(error => {
            console.error('Error al cargar pedidos pendientes:', error);
            contenedorPedidos.innerHTML = `
                <div class="text-center py-8">
                    <p class="text-red-600">Error al cargar los pedidos. Por favor, recarga la página.</p>
                    <button onclick="cargarPedidosCocina()" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Reintentar
                    </button>
                </div>`;
        })
        .finally(() => {
            ocultarCarga();
        });
}

// Iniciar conexión WebSocket
function iniciarWebSocket() {
    if ('WebSocket' in window) {
        console.log('Iniciando conexión WebSocket...');
        const ws = new WebSocket('ws://localhost:8080');
        
        // Cuando se abre la conexión, nos registramos como "cocina"
        ws.onopen = function() {
            console.log('Conexión WebSocket establecida');
            // Registramos este cliente como de tipo cocina
            ws.send(JSON.stringify({
                tipoCliente: 'cocina'
            }));
        };
        
        // Manejar errores
        ws.onerror = function(error) {
            console.error('Error en WebSocket:', error);
        };
        
        // Manejar cierre de conexión
        ws.onclose = function() {
            console.log('Conexión WebSocket cerrada');
        };
        
        // Manejar mensajes
        ws.onmessage = function(event) {
            console.log('Mensaje WebSocket recibido:', event.data);
            const mensaje = JSON.parse(event.data);
            
            // Si es un nuevo pedido o actualización de estado, recargamos los pedidos
            if (mensaje.tipo === 'nuevoPedido' || mensaje.tipo === 'actualizacionEstado') {
                console.log('Nuevo pedido o actualización recibida, recargando lista de pedidos...');
                cargarPedidosCocina();
            }
            
            // Si es una actualización de estado de producto, actualizamos solo ese producto
            if (mensaje.tipo === 'actualizacionEstadoProducto') {
                console.log('Actualización de estado de producto recibida:', mensaje);
                actualizarEstadoProductoUI(mensaje.codProducto, mensaje.estado);
            }
        };
    } else {
        console.error('WebSockets no soportados en este navegador');
    }
}

// Función para actualizar la interfaz de usuario cuando cambia el estado de un producto
function actualizarEstadoProductoUI(codProducto, estado) {
    // Usar requestAnimationFrame para agrupar las actualizaciones del DOM
    requestAnimationFrame(() => {
        console.log(`Actualizando UI para producto ${codProducto} a estado ${estado}`);
        
        // Buscar el producto específico usando el ID del producto
        const elementoProducto = document.querySelector(`.producto[data-cod-producto="${codProducto}"]`);
        
        if (!elementoProducto) {
            console.log(`No se encontró el producto con código ${codProducto} en la interfaz`);
            return;
        }
        
        // Actualizar el atributo data-estado
        elementoProducto.setAttribute('data-estado', estado);
        
        // Actualizar las clases del contenedor del producto
        elementoProducto.classList.remove('bg-yellow-50', 'bg-green-50', 'bg-gray-50', 'border-yellow-200', 'border-green-200', 'border-gray-200');
        if (estado === 'preparando') {
            elementoProducto.classList.add('bg-yellow-50', 'border-yellow-200');
        } else if (estado === 'listo') {
            elementoProducto.classList.add('bg-green-50', 'border-green-200');
        } else {
            elementoProducto.classList.add('bg-gray-50', 'border-gray-200');
        }
        
        // Actualizar el texto del estado
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
        
        // Configurar actualización del estado
        const estadoMostrar = estado.charAt(0).toUpperCase() + estado.slice(1);
        estadoElement.textContent = `Estado: ${estadoMostrar}`;
        
        // Actualizar clases de estilo según el estado
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
        
        // Actualizar botones de acción
        const btnPreparar = elementoProducto.querySelector('.btn-preparar');
        const btnListo = elementoProducto.querySelector('.btn-listo');
        
        // Actualizar botón de preparar
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
        
        // Actualizar botón de listo
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
        
        // Verificar si todos los productos del pedido están listos
        const tarjetaPedido = elementoProducto.closest('.pedido-card');
        if (tarjetaPedido) {
            const productosPedido = tarjetaPedido.querySelectorAll('.producto');
            const productosListos = Array.from(productosPedido).every(
                prod => prod.getAttribute('data-estado') === 'listo'
            );
            
            actualizarEstadoPedido(tarjetaPedido, productosListos);
        }
        
        // Actualizar contadores
        actualizarContadores();
    });
}

// Función auxiliar para actualizar el estado visual del pedido
function actualizarEstadoPedido(tarjetaPedido, productosListos) {
    const btnCompletar = tarjetaPedido.querySelector('.btnCompletado');
    let estadoPedido = tarjetaPedido.querySelector('.estado-pedido');
    
    if (productosListos) {
        // Habilitar botón de completar si todos los productos están listos
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
        
        // Actualizar borde de la tarjeta
        tarjetaPedido.classList.remove('border-yellow-500', 'border-red-500');
        tarjetaPedido.classList.add('border-green-500');
    } else {
        // Deshabilitar botón de completar si no todos los productos están listos
        if (btnCompletar) {
            btnCompletar.disabled = true;
            btnCompletar.classList.add('opacity-50', 'cursor-not-allowed');
            btnCompletar.classList.remove('bg-green-500', 'hover:bg-green-600', 'text-white');
        }
        
        // Actualizar estado visual del pedido a "En preparación"
        if (estadoPedido) {
            estadoPedido.textContent = 'En preparación';
            estadoPedido.className = 'estado-pedido text-xs font-medium px-2 py-1 rounded-full bg-yellow-100 text-yellow-800';
        }
        
        // Actualizar borde de la tarjeta
        tarjetaPedido.classList.remove('border-green-500', 'border-red-500');
        tarjetaPedido.classList.add('border-yellow-500');
    }
}

// Función para renderizar la lista de pedidos
function renderizarPedidos(pedidos) {
    const contenedorPedidos = document.getElementById('contenedorPedidos');
    if (!contenedorPedidos) return;
    
    // Si no hay pedidos, mostrar mensaje
    if (!pedidos || pedidos.length === 0) {
        contenedorPedidos.innerHTML = `
            <div class="text-center py-8">
                <p class="text-gray-600">No hay pedidos pendientes en este momento.</p>
            </div>`;
        return;
    }
    
    // Agrupar pedidos por mesa
    const pedidosPorMesa = {};
    pedidos.forEach(pedido => {
        const mesa = pedido.numMesa || 'Sin mesa';
        if (!pedidosPorMesa[mesa]) {
            pedidosPorMesa[mesa] = [];
        }
        pedidosPorMesa[mesa].push(pedido);
    });
    
    // Construir el HTML de los pedidos
    let html = '';
    
    // Recorrer cada mesa
    for (const [numMesa, pedidosMesa] of Object.entries(pedidosPorMesa)) {
        html += `
            <div class="mb-8">
                <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">Mesa ${numMesa}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">`;
        
        // Recorrer los pedidos de esta mesa
        pedidosMesa.forEach(pedido => {
            const estadoPedido = pedido.estado || 'pendiente';
            const estadoClase = estadoPedido === 'preparando' ? 'bg-yellow-100 text-yellow-800' : 
                              estadoPedido === 'listo' ? 'bg-green-100 text-green-800' : 
                              'bg-blue-100 text-blue-800';
            
            html += `
                    <div class="pedido-card bg-white rounded-lg shadow-md p-4 border-l-4 ${estadoPedido === 'preparando' ? 'border-yellow-500' : 'border-blue-500'}" 
                         data-cod-pedido="${pedido.codPedido}">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-lg font-semibold text-gray-800">Pedido #${pedido.codPedido}</h3>
                            <span class="px-2 py-1 text-xs font-medium rounded-full ${estadoClase}">
                                ${estadoPedido.charAt(0).toUpperCase() + estadoPedido.slice(1)}
                            </span>
                        </div>
                        <div class="productos-container space-y-2">`;
            
            // Agregar productos del pedido
            if (pedido.productos && pedido.productos.length > 0) {
                pedido.productos.forEach(producto => {
                    // Obtener el estado del producto, verificando múltiples posibles propiedades
                    let estadoProducto = 'pendiente';
                    if (producto.estado) {
                        estadoProducto = producto.estado.toLowerCase();
                    } else if (producto.estadoProducto) {
                        estadoProducto = producto.estadoProducto.toLowerCase();
                    } else if (producto.estado_producto) {
                        estadoProducto = producto.estado_producto.toLowerCase();
                    }
                    
                    // Depuración: Mostrar información del producto
                    console.log(`Producto: ${producto.nombre || 'sin nombre'}, Estado: ${estadoProducto}`, producto);
                    
                    const productoClase = estadoProducto === 'preparando' ? 'bg-yellow-50 border-l-4 border-yellow-300' : 
                                        estadoProducto === 'listo' ? 'bg-green-50 border-l-4 border-green-300' : 
                                        'bg-gray-50 border-l-4 border-gray-200';
                    
                    // Asegurarse de que los IDs de pedido y producto sean válidos
                    const pedidoId = pedido.codPedido || pedido.cod || '0';
                    const productoId = producto.codProducto || producto.cod || '0';
                    
                    // Asegurarse de que numMesa sea un valor válido
                    const mesaId = numMesa && numMesa !== 'undefined' ? numMesa : '0';
                    
                    html += `
                            <div class="producto ${productoClase} p-3 rounded-r border border-gray-100 transition-all duration-200" 
                                 data-cod-producto="${productoId}"
                                 data-producto-id="${productoId}"
                                 data-estado="${estadoProducto}"
                                 title="Estado: ${estadoProducto.charAt(0).toUpperCase() + estadoProducto.slice(1)}">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-medium text-gray-800">${producto.nombre || 'Producto sin nombre'}</div>
                                        <div class="text-sm text-gray-500">Cant: ${producto.cantidad || 1}</div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <div class="estado-producto text-xs font-medium px-2 py-1 rounded ${
                                            estadoProducto === 'preparando' ? 'bg-yellow-100 text-yellow-800' : 
                                            estadoProducto === 'listo' ? 'bg-green-100 text-green-800' : 
                                            'bg-gray-100 text-gray-800'
                                        }" data-estado-actual>
                                            ${estadoProducto.charAt(0).toUpperCase() + estadoProducto.slice(1)}
                                        </div>
                                        <div class="flex space-x-1">
                                            <button class="btn-estado btn-preparar px-2 py-1 text-xs rounded ${
                                                estadoProducto === 'preparando' ? 'bg-yellow-100 text-yellow-800 opacity-50 cursor-not-allowed' : 
                                                'bg-gray-100 text-gray-800 hover:bg-gray-200'
                                            }" 
                                                    data-estado="preparando" 
                                                    data-cod-pedido="${pedidoId}" 
                                                    data-pedido-id="${pedidoId}" 
                                                    data-cod-producto="${productoId}" 
                                                    data-producto-id="${productoId}" 
                                                    data-num-mesa="${mesaId}" 
                                                    data-mesa="${mesaId}"
                                                    ${estadoProducto !== 'pendiente' ? 'disabled' : ''}>
                                                <span class="material-symbols-outlined" style="font-size: 1rem;">cooking</span>
                                            </button>
                                            <button class="btn-estado btn-listo px-2 py-1 text-xs rounded ${
                                                estadoProducto === 'listo' ? 'bg-green-100 text-green-800 opacity-50 cursor-not-allowed' : 
                                                estadoProducto === 'preparando' ? 'bg-green-100 text-green-800 hover:bg-green-200' : 
                                                'bg-gray-100 text-gray-400 cursor-not-allowed'
                                            }" 
                                                    data-estado="listo" 
                                                    data-cod-pedido="${pedidoId}" 
                                                    data-pedido-id="${pedidoId}" 
                                                    data-cod-producto="${productoId}" 
                                                    data-producto-id="${productoId}" 
                                                    data-num-mesa="${mesaId}" 
                                                    data-mesa="${mesaId}"
                                                    ${estadoProducto !== 'preparando' ? 'disabled' : ''}>
                                                <span class="material-symbols-outlined" style="font-size: 1rem;">check_circle</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>`;
                });
            } else {
                html += '<p class="text-sm text-gray-500">No hay productos en este pedido</p>';
            }
            
            // Cerrar el pedido
            const codigoPedido = pedido.codPedido || pedido.cod || 'N/A';
            const fechaPedido = pedido.Fecha || pedido.fecha || new Date().toISOString();
            
            html += `
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-100 flex justify-between items-center">
                            <span class="text-xs font-medium text-gray-600">Pedido #${codigoPedido}</span>
                            <span class="text-xs text-gray-500">${formatearFechaHora(fechaPedido)}</span>
                        </div>
                    </div>`;
        });
        
        // Cerrar la sección de la mesa
        html += `
                </div>
            </div>`;
    }
    
    // Actualizar el DOM
    contenedorPedidos.innerHTML = html;
    
    // Inicializar los botones de los pedidos renderizados
    inicializarBotones();
}

// Actualizar los contadores de pedidos y productos
function actualizarContadores() {
    try {
        // Verificar si existen los elementos antes de actualizar
        const totalPedidosEl = document.getElementById('totalPedidos');
        const totalProductosPendientesEl = document.getElementById('totalProductosPendientes');
        const totalProductosListosEl = document.getElementById('totalProductosListos');

        // Contar pedidos
        const pedidos = document.querySelectorAll('.pedido-card');
        if (totalPedidosEl) {
            totalPedidosEl.textContent = pedidos.length;
        }
        
        // Contar productos pendientes
        const productosPendientes = document.querySelectorAll('.producto').length;
        if (totalProductosPendientesEl) {
            totalProductosPendientesEl.textContent = productosPendientes;
        }
        
        // Contar productos listos - esta función puede que no se necesite en esta página
        const productosListos = document.querySelectorAll('.producto .btn-listo.disabled').length;
        if (totalProductosListosEl) {
            totalProductosListosEl.textContent = productosListos;
        }

        console.log(`Estadísticas: ${pedidos.length} pedidos, ${productosPendientes} productos pendientes`);
    } catch (error) {
        console.error('Error al actualizar contadores:', error);
    }
}

// Inicializar botones de completar pedido
function inicializarBotonesCompletarPedido() {
    console.log('Inicializando botones de completar pedido...');
    
    document.querySelectorAll('.btnCompletado').forEach(boton => {
        boton.addEventListener('click', function() {
            const pedido = this.closest('.pedido-card');
            if (!pedido) {
                console.error('No se encontró el elemento del pedido');
                return;
            }
            const codPedido = pedido.dataset.codPedido;
            console.log('Completando pedido:', codPedido);
            
            fetch('cocina.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    codPedido: codPedido,
                    estado: 'completado',
                    area: 'cocina'
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Pedido completado:', data);
                // Animar y quitar el pedido
                pedido.classList.add('bg-green-50');
                setTimeout(() => pedido.remove(), 1000);
            })
            .catch(error => console.error('Error:', error));
        });
    });
}
