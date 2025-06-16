"use strict";
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando interfaz de camarero v4 (Real-time)...');
    
    crearContenedorNotificaciones();
    cargarPedidosListos();
    iniciarWebSocket();
});

//funcion que utilizo para crear el contenedor de notificacionesº
function crearContenedorNotificaciones() {
    const containerId = 'notification-container';
    if (!document.getElementById(containerId)) {
        const container = document.createElement('div');
        container.id = containerId;
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '1000';
        document.body.appendChild(container);
        console.log('Contenedor de notificaciones creado con ID:', containerId);
    }
}

// funcion que utilizo para crear notificaciones temporales
function crearNotificacion(mensaje, tipo = 'info') {
    const container = document.getElementById('notification-container');
    if (!container) {
        console.error('El contenedor de notificaciones no existe.');
        return null;
    }
    
    const clases = {
        'success': 'bg-green-500',
        'error': 'bg-red-500',
        'info': 'bg-blue-500',
        'warning': 'bg-yellow-500'
    };
    
    const notificacion = document.createElement('div');
    notificacion.className = `${clases[tipo] || 'bg-gray-500'} text-white px-4 py-2 rounded mb-2 shadow-md`;
    notificacion.innerHTML = mensaje;
    
    container.appendChild(notificacion);
    
    // Auto-eliminar después de 3 segundos
    setTimeout(() => {
        if (notificacion.parentNode === container) {
            container.removeChild(notificacion);
        }
    }, 3000);
    
    return notificacion;
}

// funcion que utilizo para formatear fecha y hora en formato legible
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
        console.error('Error al formatear fecha:', error);
        return 'Fecha no disponible';
    }
}

// funcion que utilizo para cargar pedidos listos para servir
function cargarPedidosListos(forzarActualizacion = false) {
    // Usar fetch con parámetro action (no accion)
    // Aseguro que estoy haciendo la petición correctamente con la ruta absoluta
    fetch('./camarero.php?action=obtenerPedidosListos')
    .then(response => {
        if (!response.ok) {
            throw new Error(`Error de red: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Respuesta del servidor recibida');
        
       
        if (data.debug) {
            console.log('Información de debug:', data.debug);
        }
        
        if (data.success && data.pedidos && data.pedidos.length > 0) {
            // Filtro los pedidos con productos
            const pedidosConProductos = data.pedidos.filter(p => p.productos && p.productos.length > 0);
            
            if (pedidosConProductos.length > 0) {
                // Renderizo los pedidos
                renderizarPedidos(data.pedidos);
            } else {
                document.getElementById('contenedorPedidos').innerHTML = `
                    <div class="text-center py-12">
                        <p class="text-gray-500">No hay pedidos listos para servir.</p>
                    </div>`;
            }
        } else {
            document.getElementById('contenedorPedidos').innerHTML = `
                <div class="text-center py-12">
                    <p class="text-gray-500">No hay pedidos listos para servir.</p>
                </div>`;
        }
    })
    .catch(error => {
        console.error('Error al cargar pedidos:', error);
        document.getElementById('contenedorPedidos').innerHTML = `
            <div class="text-center p-6 bg-white rounded-lg shadow border-l-4 border-red-500">
                <p class="text-xl text-red-600">Error al cargar pedidos</p>
                <p class="text-gray-500 mt-2">Ha ocurrido un error técnico. Por favor, actualice la página.</p>
                <p class="text-gray-400 mt-1">${error.message}</p>
            </div>`;
    });
}

// función para renderizar pedidos agrupados por mesas
function renderizarPedidos(pedidos) {
    const contenedor = document.getElementById('contenedorPedidos');
    // Limpio el contenedor
    contenedor.innerHTML = '';
    
    // Si no hay pedidos, muestro mensaje
    if (!pedidos || pedidos.length === 0) {
        contenedor.innerHTML = `
            <div id="sin-pedidos" class="text-center py-12 px-4 bg-white rounded-lg shadow-md">
                <span class="material-symbols-outlined text-[#2563EB] text-5xl mb-3">room_service</span>
                <p class="text-xl text-gray-700 font-medium mb-2">No hay pedidos listos para servir</p>
                <p class="text-gray-500">Los pedidos listos para servir aparecerán aquí</p>
            </div>
        `;
        return;
    }
    
    // Agrupo los pedidos por mesa
    const pedidosPorMesa = {};
    pedidos.forEach(pedido => {
        if (!pedido.mesa) return;
        
        if (!pedidosPorMesa[pedido.mesa]) {
            pedidosPorMesa[pedido.mesa] = [];
        }
        pedidosPorMesa[pedido.mesa].push(pedido);
    });
    
    // Renderizo cada mesa con sus pedidos
    for (const [numMesa, pedidosMesa] of Object.entries(pedidosPorMesa)) {
        // Creo el contenedor de mesa con estilo similar a cocina
        const mesaElement = document.createElement('div');
        mesaElement.className = 'mesa-container mb-8';
        mesaElement.dataset.mesaId = numMesa;
        
        mesaElement.innerHTML = `<h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">Mesa ${numMesa}</h2>`;
        
        // Contenedor para pedidos de esta mesa con grid como en cocina
        const pedidosContainer = document.createElement('div');
        pedidosContainer.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4';
        mesaElement.appendChild(pedidosContainer);
        
        // Renderizo cada pedido
        pedidosMesa.forEach(pedido => {
            if (!pedido.productos || pedido.productos.length === 0) return;
            
            // Creo la tarjeta de pedido con estilos similares a cocina
            const pedidoCard = document.createElement('div');
            pedidoCard.className = 'pedido-card bg-white rounded-lg shadow-md p-4 border-l-4 border-green-500';
            pedidoCard.dataset.codPedido = pedido.cod;
            
            // Encabezado del pedido con estilo similar a cocina
            const pedidoHeader = document.createElement('div');
            pedidoHeader.className = 'pedido-header flex justify-between items-center mb-3 pb-2 border-b';
            
            // Fecha formateada para el encabezado
            const fechaFormateada = formatearFechaHora(pedido.fecha);
            const horaParte = fechaFormateada.split(' - ')[0];
            const fechaParte = fechaFormateada.split(' - ')[1] || '';
            
            pedidoHeader.innerHTML = `
                <div class="flex items-center">
                    <span class="text-sm font-bold text-gray-700 mr-2">Pedido #${pedido.cod}</span>
                    <span class="hora-pedido text-xs font-medium text-gray-500">${horaParte}</span>
                </div>
                <div class="flex items-center">
                    <span class="fecha-pedido text-xs text-gray-400">${fechaParte}</span>
                    <span class="estado-pedido text-xs font-medium px-2 py-1 rounded-full bg-green-100 text-green-800 ml-2">Listo para servir</span>
                </div>
            `;
            pedidoCard.appendChild(pedidoHeader);
            
            // Contenedor para productos
            const productosContainer = document.createElement('div');
            productosContainer.className = 'space-y-3';
            pedidoCard.appendChild(productosContainer);
            
            // Renderizo cada producto del pedido
            pedido.productos.forEach(producto => {
                const codIdentificador = producto.codDetalle || `${pedido.cod}-${producto.codProducto}`;
                
                // Creo el elemento de producto con estilo similar a cocina
                const productoDiv = document.createElement('div');
                productoDiv.className = 'producto bg-green-50 p-3 rounded-lg border border-green-200 relative';
                productoDiv.setAttribute('data-cod-producto', producto.codProducto);
                productoDiv.setAttribute('data-cod-detalle', codIdentificador);
                
                // Contenido del producto
                productoDiv.innerHTML = `
                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <span class="nombre font-medium text-gray-800 block">${producto.nombre}</span>
                            <span class="cantidad text-sm text-gray-600 block">Cantidad: ${producto.cantidad}</span>
                        </div>
                    </div>
                    <button class="btnServir servir w-full p-3 mt-2 bg-[#72E8AC] hover:bg-[#60C99F] text-[#256353] font-bold text-lg rounded-lg transition-colors flex items-center justify-center shadow-md border-2 border-[#60C99F]">
                        <span class="material-symbols-outlined mr-1">check_circle</span>
                        SERVIR
                    </button>
                `;
                
                // Configuro el botón SERVIR con sus atributos
                const botonServir = productoDiv.querySelector('button');
                botonServir.setAttribute('data-pedido', pedido.cod);
                botonServir.setAttribute('data-producto', producto.codProducto);
                botonServir.setAttribute('data-detalle', codIdentificador);
                
                // Añadir el producto al contenedor
                productosContainer.appendChild(productoDiv);
            });
            
            // Añado el pedido al contenedor
            pedidosContainer.appendChild(pedidoCard);
        });
        
        // Añado la mesa al contenedor principal
        contenedor.appendChild(mesaElement);
    }
    
    // Si no hay mesas después de procesar, muestro mensaje
    if (!contenedor.querySelector('.mesa-container')) {
        contenedor.innerHTML = `
            <div class="text-center py-12 px-4 bg-white rounded-lg shadow-md">
                <span class="material-symbols-outlined text-[#2563EB] text-5xl mb-3">room_service</span>
                <p class="text-xl text-gray-700 font-medium mb-2">No hay pedidos listos para servir</p>
                <p class="text-gray-500">Los pedidos listos para servir aparecerán aquí</p>
            </div>
        `;
    }
    
    // Inicializo botones SERVIR
    inicializarBotonesServir();
}

// funcion que utilizo para verificar si una mesa está vacía y ocultarla en ese caso
function verificarYocultarMesaSiVacia(mesaElement) {
    if (!mesaElement) {
        return;
    }
    
    // Busco productos dentro de esta mesa
    const productos = mesaElement.querySelectorAll('.producto');
    
    // Si no hay productos, eliminar la mesa inmediatamente (sin animación)
    if (productos.length === 0) {
        mesaElement.remove();
        revisarEstadoGeneralPedidos();
    }
}

// funcion que utilizo para inicializar los botones SERVIR
function inicializarBotonesServir() {
    // Busco elementos ya existentes y futuros con delegación de eventos
    const contenedor = document.getElementById('contenedorPedidos');
    
    if (!contenedor) {
        console.error('No se encontró el contenedor de pedidos');
        return;
    }
    
    contenedor.addEventListener('click', function(e) {
        // Busco botones con la clase btnServir o servir
        const boton = e.target.closest('.servir, .btnServir');
        
        if (boton) {
            e.preventDefault();
            
            const codDetalle = boton.getAttribute('data-detalle');
            if (codDetalle) {
                marcarComoServido(codDetalle);
            } else {
                crearNotificacion('Error: No se pudo identificar el producto.', 'error');
            }
        }
    });
    
    // Verifico que los botones están presentes
    const botones = document.querySelectorAll('.servir, .btnServir');
    console.log(`Inicialización de botones SERVIR completada. Se encontraron ${botones.length} botones.`);
}

// funcion que utilizo para marcar un producto como servido con ACTUALIZACIÓN OPTIMISTA
function marcarComoServido(codDetalle) {
    // 1. Buscar el elemento en la UI.
    const productoElement = document.querySelector(`.producto[data-cod-detalle="${codDetalle}"]`);

    // Si el elemento no existe, es porque ya fue servido
    if (!productoElement) {
        return; // Salir de la función
    }
    
    // 2. Buscar el pedido y la mesa que contienen este producto
    const pedidoCard = productoElement.closest('.pedido-card');
    const mesaContainer = productoElement.closest('.mesa-container');

    // 3. Eliminamos el producto inmediatamente
    productoElement.remove();
    
    // 4. Verifico si el pedido quedó sin productos
    if (pedidoCard) {
        const productosRestantes = pedidoCard.querySelectorAll('.producto');
        if (productosRestantes.length === 0) {
            // Si el pedido quedó sin productos, lo eliminamos
            pedidoCard.remove();
        }
    }
    
    // 5. Verificar si la mesa quedó sin productos
    if (mesaContainer) {
        verificarYocultarMesaSiVacia(mesaContainer);
    }

    // 3. Envio la solicitud al servidor en segundo plano.
    fetch('./camarero.php?action=marcarComoServido', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ codDetalle: codDetalle })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.log('Error al marcar como servido:', data.error || 'Error desconocido');
            // Esperamos un momento antes de recargar para evitar problemas
            setTimeout(() => cargarPedidosListos(), 500); 
        } else {
            console.log('Producto marcado como servido exitosamente');
        }
    })
    .catch(error => {
        console.log('Error en la petición:', error.message || error);
        setTimeout(() => cargarPedidosListos(), 500);
        // Recargo la lista para reflejar el estado real del servidor.
        cargarPedidosListos();
    });
}

// funcion que utilizo para verificar si una mesa quedo vacia y ocultarla.
function verificarYocultarMesaSiVacia(mesaContainer) {
    if (!mesaContainer) {
        return;
    }
    
    // Verifico si la mesa tiene productos
    const productosRestantes = mesaContainer.querySelectorAll('.producto');
    
    // Si no hay productos, elimino la mesa inmediatamente
    if (productosRestantes.length === 0) {
        mesaContainer.remove();
        revisarEstadoGeneralPedidos(); // Comprobar si no hay más pedidos
    }
}

// funcion que utilizo para verificar si la pantalla de pedidos está vacía y mostrar un mensaje.
function revisarEstadoGeneralPedidos() {
    const contenedorPedidos = document.getElementById('contenedorPedidos');
    // Se usa querySelector para ser más específico y evitar contar otros nodos (ej. texto)
    const mesasRestantes = contenedorPedidos.querySelector('.mesa-container');

    if (!mesasRestantes) {
        console.log('No quedan pedidos. Mostrando mensaje final.');
        contenedorPedidos.innerHTML = `
            <div class="text-center py-12 rounded-lg shadow-md">
                <p class="text-gray-500 mt-2">No hay más pedidos listos por ahora.</p>
            </div>`;
    }
}

// funcion que utilizo para procesar las notificaciones del websocket
function procesarNotificacionWebSocket(mensaje) {
    console.log('[WS] Mensaje WebSocket CRUDO recibido:', mensaje);
    let data;
    try {
        data = JSON.parse(mensaje);
    } catch (error) {
        console.error('[WS] Error al parsear JSON de WebSocket:', error, "Mensaje original:", mensaje);
        crearNotificacion('Error interno procesando actualización.', 'error');
        return;
    }

    console.log('[WS] Datos parseados del WebSocket:', data);

    const contenedorPedidos = document.getElementById('contenedorPedidos');
    if (!contenedorPedidos && (data.tipo === 'productoListo' || data.tipo === 'pedidoListo' || data.tipo === 'nuevoPedido' || data.tipo === 'productoServido')) {
        console.error('[WS] Contenedor de pedidos no encontrado, no se puede actualizar la UI para el mensaje:', data.tipo);
        return;
    }

    switch (data.tipo) {
        case 'productoListo':
        case 'pedidoListo':
        case 'nuevoPedido':
            console.log(`[WS] Notificación '${data.tipo}' recibida. Mensaje: ${data.mensaje || 'N/A'}. Recargando TODOS los pedidos.`);
            crearNotificacion(data.mensaje || `Actualización de cocina: ${data.tipo}`, 'info');
            cargarPedidosListos(); // ¡ESTO ES LO MÁS IMPORTANTE PARA ACTUALIZACIONES EN TIEMPO REAL!
            break;

        case 'productoServido':
            console.log(`[WS] Notificación 'productoServido' recibida para detalle: ${data.codDetalle}. Mesa: ${data.numMesa}`);
            if (data.codDetalle) {
                const productoElement = document.querySelector(`.producto[data-cod-detalle="${data.codDetalle}"]`);
                if (productoElement) {
                    const mesaContainer = productoElement.closest('.mesa-container');
                    console.log(`[WS] Eliminando producto ${data.codDetalle} de la UI debido a notificación 'productoServido'.`);
                    productoElement.remove();
                    if (mesaContainer) {
                        console.log(`[WS] Verificando si la mesa ${mesaContainer.dataset.mesaId || ''} quedó vacía tras 'productoServido'.`);
                        verificarYocultarMesaSiVacia(mesaContainer);
                    } else {
                         console.warn(`[WS] No se encontró mesa-container para producto ${data.codDetalle} tras 'productoServido'.`);
                    }
                } else {
                    console.log(`[WS] Producto ${data.codDetalle} no encontrado en la UI para eliminar por notificación 'productoServido'. Podría ya estar servido.`);
                }
            } else {
                console.warn("[WS] Notificación 'productoServido' sin 'codDetalle'. No se puede procesar.");
            }
            break;
       
    }
}

// variable global para la instancia de WebSocket del camarero
window.wsCamarero = null;

// funcion que utilizo para iniciar y gestionar la conexion WebSocket
function iniciarWebSocket() {
    // Prevenir multiples conexiones si ya esta abierta o conectando
    if (window.wsCamarero && (window.wsCamarero.readyState === WebSocket.OPEN || window.wsCamarero.readyState === WebSocket.CONNECTING)) {
        console.log(`[WS Camarero] Se evitó una nueva conexión. Estado actual: ${window.wsCamarero.readyState}`);
        return;
    }

    console.log('[WS Camarero] Intentando conectar...');
    window.wsCamarero = new WebSocket(window.location.hostname === "localhost" ? "ws://localhost:8081" : "wss://websocket-u5s9.onrender.com");

    window.wsCamarero.onopen = function() {
        console.log('[WS Camarero] Conectado al servidor WebSocket.');
        // envio un mensaje de registro para identificar este cliente como 'camarero'
        // aseguro que el servidor espera un objeto con 'tipo' y 'tipoCliente'
        const registro = {
            tipo: 'registro', // el servidor espera un campo 'tipo'
            tipoCliente: 'camarero'
        };
        window.wsCamarero.send(JSON.stringify(registro));
    };

    window.wsCamarero.onmessage = function(evento) {
        procesarNotificacionWebSocket(evento.data);
    };

    window.wsCamarero.onclose = function(event) {
        console.warn(`[WS Camarero] Desconectado. Razón: ${event.reason || 'Desconocido'} (código: ${event.code}). Reintentando en 3 segundos...`);
        window.wsCamarero = null; // Limpio la instancia para permitir una nueva creación
        setTimeout(iniciarWebSocket, 3000);
    };

    window.wsCamarero.onerror = function(error) {
        console.error('[WS Camarero] Error de WebSocket:', error);
    };
}
