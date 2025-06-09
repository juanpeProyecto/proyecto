/**
 * Sistema unificado para la página de gestión de cocina
 * Combina la funcionalidad de cocina.js e inicializarCocina.js
 */

// Inicialización principal cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando página de cocina...');
    
    // Inicializar los botones de estado
    inicializarBotones();
    
    // Cargar pedidos pendientes al iniciar - Usamos nuestra propia versión
    // para evitar conflictos con gestionPedidos.js
    cargarPedidosCocina();
    
    // Configurar conexión WebSocket
    iniciarWebSocket();
    
    // Actualizar contadores
    actualizarContadores();
});

// Función para actualizar el estado de un producto individual
function actualizarEstadoProducto(boton, estado, codPedido, codProducto, numMesa) {
    console.log(`Actualizando estado de producto ${codProducto} a ${estado}`);
    
    // Obtener el contenedor del producto
    const elementoProducto = boton.closest('.producto');
    if (!elementoProducto) {
        console.error('No se pudo encontrar el contenedor del producto', boton);
        return;
    }
    
    // Mostrar estado en consola
    console.log('Actualizando estado del producto:', {codPedido, codProducto, estado, numMesa});
    
    // Actualizar el atributo data-estado del producto
    elementoProducto.setAttribute('data-estado', estado);
    
    // Actualizar el texto del botón para mostrar el estado actual
    const estadoMostrar = estado.charAt(0).toUpperCase() + estado.slice(1);
    boton.textContent = estadoMostrar;
    
    // Deshabilitar el botón después de hacer clic
    boton.disabled = true;
    
    // Guardar el texto original del botón
    const textoOriginalBoton = boton.textContent;

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
            const estadoMostrar = data.estadoProducto.charAt(0).toUpperCase() + data.estadoProducto.slice(1);
            
            // Actualizar el botón que se hizo clic
            boton.textContent = estadoMostrar;
            boton.disabled = true;
            
            // Actualizar el atributo data-estado del producto
            if (elementoProducto) {
                elementoProducto.setAttribute('data-estado', data.estadoProducto);
                
                // Actualizar el texto del estado si existe un elemento de estado
                const estadoElement = elementoProducto.querySelector('.estado-producto');
                if (estadoElement) {
                    estadoElement.textContent = estadoMostrar;
                }
                
                // Si el pedido está completo, eliminarlo de la interfaz
                if (data.pedidoCompleto) {
                    const tarjetaPedido = elementoProducto.closest('.pedido-card');
                    if (tarjetaPedido) {
                        tarjetaPedido.remove();
                    }
                }
            }
            
            // Actualizar contadores
            actualizarContadores();
        } else {
            throw new Error(data.mensaje || 'Error desconocido del servidor');
        }
    })
    .catch(error => {
        console.error('Error al actualizar estado:', error);
        
        // Mostrar mensaje de error en consola
        console.error(`Error: ${error.message}`);
        
        // Restaurar el botón
        if (boton) {
            boton.disabled = false;
            boton.innerHTML = textoOriginalBoton;
        }
        const botones = elementoProducto.querySelectorAll('.btn-estado-producto');
        botones.forEach(btn => {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            if (btn === boton) {
                btn.textContent = textoOriginalBoton;
            }
        });
    });
}

// Inicializar los botones de cambio de estado de todos los productos
function inicializarBotones() {
    console.log('Inicializando botones de estado...');
    
    // También inicializar los botones de completar pedido
    inicializarBotonesCompletarPedido();
    
    // Seleccionar todos los contenedores de producto
    const productos = document.querySelectorAll('.producto');
    
    productos.forEach(producto => {
        const botones = producto.querySelectorAll('.btn-estado-producto');
        const estadoActual = producto.getAttribute('data-estado') || 'pendiente';
        
        // Inicializar el estado visual
        let estadoElement = producto.querySelector('.estado-producto');
        if (!estadoElement) {
            estadoElement = document.createElement('div');
            estadoElement.className = 'estado-producto text-sm font-medium px-2 py-1 rounded mt-1';
            const nombreProducto = producto.querySelector('.producto-name');
            if (nombreProducto && nombreProducto.parentNode) {
                nombreProducto.parentNode.insertBefore(estadoElement, nombreProducto.nextSibling);
            } else {
                producto.insertBefore(estadoElement, producto.firstChild);
            }
        }
        
        // Establecer estado inicial
        const estadoMostrar = estadoActual.charAt(0).toUpperCase() + estadoActual.slice(1);
        estadoElement.textContent = `Estado: ${estadoMostrar}`;
        
        // Aplicar estilos según el estado
        estadoElement.className = 'estado-producto text-sm font-medium px-2 py-1 rounded mt-1 ';
        if (estadoActual === 'preparando') {
            estadoElement.classList.add('bg-yellow-100', 'text-yellow-800');
            producto.classList.add('bg-yellow-50');
        } else if (estadoActual === 'listo') {
            estadoElement.classList.add('bg-green-100', 'text-green-800');
            producto.classList.add('bg-green-50');
        } else {
            estadoElement.classList.add('bg-gray-100', 'text-gray-800');
        }
        
        // Configurar eventos de los botones
        botones.forEach(boton => {
            // Eliminar eventos anteriores para evitar duplicados
            const nuevoBoton = boton.cloneNode(true);
            boton.parentNode.replaceChild(nuevoBoton, boton);
            
            nuevoBoton.addEventListener('click', function() {
                const estado = this.getAttribute('data-estado');
                const codPedido = this.getAttribute('data-cod-pedido');
                const codProducto = this.getAttribute('data-cod-producto');
                const numMesa = this.getAttribute('data-num-mesa');
                
                console.log(`Botón ${estado} presionado para producto ${codProducto} en pedido ${codPedido}`);
                actualizarEstadoProducto(this, estado, codPedido, codProducto, numMesa);
            });
        });
    });
}

// Cargar pedidos pendientes al iniciar (versión específica para cocina)
function cargarPedidosCocina() {
    console.log('Cargando pedidos pendientes...');
    fetch('cocina.php?action=obtenerPedidosPendientes&area=cocina')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(pedidos => {
            console.log('Pedidos pendientes cargados:', pedidos);
            actualizarContadores();
        })
        .catch(error => {
            console.error('Error al cargar pedidos pendientes:', error);
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
        };
    } else {
        console.error('WebSockets no soportados en este navegador');
    }
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
