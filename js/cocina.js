"use strict";

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
        console.error('No se pudo encontrar el contenedor del producto o pedido', boton);
        return;
    }
    
    // Mostrar estado en consola
    console.log('Actualizando estado del producto:', {codPedido, codProducto, estado, numMesa});
    
    // Actualizar el atributo data-estado del producto
    elementoProducto.setAttribute('data-estado', estado);
    
    // Actualizar la clase del producto según el estado
    elementoProducto.className = 'producto p-3 rounded border ';
    if (estado === 'preparando') {
        elementoProducto.classList.add('bg-yellow-50', 'border-yellow-200');
    } else if (estado === 'listo') {
        elementoProducto.classList.add('bg-green-50', 'border-green-200');
    } else {
        elementoProducto.classList.add('bg-gray-50', 'border-gray-200');
    }
    
    // Actualizar el botón que se hizo clic
    boton.disabled = true;
    boton.classList.remove('bg-gray-100', 'text-gray-800');
    
    if (estado === 'preparando') {
        boton.classList.add('bg-yellow-500', 'text-white');
    } else if (estado === 'listo') {
        boton.classList.add('bg-green-500', 'text-white');
    }
    
    // Guardar el HTML original del botón para restaurar en caso de error
    const htmlOriginalBoton = boton.outerHTML;

    // Preparar datos para enviar al servidor
    const datos = {
        action: 'actualizar_estado_producto',
        codPedido: codPedido,
        codProducto: codProducto,
        estado: estado,
        area: 'cocina',
        codEmpleado: 1, // Debería venir de la sesión
        numMesa: numMesa
    };
    
    console.log('Enviando actualización de estado:', datos);
    
    // Mostrar indicador de carga
    mostrarCarga();
    
    // Realizar la petición AJAX
    fetch('cocina.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(datos)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Respuesta del servidor:', data);
        
        if (data.success) {
            // Actualizar el estado visual del producto
            const estadoProducto = data.estadoProducto || estado;
            
            // Actualizar el atributo data-estado del producto
            elementoProducto.setAttribute('data-estado', estadoProducto);
            
            // Actualizar la clase del producto según el estado
            elementoProducto.className = 'producto p-3 rounded border ';
            if (estadoProducto === 'preparando') {
                elementoProducto.classList.add('bg-yellow-50', 'border-yellow-200');
            } else if (estadoProducto === 'listo') {
                elementoProducto.classList.add('bg-green-50', 'border-green-200');
                
                // Si el producto está listo, deshabilitar el botón de preparar
                const btnPreparar = elementoProducto.querySelector('.btn-preparar');
                if (btnPreparar) {
                    btnPreparar.disabled = true;
                    btnPreparar.classList.add('opacity-50', 'cursor-not-allowed');
                }
            } else {
                elementoProducto.classList.add('bg-gray-50', 'border-gray-200');
            }
            
            // Actualizar el estado del pedido si es necesario
            if (data.estadoPedido && elementoPedido) {
                const estadoPedidoElement = elementoPedido.querySelector('.estado-pedido');
                if (estadoPedidoElement) {
                    estadoPedidoElement.textContent = data.estadoPedido.charAt(0).toUpperCase() + data.estadoPedido.slice(1);
                    estadoPedidoElement.className = 'px-2 py-1 text-xs font-medium rounded-full ';
                    
                    if (data.estadoPedido === 'preparando') {
                        estadoPedidoElement.classList.add('bg-yellow-100', 'text-yellow-800');
                        elementoPedido.classList.add('border-yellow-500');
                        elementoPedido.classList.remove('border-blue-500');
                    } else if (data.estadoPedido === 'listo') {
                        estadoPedidoElement.classList.add('bg-green-100', 'text-green-800');
                        elementoPedido.classList.add('border-green-500');
                        elementoPedido.classList.remove('border-blue-500', 'border-yellow-500');
                    } else {
                        estadoPedidoElement.classList.add('bg-blue-100', 'text-blue-800');
                        elementoPedido.classList.add('border-blue-500');
                        elementoPedido.classList.remove('border-yellow-500', 'border-green-500');
                    }
                }
            }
            
            // Actualizar contadores
            actualizarContadores();
            
            // Si el pedido está completo, recargar la lista después de un breve retraso
            if (data.pedidoCompleto) {
                setTimeout(() => {
                    cargarPedidosCocina();
                }, 1000);
            }
        } else {
            throw new Error(data.mensaje || 'Error desconocido del servidor');
        }
    })
    .catch(error => {
        console.error('Error al actualizar estado:', error);
        
        // Mostrar mensaje de error
        alert(`Error al actualizar el estado: ${error.message}`);
        
        // Restaurar el botón a su estado original
        if (boton) {
            boton.outerHTML = htmlOriginalBoton;
        }
        
        // Volver a inicializar los botones
        inicializarBotones();
    })
    .finally(() => {
        // Ocultar indicador de carga
        ocultarCarga();
    });
}

// Inicializar los botones de cambio de estado de todos los productos
function inicializarBotones() {
    console.log('Inicializando botones de estado...');
    
    // Inicializar botones de completar pedido
    inicializarBotonesCompletarPedido();
    
    // Seleccionar todos los botones de estado
    const botones = document.querySelectorAll('.btn-estado');
    
    botones.forEach(boton => {
        // Verificar si el botón ya tiene un manejador de eventos
        if (boton.hasAttribute('data-inicializado')) {
            console.log('Botón ya inicializado, omitiendo...', boton);
            return;
        }
        
        // Función para obtener atributos con diferentes formatos
        const getDataAttribute = (element, keys) => {
            for (const key of keys) {
                const value = element.getAttribute(key);
                if (value) return value;
                
                // Probar con prefijos de datos
                const dataKey = key.startsWith('data-') ? key : `data-${key}`;
                const dataValue = element.getAttribute(dataKey);
                if (dataValue) return dataValue;
                
                // Probar con camelCase
                const camelCaseKey = key.replace(/-(.)/g, (_, char) => char.toUpperCase());
                const camelCaseValue = element.getAttribute(camelCaseKey);
                if (camelCaseValue) return camelCaseValue;
            }
            return null;
        };
        
        // Obtener los datos del botón con múltiples formatos posibles
        const estado = getDataAttribute(boton, ['data-estado', 'estado']);
        let codPedido = getDataAttribute(boton, ['data-cod-pedido', 'codPedido', 'pedido-id', 'data-pedido-id']);
        let codProducto = getDataAttribute(boton, ['data-cod-producto', 'codProducto', 'producto-id', 'data-producto-id']);
        let numMesa = getDataAttribute(boton, ['data-num-mesa', 'numMesa', 'mesa', 'data-mesa']);
        
        console.log('Datos iniciales del botón:', { estado, codPedido, codProducto, numMesa });
        
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
            if (pedidoElement && !numMesa) {
                numMesa = pedidoElement.getAttribute('data-num-mesa') || 
                          pedidoElement.closest('.mb-8')?.querySelector('h2')?.textContent?.match(/Mesa\s*(\d+)/i)?.[1] ||
                          '0';
            }
            
            // Actualizar valores si se encontraron en el DOM
            if (pedidoId && (!codPedido || codPedido === '0')) {
                codPedido = pedidoId;
                boton.setAttribute('data-cod-pedido', codPedido);
            }
            
            if (productoId && (!codProducto || codProducto === '0' || codProducto === 'undefined')) {
                codProducto = productoId;
                boton.setAttribute('data-cod-producto', codProducto);
            }
            
            if (!numMesa) {
                numMesa = '0';
                boton.setAttribute('data-num-mesa', numMesa);
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
        if (!codPedido || codPedido === '0' || !codProducto || codProducto === '0' || codProducto === 'undefined') {
            console.error('Datos faltantes o inválidos en el botón:', { 
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
                    numMesa
                },
                parentElement: boton.parentElement ? {
                    tagName: boton.parentElement.tagName,
                    className: boton.parentElement.className,
                    dataset: Object.fromEntries(
                        Object.entries(boton.parentElement.dataset).map(([key, value]) => [key, value])
                    )
                } : null,
                closestProducto: producto ? {
                    attributes: Array.from(producto.attributes).map(attr => ({
                        name: attr.name,
                        value: attr.value
                    }))
                } : null
            });
            
            // Mostrar más información en la consola
            console.group('Información adicional del botón con error');
            console.log('Elemento botón:', boton);
            console.log('Padre del botón:', boton.parentElement);
            console.log('Producto más cercano:', producto);
            console.log('Árbol DOM del botón:', boton.closest('.producto, .pedido-card'));
            console.groupEnd();
            
            boton.disabled = true;
            boton.title = 'Error: Faltan datos del pedido o producto';
            return;
        }
            
        // Si aún faltan datos, mostrar error y deshabilitar el botón
        if (!codPedido || !codProducto) {
            console.error('No se pudieron obtener los datos necesarios para el botón');
            boton.disabled = true;
            boton.classList.add('opacity-50', 'cursor-not-allowed');
            return;
        }
        
        // Configurar el evento click
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const estado = this.getAttribute('data-estado');
            const codPedido = this.getAttribute('data-cod-pedido');
            const codProducto = this.getAttribute('data-cod-producto');
            const numMesa = this.getAttribute('data-num-mesa');
            
            console.log('Botón clickeado:', { 
                estado, 
                codPedido, 
                codProducto, 
                numMesa,
                html: this.outerHTML 
            });
            
            if (!codPedido || !codProducto) {
                console.error('Faltan datos requeridos para actualizar el estado', {
                    codPedido,
                    codProducto,
                    html: this.outerHTML
                });
                alert('Error: No se pudo obtener la información necesaria para actualizar el estado.');
                return;
            }
            
            // Actualizar el estado del producto
            actualizarEstadoProducto(this, estado, codPedido, codProducto, numMesa);
        });
        
        // Marcar el botón como inicializado
        boton.setAttribute('data-inicializado', 'true');
        
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
        
        // Buscar todos los elementos de producto con este código
        const elementosProducto = document.querySelectorAll(`.producto[data-cod-producto="${codProducto}"]`);
        
        if (elementosProducto.length === 0) {
            console.log(`No se encontró el producto con código ${codProducto} en la interfaz`);
            cargarPedidosCocina();
            return;
        }
        
        // Crear un fragmento de documento para las actualizaciones por lotes
        const fragment = document.createDocumentFragment();
        const updates = [];
        
        // Procesar cada instancia del producto
        elementosProducto.forEach(elementoProducto => {
            // Actualizar el atributo data-estado
            elementoProducto.setAttribute('data-estado', estado);
            
            // Actualizar el texto del estado
            let estadoElement = elementoProducto.querySelector('.estado-producto');
            if (!estadoElement) {
                estadoElement = document.createElement('div');
                estadoElement.className = 'estado-producto text-sm font-medium px-2 py-1 rounded mt-1';
                const btnContainer = elementoProducto.querySelector('.btn-container');
                if (btnContainer) {
                    btnContainer.after(estadoElement);
                } else {
                    elementoProducto.appendChild(estadoElement);
                }
            }
            
            // Configurar actualización del estado
            const estadoMostrar = estado.charAt(0).toUpperCase() + estado.slice(1);
            updates.push(() => {
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
            });
            
            // Actualizar botones de acción
            const btnContainer = elementoProducto.querySelector('.btn-container');
            if (btnContainer) {
                const btnPreparando = btnContainer.querySelector('.btn-preparando');
                const btnListo = btnContainer.querySelector('.btn-listo');
                
                updates.push(() => {
                    if (btnPreparando) {
                        btnPreparando.disabled = estado !== 'pendiente';
                        if (estado === 'pendiente') {
                            btnPreparando.classList.remove('opacity-50', 'cursor-not-allowed');
                        } else {
                            btnPreparando.classList.add('opacity-50', 'cursor-not-allowed');
                        }
                    }
                    
                    if (btnListo) {
                        btnListo.disabled = estado === 'listo';
                        if (estado === 'pendiente' || estado === 'preparando') {
                            btnListo.classList.remove('opacity-50', 'cursor-not-allowed');
                        } else {
                            btnListo.classList.add('opacity-50', 'cursor-not-allowed');
                        }
                    }
                });
            }
            
            // Verificar si todos los productos del pedido están listos
            const tarjetaPedido = elementoProducto.closest('.pedido-card');
            if (tarjetaPedido) {
                updates.push(() => {
                    const productosPedido = tarjetaPedido.querySelectorAll('.producto');
                    const productosListos = Array.from(productosPedido).every(
                        prod => prod.getAttribute('data-estado') === 'listo'
                    );
                    
                    actualizarEstadoPedido(tarjetaPedido, productosListos);
                });
            }
        });
        
        // Ejecutar todas las actualizaciones
        updates.forEach(update => update());
        
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
                    const estadoProducto = producto.estadoProducto || 'pendiente';
                    const productoClase = estadoProducto === 'preparando' ? 'bg-yellow-50' : 
                                        estadoProducto === 'listo' ? 'bg-green-50' : 'bg-gray-50';
                    
                    // Asegurarse de que los IDs de pedido y producto sean válidos
                    const pedidoId = pedido.codPedido || pedido.cod || '0';
                    const productoId = producto.codProducto || producto.cod || '0';
                    
                    // Asegurarse de que numMesa sea un valor válido
                    const mesaId = numMesa && numMesa !== 'undefined' ? numMesa : '0';
                    
                    html += `
                            <div class="producto ${productoClase} p-3 rounded border border-gray-100" 
                                 data-cod-producto="${productoId}"
                                 data-producto-id="${productoId}">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-medium text-gray-800">${producto.nombre || 'Producto sin nombre'}</div>
                                        <div class="text-sm text-gray-500">Cant: ${producto.cantidad || 1}</div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button class="btn-estado btn-preparar px-2 py-1 text-xs rounded ${estadoProducto === 'preparando' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'} ${estadoProducto === 'listo' ? 'opacity-50 cursor-not-allowed' : ''}" 
                                                data-estado="preparando" 
                                                data-cod-pedido="${pedidoId}" 
                                                data-pedido-id="${pedidoId}" 
                                                data-cod-producto="${productoId}" 
                                                data-producto-id="${productoId}" 
                                                data-num-mesa="${mesaId}" 
                                                data-mesa="${mesaId}"
                                                ${estadoProducto === 'listo' ? 'disabled' : ''}>
                                            <span class="material-symbols-outlined" style="font-size: 1rem;">cooking</span>
                                        </button>
                                        <button class="btn-estado btn-listo px-2 py-1 text-xs rounded ${estadoProducto === 'listo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}" 
                                                data-estado="listo" 
                                                data-cod-pedido="${pedidoId}" 
                                                data-pedido-id="${pedidoId}" 
                                                data-cod-producto="${productoId}" 
                                                data-producto-id="${productoId}" 
                                                data-num-mesa="${mesaId}" 
                                                data-mesa="${mesaId}">
                                            <span class="material-symbols-outlined" style="font-size: 1rem;">check_circle</span>
                                        </button>
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
