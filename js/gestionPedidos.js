/**
 * Funciones para gestionar pedidos en cocina
 */

// Función para agregar un nuevo pedido a la interfaz
function agregarNuevoPedido(datos) {
    console.log('Agregando nuevo pedido:', datos);
    const contenedorPedidos = document.getElementById('contenedorPedidos');
    if (!contenedorPedidos) {
        console.error('No se encontró el contenedor de pedidos');
        return;
    }
    
    // Si hay mensaje de "no hay pedidos", lo eliminamos
    const mensajeNoPedidos = contenedorPedidos.querySelector('.animate-pulse');
    if (mensajeNoPedidos) {
        mensajeNoPedidos.remove();
    }
    
    // Usamos la plantilla HTML para crear el nuevo pedido
    const plantilla = document.getElementById('plantillaPedido');
    if (!plantilla) {
        console.error('No se encontró la plantilla de pedido');
        return;
    }
    
    const nuevoElemento = document.importNode(plantilla.content, true);
    
    // Completamos información básica del pedido
    const tituloPedido = nuevoElemento.querySelector('h2');
    if (tituloPedido) {
        tituloPedido.textContent = `Pedido #${datos.cod}`;
    }
    
    const nuevoPedido = nuevoElemento.querySelector('.pedido');
    if (nuevoPedido) {
        nuevoPedido.setAttribute('data-cod-pedido', datos.cod);
        nuevoPedido.classList.add('nuevoPedido');
        setTimeout(() => {
            nuevoPedido.classList.remove('nuevoPedido');
        }, 3000);
    }
    
    // Cargar los detalles del pedido mediante AJAX
    fetch(`obtenerDetallesPedido.php?cod=${datos.cod}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error: ${response.status}`);
            }
            return response.json();
        })
        .then(detalles => {
            console.log('Detalles recibidos:', detalles); // Para depuración
            const contenedorDetalles = nuevoElemento.querySelector('.detallesPedido');
            if (!contenedorDetalles) {
                console.error('No se encontró el contenedor de detalles');
                return;
            }
            
            // Crear contenedor de productos
            const listaProductos = document.createElement('ul');
            listaProductos.className = 'space-y-3';
            
            // Añadir cada producto
            if (detalles.productos && Array.isArray(detalles.productos)) {
                detalles.productos.forEach(producto => {
                    if (!producto) return; // Ignoramos productos nulos
                    
                    // Usamos la plantilla para productos
                    const plantillaProducto = document.getElementById('plantillaProducto');
                    if (!plantillaProducto) return;
                    
                    const nuevoProducto = document.importNode(plantillaProducto.content, true);
                    
                    // Rellenar datos del producto
                    const nombreElement = nuevoProducto.querySelector('.nombre');
                    if (nombreElement) nombreElement.textContent = producto.nombre;
                    
                    const cantidadElement = nuevoProducto.querySelector('.cantidad');
                    if (cantidadElement) cantidadElement.textContent = producto.cantidad;
                    
                    const productoLi = nuevoProducto.querySelector('li');
                    if (productoLi) {
                        productoLi.setAttribute('data-cod-producto', producto.codProducto);
                        productoLi.setAttribute('data-cod-pedido', datos.cod);
                    }
                    
                    // Rellenar observaciones si existen
                    const obsElement = nuevoProducto.querySelector('.observacionesProducto');
                    if (obsElement && producto.observaciones) {
                        obsElement.textContent = producto.observaciones;
                    }
                    
                    // Añadir botones con sus manejadores de eventos
                    const btnPreparando = nuevoProducto.querySelector('.btnPreparando');
                    if (btnPreparando) {
                        btnPreparando.setAttribute('data-estado', 'preparando');
                        btnPreparando.setAttribute('data-cod-producto', producto.codProducto);
                        btnPreparando.setAttribute('data-cod-pedido', datos.cod);
                        btnPreparando.setAttribute('data-num-mesa', detalles.numMesa || 0);
                        btnPreparando.classList.add('btn-estado-producto');
                        
                        btnPreparando.addEventListener('click', function() {
                            const estado = this.getAttribute('data-estado');
                            const codPedido = parseInt(this.getAttribute('data-cod-pedido'));
                            const codProducto = parseInt(this.getAttribute('data-cod-producto'));
                            const numMesa = this.getAttribute('data-num-mesa');
                            
                            actualizarEstadoProducto(this, estado, codPedido, codProducto, numMesa);
                        });
                    }
                    
                    const btnListo = nuevoProducto.querySelector('.btnListo');
                    if (btnListo) {
                        btnListo.setAttribute('data-estado', 'listo');
                        btnListo.setAttribute('data-cod-producto', producto.codProducto);
                        btnListo.setAttribute('data-cod-pedido', datos.cod);
                        btnListo.setAttribute('data-num-mesa', detalles.numMesa || 0);
                        btnListo.classList.add('btn-estado-producto');
                        
                        btnListo.addEventListener('click', function() {
                            const estado = this.getAttribute('data-estado');
                            const codPedido = parseInt(this.getAttribute('data-cod-pedido'));
                            const codProducto = parseInt(this.getAttribute('data-cod-producto'));
                            const numMesa = this.getAttribute('data-num-mesa');
                            
                            actualizarEstadoProducto(this, estado, codPedido, codProducto, numMesa);
                        });
                    }
                    
                    // Establecer el estado visual según el estado del producto
                    const estadoElement = nuevoProducto.querySelector('.estadoProducto');
                    if (estadoElement && productoLi) {
                        estadoElement.textContent = producto.estado || 'pendiente';
                        estadoElement.className = 'estadoProducto ml-2 px-2 py-0.5 text-xs font-medium rounded-full';
                        
                        if (producto.estado === 'preparando') {
                            productoLi.style.backgroundColor = '#fefce8';
                            estadoElement.style.cssText = 'background-color:#fef3c7; color:#92400e; border-radius:9999px; padding:2px 8px;';
                            
                            // Desactivar botón preparando
                            if (btnPreparando) {
                                btnPreparando.disabled = true;
                                btnPreparando.classList.add('opacity-50', 'cursor-not-allowed');
                            }
                        } else if (producto.estado === 'listo') {
                            productoLi.style.backgroundColor = '#f0fdf4';
                            estadoElement.style.cssText = 'background-color:#dcfce7; color:#166534; border-radius:9999px; padding:2px 8px;';
                            
                            // Desactivar ambos botones
                            if (btnPreparando) {
                                btnPreparando.disabled = true;
                                btnPreparando.classList.add('opacity-50', 'cursor-not-allowed');
                            }
                            if (btnListo) {
                                btnListo.disabled = true;
                                btnListo.classList.add('opacity-50', 'cursor-not-allowed');
                            }
                        }
                    }
                    
                    // Añadir el producto a la lista
                    listaProductos.appendChild(nuevoProducto);
                });
            }
            
            // Limpiamos y añadimos la lista al contenedor
            contenedorDetalles.innerHTML = '';
            contenedorDetalles.appendChild(listaProductos);
            
            // Actualizar contadores de estados
            const contPendientes = nuevoElemento.querySelector('#contadorPendientes');
            const contPreparando = nuevoElemento.querySelector('#contadorPreparando');
            
            if (contPendientes && contPreparando && detalles.contadores) {
                contPendientes.textContent = detalles.contadores.pendientes || 0;
                contPreparando.textContent = detalles.contadores.preparando || 0;
            }
        })
        .catch(error => {
            console.error('Error al cargar detalles del pedido:', error);
            const contenedorDetalles = nuevoElemento.querySelector('.detallesPedido');
            if (contenedorDetalles) {
                contenedorDetalles.innerHTML = `<p class="text-red-500">Error al cargar detalles: ${error.message}</p>`;
            }
        });
    
    // Añadimos el nuevo pedido al contenedor
    contenedorPedidos.insertBefore(nuevoElemento, contenedorPedidos.firstChild);
}

// Función para actualizar el estado de un pedido
function actualizarEstadoPedido(datos) {
    const pedido = document.querySelector(`.pedido[data-cod-pedido="${datos.codPedido}"]`);
    console.log('Actualizando estado del pedido:', datos);
    
    if (pedido) {
        // Los pedidos solo se ocultan cuando están en estado 'listo' y se marca el botón "Completado"
        // o cuando están en estado 'completado'
        if (datos.estado === 'listo') {
            // Marcar el pedido como listo pero no ocultarlo
            pedido.classList.add('border-green-500');
            const btnCompletado = pedido.querySelector('.btnCompletado');
            if (btnCompletado) {
                btnCompletado.classList.remove('bg-[#72E8AC]', 'text-[#256353]');
                btnCompletado.classList.add('bg-green-500', 'text-white');
                btnCompletado.innerHTML = `
                    <span class="material-symbols-outlined text-sm align-middle mr-1">check_circle</span> Listo para servir
                `;
            }
        } else if (datos.estado === 'completado') {
            // Si el empleado marca completado, quitamos el pedido de la vista
            pedido.classList.add('bg-green-50');
            setTimeout(() => {
                pedido.remove();
                
                // Si no quedan pedidos, mostramos el mensaje de "esperando pedidos"
                const contenedorPedidos = document.getElementById('contenedorPedidos');
                if (contenedorPedidos && contenedorPedidos.children.length === 0) {
                    contenedorPedidos.innerHTML = `
                        <div class="animate-pulse text-center p-6">
                            <p class="text-gray-500">Esperando pedidos...</p>
                        </div>
                    `;
                }
            }, 1000);
        }
    }
}

// Cargar pedidos pendientes al iniciar
function cargarPedidosPendientes() {
    console.log('Iniciando carga de pedidos pendientes en cocina...');
    fetch('cocina.php?action=obtenerPedidosPendientes&area=cocina')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(pedidos => {
            console.log('Pedidos pendientes cargados:', pedidos);
            const contenedorPedidos = document.getElementById('contenedorPedidos');
            
            if (!contenedorPedidos) {
                console.error('No se encontró el contenedor de pedidos');
                return;
            }
            
            // Limpiar mensajes de carga
            contenedorPedidos.innerHTML = '';
            
            // Si no hay pedidos, mostrar mensaje
            if (!pedidos || pedidos.length === 0) {
                contenedorPedidos.innerHTML = `
                    <div class="animate-pulse text-center p-6">
                        <p class="text-gray-500">Esperando pedidos...</p>
                    </div>
                `;
                return;
            }
            
            // Para cada pedido pendiente, obtenemos sus detalles y lo mostramos
            pedidos.forEach(pedido => {
                const plantilla = document.getElementById('plantillaPedido');
                const nuevoElemento = document.importNode(plantilla.content, true);
                
                // Rellenamos los datos básicos
                const tituloPedido = nuevoElemento.querySelector('h2');
                if (tituloPedido) {
                    tituloPedido.textContent = `Pedido #${pedido.cod}`;
                }
                
                const nuevoPedido = nuevoElemento.querySelector('.pedido');
                if (nuevoPedido) {
                    nuevoPedido.setAttribute('data-cod-pedido', pedido.cod);
                }
                
                // Cargar detalles del pedido mediante AJAX
                fetch(`obtenerDetallesPedido.php?cod=${pedido.cod}`)
                    .then(response => response.json())
                    .then(detalles => {
                        // Obtenemos el pedido del DOM que acabamos de añadir
                        const pedidoEnDOM = document.querySelector(`.pedido[data-cod-pedido="${pedido.cod}"]`);
                        if (!pedidoEnDOM) return;
                        
                        // Contenedor de productos
                        const contenedorDetalles = pedidoEnDOM.querySelector('.detallesPedido');
                        const listaProductos = document.createElement('ul');
                        listaProductos.className = 'space-y-3';
                        
                        // Añadir cada producto
                        if (detalles.productos && Array.isArray(detalles.productos)) {
                            detalles.productos.forEach(producto => {
                                if (!producto) return;
                                
                                // Usamos la plantilla para productos
                                const plantillaProducto = document.getElementById('plantillaProducto');
                                if (!plantillaProducto) return;
                                
                                const nuevoProducto = document.importNode(plantillaProducto.content, true);
                                
                                // Rellenar datos del producto
                                const nombreElement = nuevoProducto.querySelector('.nombre');
                                if (nombreElement) nombreElement.textContent = producto.nombre;
                                
                                const cantidadElement = nuevoProducto.querySelector('.cantidad');
                                if (cantidadElement) cantidadElement.textContent = producto.cantidad;
                                
                                const productoLi = nuevoProducto.querySelector('li');
                                if (productoLi) {
                                    productoLi.setAttribute('data-cod-producto', producto.codProducto);
                                    productoLi.setAttribute('data-cod-pedido', pedido.cod);
                                }
                                
                                // Rellenar observaciones si existen
                                const obsElement = nuevoProducto.querySelector('.observacionesProducto');
                                if (obsElement && producto.observaciones) {
                                    obsElement.textContent = producto.observaciones;
                                }
                                
                                // Añadir botones con sus manejadores de eventos
                                const btnPreparando = nuevoProducto.querySelector('.btnPreparando');
                                if (btnPreparando) {
                                    btnPreparando.setAttribute('data-estado', 'preparando');
                                    btnPreparando.setAttribute('data-cod-producto', producto.codProducto);
                                    btnPreparando.setAttribute('data-cod-pedido', pedido.cod);
                                    btnPreparando.setAttribute('data-num-mesa', detalles.numMesa || 0);
                                    btnPreparando.classList.add('btn-estado-producto');
                                }
                                
                                const btnListo = nuevoProducto.querySelector('.btnListo');
                                if (btnListo) {
                                    btnListo.setAttribute('data-estado', 'listo');
                                    btnListo.setAttribute('data-cod-producto', producto.codProducto);
                                    btnListo.setAttribute('data-cod-pedido', pedido.cod);
                                    btnListo.setAttribute('data-num-mesa', detalles.numMesa || 0);
                                    btnListo.classList.add('btn-estado-producto');
                                }
                                
                                // Establecer el estado visual según el estado del producto
                                const estadoElement = nuevoProducto.querySelector('.estadoProducto');
                                if (estadoElement && productoLi) {
                                    estadoElement.textContent = producto.estado || 'pendiente';
                                    estadoElement.className = 'estadoProducto ml-2 px-2 py-0.5 text-xs font-medium rounded-full';
                                    
                                    if (producto.estado === 'preparando') {
                                        productoLi.style.backgroundColor = '#fefce8';
                                        estadoElement.style.cssText = 'background-color:#fef3c7; color:#92400e; border-radius:9999px; padding:2px 8px;';
                                        
                                        // Desactivar botón preparando
                                        if (btnPreparando) {
                                            btnPreparando.disabled = true;
                                            btnPreparando.classList.add('opacity-50', 'cursor-not-allowed');
                                        }
                                    } else if (producto.estado === 'listo') {
                                        productoLi.style.backgroundColor = '#f0fdf4';
                                        estadoElement.style.cssText = 'background-color:#dcfce7; color:#166534; border-radius:9999px; padding:2px 8px;';
                                        
                                        // Desactivar ambos botones
                                        if (btnPreparando) {
                                            btnPreparando.disabled = true;
                                            btnPreparando.classList.add('opacity-50', 'cursor-not-allowed');
                                        }
                                        if (btnListo) {
                                            btnListo.disabled = true;
                                            btnListo.classList.add('opacity-50', 'cursor-not-allowed');
                                        }
                                    }
                                }
                                
                                // Añadir el producto a la lista
                                listaProductos.appendChild(nuevoProducto);
                            });
                        }
                        
                        // Limpiamos y añadimos la lista al contenedor
                        contenedorDetalles.innerHTML = '';
                        contenedorDetalles.appendChild(listaProductos);
                        
                        // Actualizar contadores de estados
                        const contPendientes = pedidoEnDOM.querySelector('#contadorPendientes');
                        const contPreparando = pedidoEnDOM.querySelector('#contadorPreparando');
                        
                        if (contPendientes && contPreparando && detalles.contadores) {
                            contPendientes.textContent = detalles.contadores.pendientes || 0;
                            contPreparando.textContent = detalles.contadores.preparando || 0;
                        }
                        
                        // Aplicar estilos de estado al pedido según su estado general
                        if (detalles.estadoPedido === 'listo') {
                            pedidoEnDOM.classList.add('border-green-500');
                            const btnCompletado = pedidoEnDOM.querySelector('.btnCompletado');
                            if (btnCompletado) {
                                btnCompletado.classList.remove('bg-[#72E8AC]');
                                btnCompletado.classList.add('bg-green-500', 'text-white');
                                btnCompletado.innerHTML = `
                                    <span class="material-symbols-outlined text-sm align-middle mr-1">check_circle</span> Listo para servir
                                `;
                            }
                        }
                    })
                    .catch(error => {
                        console.error(`Error al cargar detalles del pedido ${pedido.cod}:`, error);
                    });
                
                // Añadimos el pedido al contenedor
                contenedorPedidos.appendChild(nuevoElemento);
            });
        })
        .catch(error => {
            console.error('Error al cargar pedidos pendientes:', error);
        });
}

// Al cargar el documento
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si estamos en la página que usa este contenedor
    // antes de intentar cargar pedidos
    if (document.getElementById('contenedorPedidos')) {
        // Cargar pedidos pendientes solo si existe el contenedor
        cargarPedidosPendientes();
    }
});
