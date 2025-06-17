"use strict";

// funcion que uso para agregar un nuevo pedido a la interfaz
function agregarNuevoPedido(datos) {
    const contenedorPedidos = document.getElementById('contenedorPedidos');
    if (!contenedorPedidos) {
        return;
    }
    
    // si hay mensaje de no hay pedidos lo eliminamos
    const mensajeNoPedidos = contenedorPedidos.querySelector('.animate-pulse');
    if (mensajeNoPedidos) {
        mensajeNoPedidos.remove();
    }
    
    // uso la plantilla HTML para crear el nuevo pedido
    const plantilla = document.getElementById('plantillaPedido');
    if (!plantilla) {
        return;
    }
    
    const nuevoElemento = document.importNode(plantilla.content, true); // uso la plantilla HTML para crear el nuevo pedido

    // Aseguro que el pie de la tarjeta solo contenga la hora
    const pieTarjeta = nuevoElemento.querySelector('#pedido-card-footer');
    if (pieTarjeta) {
        const divHora = pieTarjeta.querySelector('.text-gray-500.text-sm'); // El div que envuelve el span de la hora
        if (divHora) {
            pieTarjeta.innerHTML = ''; // Limpio el pie
            pieTarjeta.appendChild(divHora); // Añado solo el div de la hora
        }
    }
    
    
    const nuevoPedido = nuevoElemento.querySelector('.pedido');
    if (nuevoPedido) {
        nuevoPedido.setAttribute('data-cod-pedido', datos.cod);
        nuevoPedido.classList.add('nuevoPedido');
        setTimeout(() => {
            nuevoPedido.classList.remove('nuevoPedido');
        }, 3000);
    }
    
    // funcion que uso para cargar los detalles del pedido mediante AJAX
    fetch(`obtenerDetallesPedido.php?cod=${datos.cod}`)
        .then(response => {
            if (!response.ok) {
                // Log the raw text of the 404 response to see if it's HTML or our JSON error
                response.text().then(text => {
                    console.error('Raw 404 response text for new pedido ' + datos.cod + ':', text);
                });
                throw new Error(`HTTP error: ${response.status}`);
            }
            return response.json();
        })
        .then(detalles => {
            const contenedorDetalles = nuevoElemento.querySelector('.detallesPedido');
            if (!contenedorDetalles) {
                console.error('No se encontró el contenedor de detalles');
                return;
            }
            
            // creo contenedor de productos
            const listaProductos = document.createElement('ul');
            listaProductos.className = 'space-y-3';
            
            // añado cada producto
            if (detalles.productos && Array.isArray(detalles.productos)) {
                detalles.productos.forEach(producto => {
                    if (!producto) return; // ignoramos productos nulos
                    
                    // uso la plantilla para productos
                    const plantillaProducto = document.getElementById('plantillaProducto');
                    if (!plantillaProducto) return;
                    
                    const nuevoProducto = document.importNode(plantillaProducto.content, true);
                    
                    // relleno datos del producto
                    const nombreElement = nuevoProducto.querySelector('.nombre');
                    if (nombreElement) nombreElement.textContent = producto.nombre;
                    
                    const cantidadElement = nuevoProducto.querySelector('.cantidad');
                    if (cantidadElement) cantidadElement.textContent = producto.cantidad;
                    
                    const productoLi = nuevoProducto.querySelector('li');
                    if (productoLi) {
                        productoLi.setAttribute('data-cod-producto', producto.codProducto);
                        productoLi.setAttribute('data-cod-pedido', datos.cod);
                    }
                    
                    // relleno observaciones si existen
                    const obsElement = nuevoProducto.querySelector('.observacionesProducto');
                    if (obsElement && producto.observaciones && producto.observaciones.trim() !== '') {
                        obsElement.textContent = producto.observaciones;
                        obsElement.classList.remove('hidden');
                    } else if (obsElement) {
                        obsElement.classList.add('hidden');
                    }
                    
                    // añado botones con sus manejadores de eventos
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
                    
                    // establezco el estado visual según el estado del producto
                    const estadoElement = nuevoProducto.querySelector('.estadoProducto');
                    if (estadoElement && productoLi) {
                        estadoElement.textContent = producto.estado || 'pendiente';
                        estadoElement.className = 'estadoProducto ml-2 px-2 py-0.5 text-xs font-medium rounded-full';
                        
                        if (producto.estado === 'preparando') {
                            productoLi.style.backgroundColor = '#fefce8';
                            estadoElement.style.cssText = 'background-color:#fef3c7; color:#92400e; border-radius:9999px; padding:2px 8px;';
                            
                            // desactivo botón preparando
                            if (btnPreparando) {
                                btnPreparando.disabled = true;
                                btnPreparando.classList.add('opacity-50', 'cursor-not-allowed');
                            }
                        } else if (producto.estado === 'listo') {
                            productoLi.style.backgroundColor = '#f0fdf4';
                            estadoElement.style.cssText = 'background-color:#dcfce7; color:#166534; border-radius:9999px; padding:2px 8px;';
                            
                            // Desactivo ambos botones
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
                    
                    // añado el producto a la lista
                    listaProductos.appendChild(nuevoProducto);
                });
            }
            
            // limpio y añado la lista al contenedor
            contenedorDetalles.innerHTML = '';
            contenedorDetalles.appendChild(listaProductos);

            // Mostrar observación general del pedido
            // El elemento 'nuevoElemento' o 'pedidoEnDOM' (dependiendo de la función) es el .pedido-card
            const pedidoCardElement = nuevoElemento.querySelector('.pedido-card') || nuevoElemento; // En agregarNuevoPedido, nuevoElemento ya es el fragmento que contiene .pedido-card
            if (pedidoCardElement) {
                const obsGeneralElement = pedidoCardElement.querySelector('.observacionGeneralPedido');
                if (obsGeneralElement && detalles.Observaciones && detalles.Observaciones.trim() !== '') {
                    obsGeneralElement.textContent = detalles.Observaciones;
                    obsGeneralElement.classList.remove('hidden');
                } else if (obsGeneralElement) {
                    obsGeneralElement.classList.add('hidden');
                }
            }
            
            // actualizo contadores de estados
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
    
    // añado el nuevo pedido al contenedor
    contenedorPedidos.insertBefore(nuevoElemento, contenedorPedidos.firstChild);
}

// funcion para actualizar el estado de un pedido
function actualizarEstadoPedido(datos) {
    const pedido = document.querySelector(`.pedido[data-cod-pedido="${datos.codPedido}"]`);
    console.log('Actualizando estado del pedido:', datos);
    
    if (pedido) {
        // los pedidos solo se ocultan cuando están en estado listo y se marca el botón Completado
        // o cuando están en estado completado
        if (datos.estado === 'listo') {
            // marco el pedidfo como listo pero no ocultarlo
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
            // si el empleado marca completado, quitamos el pedido de la vista
            pedido.classList.add('bg-green-50');
            setTimeout(() => {
                pedido.remove();
                
                // si no quedan pedidos, mostramos el mensaje de esperando pedidos
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

// cargo los pedidos pendientes al iniciar
function cargarPedidosPendientes() {
    console.log('Iniciando carga de pedidos pendientes en cocina...');
    fetch('cocina.php?action=obtenerPedidosPendientes&area=cocina')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos del servidor:', data);
            const contenedorPedidos = document.getElementById('contenedorPedidos');
            
            if (!contenedorPedidos) {
                console.error('No se encontró el contenedor de pedidos');
                return;
            }
            
            // limpio mensajes de carga
            contenedorPedidos.innerHTML = '';
            
            // verifico si hay un error en la respuesta
            if (data.error) {
                console.error('Error del servidor:', data.error);
                contenedorPedidos.innerHTML = `
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <p>Error al cargar los pedidos: ${data.error}</p>
                    </div>
                `;
                return;
            }
            
            // verifico si la respuesta tiene éxito y contiene datos
            if (!data.success) {
                console.error('Error del servidor:', data.error || 'Error desconocido');
                contenedorPedidos.innerHTML = `
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <p>Error al cargar los pedidos: ${data.error || 'Error desconocido'}</p>
                    </div>
                `;
                return;
            }
            
            // extraigo los pedidos de la respuesta
            const pedidos = Array.isArray(data.pedidos) ? data.pedidos : [];
            console.log('Pedidos a mostrar:', pedidos);
            
            // si no hay pedidos, muestro mensaje
            if (pedidos.length === 0) {
                contenedorPedidos.innerHTML = `
                    <div class="animate-pulse text-center p-6">
                        <p class="text-gray-500">No hay pedidos pendientes en este momento</p>
                    </div>
                `;
                return;
            }
            
            // para cada pedido pendiente, obtenemos sus detalles y lo mostramos
            pedidos.forEach(pedido => {
                const plantilla = document.getElementById('plantillaPedido');
                const nuevoElemento = document.importNode(plantilla.content, true);

                // aseguro que el pie de la tarjeta solo contenga la hora
                const pieTarjeta = nuevoElemento.querySelector('#pedido-card-footer');
                if (pieTarjeta) {
                    const divHora = pieTarjeta.querySelector('.text-gray-500.text-sm'); // el div que envuelve el span de la hora
                    if (divHora) {
                        pieTarjeta.innerHTML = ''; // limpio el pie
                        pieTarjeta.appendChild(divHora); // añado solo el div de la hora
                    }
                }
                
                const nuevoPedido = nuevoElemento.querySelector('.pedido');
                if (nuevoPedido) {
                    nuevoPedido.setAttribute('data-cod-pedido', pedido.cod);
                }
                
                // cargo detalles del pedido mediante AJAX
                fetch(`obtenerDetallesPedido.php?cod=${pedido.cod}`)
                    .then(response => {
                        if (!response.ok) {
                            // Log the raw text of the 404 response to see if it's HTML or our JSON error
                            response.text().then(text => {
                                console.error('Raw 404 response text for pedido ' + pedido.cod + ':', text);
                            });
                            throw new Error(`HTTP error: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(detalles => {
                        // obtengo el pedido del DOM que acabamos de añadir
                        const pedidoEnDOM = document.querySelector(`.pedido[data-cod-pedido="${pedido.cod}"]`);
                        if (!pedidoEnDOM) return;
                        
                        // contenedor de productos
                        const contenedorDetalles = pedidoEnDOM.querySelector('.detallesPedido');
                        const listaProductos = document.createElement('ul');
                        listaProductos.className = 'space-y-3';
                        
                        // añado cada producto
                        if (detalles.productos && Array.isArray(detalles.productos)) {
                            detalles.productos.forEach(producto => {
                                if (!producto) return;
                                
                                // uso la plantilla para productos
                                const plantillaProducto = document.getElementById('plantillaProducto');
                                if (!plantillaProducto) return;
                                
                                const nuevoProducto = document.importNode(plantillaProducto.content, true);
                                
                                // relleno datos del producto
                                const nombreElement = nuevoProducto.querySelector('.nombre');
                                if (nombreElement) nombreElement.textContent = producto.nombre;
                                
                                const cantidadElement = nuevoProducto.querySelector('.cantidad');
                                if (cantidadElement) cantidadElement.textContent = producto.cantidad;
                                
                                const productoLi = nuevoProducto.querySelector('li');
                                if (productoLi) {
                                    productoLi.setAttribute('data-cod-producto', producto.codProducto);
                                    productoLi.setAttribute('data-cod-pedido', pedido.cod);
                                }
                                
                                // relleno observaciones si existen
                                const obsElement = nuevoProducto.querySelector('.observacionesProducto');
                                if (obsElement && producto.observaciones) {
                                    obsElement.textContent = producto.observaciones;
                                }
                                
                                // añado botones con sus manejadores de eventos
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
                                
                                // establezco el estado visual según el estado del producto
                                const estadoElement = nuevoProducto.querySelector('.estadoProducto');
                                if (estadoElement && productoLi) {
                                    estadoElement.textContent = producto.estado || 'pendiente';
                                    estadoElement.className = 'estadoProducto ml-2 px-2 py-0.5 text-xs font-medium rounded-full';
                                    
                                    if (producto.estado === 'preparando') {
                                        productoLi.style.backgroundColor = '#fefce8';
                                        estadoElement.style.cssText = 'background-color:#fef3c7; color:#92400e; border-radius:9999px; padding:2px 8px;';

                                        // desactivo botón preparando
                                        if (btnPreparando) {
                                            btnPreparando.disabled = true;
                                            btnPreparando.classList.add('opacity-50', 'cursor-not-allowed');
                                        }
                                    } else if (producto.estado === 'listo') {
                                        productoLi.style.backgroundColor = '#f0fdf4';
                                        estadoElement.style.cssText = 'background-color:#dcfce7; color:#166534; border-radius:9999px; padding:2px 8px;';
                                        
                                        // desactivo ambos botones
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
                                
                                // añado el producto a la lista
                                listaProductos.appendChild(nuevoProducto);
                            });
                        }
                        
                        // limpio y añado la lista al contenedor
                        contenedorDetalles.innerHTML = '';
                        contenedorDetalles.appendChild(listaProductos);
                        
                        // actualizo contadores de estados
                        const contPendientes = pedidoEnDOM.querySelector('#contadorPendientes');
                        const contPreparando = pedidoEnDOM.querySelector('#contadorPreparando');
                        
                        if (contPendientes && contPreparando && detalles.contadores) {
                            contPendientes.textContent = detalles.contadores.pendientes || 0;
                            contPreparando.textContent = detalles.contadores.preparando || 0;
                        }
                        
                        // aplico estilos de estado al pedido según su estado general
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
                
                // añado el pedido al contenedor
                contenedorPedidos.appendChild(nuevoElemento);
            });
        })
        .catch(error => {
            console.error('Error al cargar pedidos pendientes:', error);
        });
}

// Variable global para indicar que gestionPedidos está activo
window.gestionPedidosActivo = false;

// Al cargar el documento, verifico si estamos en la página que usa este contenedor
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('contenedorPedidos')) {
        // Indico que gestionPedidos está activo
        window.gestionPedidosActivo = true;
        console.log('gestionPedidos.js: Activando carga de pedidos');
        
        // cargo los pedidos pendientes solo si existe el contenedor
        cargarPedidosPendientes();
    }
});
