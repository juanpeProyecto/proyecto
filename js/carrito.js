"use strict";

// Función para obtener la clave de carrito según la mesa
function obtenerKeyCarrito() {
    let mesa = localStorage.getItem('numMesa') || '';
    return mesa ? `carrito_${mesa}` : 'carrito';
}

// Función para eliminar un producto del carrito
function eliminarDelCarrito(indice) 
{
  let carrito = JSON.parse(localStorage.getItem(obtenerKeyCarrito())) || [];//recupero los datos del carrito
  let productoEliminado = carrito[indice]; //obtengo el producto eliminado
  carrito.splice(indice, 1);//elimino el producto del carrito
  localStorage.setItem(obtenerKeyCarrito(), JSON.stringify(carrito));//guardo los datos del carrito
  actualizarContadorCarrito();
  actualizarCarrito();
  if (productoEliminado && productoEliminado.nombre) {
    mostrarAvisoCarrito(`${productoEliminado.nombre} eliminado del carrito`, '#51B2E0');
  } else {
    mostrarAvisoCarrito('Producto eliminado del carrito', '#51B2E0');
  }
}

// Funcioin para vaciar el carrito
function vaciarCarrito() {
  let carrito = JSON.parse(localStorage.getItem(obtenerKeyCarrito())) || [];//recupero los datos del carrito
  if (carrito.length > 0) {
    localStorage.removeItem(obtenerKeyCarrito());//elimino los datos del carrito
    actualizarContadorCarrito();
    actualizarCarrito();
    mostrarAvisoCarrito('Carrito vaciado correctamente', '#51B2E0');
  } else {//si el carrito esta vacio
    localStorage.removeItem(obtenerKeyCarrito());
    actualizarContadorCarrito();
    actualizarCarrito();
  }
}

// Función para mostrar mensajes flotantes
function mostrarAvisoCarrito(mensaje, color = '#51B2E0') {
    let aviso = document.getElementById('avisoCarrito');
    if (!aviso) {
        aviso = document.createElement('div');
        aviso.id = 'avisoCarrito';
        aviso.className = 'fixed top-20 right-4 z-50 px-4 py-2 rounded-lg shadow-lg hidden transition-all duration-300';
        document.body.appendChild(aviso);
    }
    aviso.textContent = mensaje;
    aviso.style.backgroundColor = color;
    aviso.classList.remove('hidden');
    aviso.style.opacity = '1';
    setTimeout(() => {
        aviso.style.opacity = '0';
        setTimeout(() => aviso.classList.add('hidden'), 300);
    }, 1800);
}

// funcion para aniadir productos al carrito
function anadirAlCarrito(producto) {
    let carrito = JSON.parse(localStorage.getItem(obtenerKeyCarrito())) || [];//recupero los datos del carrito
    producto.cantidad = parseInt(producto.cantidad);//convierto la cantidad a numero
    if (isNaN(producto.cantidad) || producto.cantidad < 1) {
        producto.cantidad = 1;
    }
    const idx = carrito.findIndex(p => p.codProducto === producto.codProducto);//busco el producto en el carrito
    if (idx !== -1) {//si el producto existe
        carrito[idx].cantidad = Number(carrito[idx].cantidad) + producto.cantidad;//sumo la cantidad
    } else {
        carrito.push(producto);//si no existe lo añado
    }
    localStorage.setItem(obtenerKeyCarrito(), JSON.stringify(carrito));//guardo los datos del carrito
    mostrarAvisoCarrito(`${producto.nombre} añadido al carrito`, '#51B2E0');//muestro un aviso que indica que el producto ha sido añadido al carrito
    actualizarContadorCarrito();
    actualizarCarrito();
}

// funcion que se encarga de actualizar el carrito 
function actualizarCarrito()
{
    let carrito = JSON.parse(localStorage.getItem(obtenerKeyCarrito())) || [];//recupero los datos del carrito
    const contenidoPedido = document.getElementById('carritoContenido');
    const totalDiv = document.getElementById('carritoTotal');
    if (!contenidoPedido || !totalDiv) {
        return;
    }
    const btnVaciar = document.getElementById('btnVaciar');
    const btnFinalizar = document.getElementById('btnFinalizar');
    if (carrito.length === 0) 
    {
        contenidoPedido.innerHTML = '<div class="text-center text-[#256353]">El carrito está vacío.</div>';
        totalDiv.textContent = '';
        if(btnVaciar) btnVaciar.disabled = true;
        if(btnFinalizar) btnFinalizar.disabled = true;
        return;
    }
    let total = 0;
    contenidoPedido.innerHTML = ''; 
    for (let i = 0; i < carrito.length; i++) { //recorro el carrito
        const item = carrito[i];
        let precioNum = typeof item.precio === 'string'? parseFloat(item.precio.replace(',', '.').replace('€', '')) : Number(item.precio);//convierto el precio a tipo numero
        const subtotal = precioNum * item.cantidad;
        total += subtotal; //sumo el subtotal al total
        const productoDiv = document.createElement('div');
        productoDiv.className = 'flex flex-col sm:flex-row items-center justify-between bg-[#E0FAF4] rounded-xl p-4 shadow gap-2 sm:gap-4';
        productoDiv.innerHTML = `
            <div class="flex-1 w-full">
                <div class="text-base sm:text-lg font-bold text-[#256353]">${item.nombre}</div>
                <div class="text-[#21476B]">Cantidad: ${item.cantidad}</div>
                <div class="text-[#51B2E0]">Precio: ${item.precio}</div>
                <label class="block mt-2 text-[#256353] text-sm">Observaciones para este producto:
                    <input type="text" class="observacion-producto w-full rounded border p-1 mt-1 text-[#256353] bg-[#E0FAF4] border-[#72E8AC] focus:outline-none focus:border-[#51B2E0]" name="observacionesProducto[]" data-index="${i}" value="${item.observacion || ''}" placeholder="Observaciones para este producto...">
                </label>
            </div>
            <div class="mt-2 sm:mt-0 flex flex-col items-center justify-center sm:items-end sm:justify-end w-full sm:w-auto"> 
                <div class="text-[#256353] font-semibold">Subtotal: ${subtotal.toFixed(2).replace('.', ',') }€</div>
                <button class="btnEliminarProducto flex items-center justify-center gap-2 bg-[#51B2E0] hover:bg-[#72E8AC] text-white hover:text-[#256353] px-4 py-2 rounded-lg font-semibold shadow transition-all duration-200 w-full sm:w-auto mt-2" data-index="${i}">
                    <span class="material-symbols-outlined">delete</span>
                    <span class="hidden sm:inline">Eliminar</span>
                </button>
            </div>
        `;//tofixed sirve para redondear a 2 decimales y replace para cambiar el caracter . por ,
        contenidoPedido.appendChild(productoDiv);
    }
    totalDiv.textContent = `Total: ${total.toFixed(2).replace('.', ',')}€`;//muestro el total

    let botonesEliminar = contenidoPedido.querySelectorAll('.btnEliminarProducto');
    for (let i = 0; i < botonesEliminar.length; i++) { //recorro los botones eliminar 
        botonesEliminar[i].addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));//obtengo el indice del producto
            eliminarDelCarrito(index);
        });
    }

    if(btnVaciar) {
        btnVaciar.disabled = false;
        btnVaciar.onclick = function() {
            vaciarCarrito();
        };
    }
}

// funcion que se encarga de obtener el numero de la mesa
(function obtenerNumMesa() {
    // Solo usamos localStorage para gestionar el número de mesa
    let parametrosUrl = new URLSearchParams(window.location.search); //obtengo los parametros de la url
    let numMesaUrl = parametrosUrl.get('numMesa'); //obtengo el numero de mesa de la url
    if (numMesaUrl) {
        localStorage.setItem('numMesa', numMesaUrl); //guardo el numero de mesa en localStorage
    }
    // Si no hay numMesa en localStorage, el usuario no debería poder continuar
})();

// funcion que se encarga de iniciializar el carrito al cargar el documento
document.addEventListener('DOMContentLoaded', function() {
    actualizarCarrito();
    actualizarContadorCarrito();
    const formPedido = document.getElementById('formPedido');
    const formNumMesa = document.getElementById('formNumMesa');
    const formTotal = document.getElementById('formTotal');
    const btnFinalizar = document.getElementById('btnFinalizar');
   
    if (btnFinalizar && formPedido) {
         // evento que se encarga de finalizar el pedido
        btnFinalizar.addEventListener('click', function(evento) {
            evento.preventDefault(); // evito que se envie el formulario
            // Guardo las observaciones de producto en el localStorage antes de enviar
            let carrito = JSON.parse(localStorage.getItem(obtenerKeyCarrito())) || [];//recupero el carrito o un array vacio si no existe
            let observacionesInputs = document.querySelectorAll('.observacion-producto');
            observacionesInputs.forEach(function(input) {
                let i = parseInt(input.getAttribute('data-index')); //obtengo el indice del producto
                if (!isNaN(i)) {
                    carrito[i].observacion = input.value;
                }
            });
            localStorage.setItem(obtenerKeyCarrito(), JSON.stringify(carrito));
            actualizarCarrito();
            // Creo inputs ocultos para observaciones por producto 
            let formObservacionesPorProducto = document.getElementById('formObservacionesPorProducto');
            formObservacionesPorProducto.innerHTML = '';
            carrito.forEach(function(item) { //recorro el carrito
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'observacionesProducto[]';
                input.value = item.observacion || '';
                formObservacionesPorProducto.appendChild(input);
            });
            //asigno el numero de mesa al formulario
            if (formNumMesa) {
                let mesa = localStorage.getItem('numMesa') || '';
                if (!mesa) {
                    alert('No hay número de mesa válido. No se puede enviar el pedido.');
                    return;
                }
                formNumMesa.value = mesa;
            }

            // Creo inputs ocultos para cada producto antes de enviar
            const formProductos = document.getElementById('formProductos');
            if (formProductos) {
                formProductos.innerHTML = '';//vacío el formulario
                carrito.forEach(function(prod) {
                    // Codigo del producto
                    const inputCodigo = document.createElement('input');
                    inputCodigo.type = 'hidden';
                    inputCodigo.name = 'codProducto[]';
                    inputCodigo.value = prod.codProducto;
                    formProductos.appendChild(inputCodigo);

                    // Cantidad del producto
                    const inputCantidad = document.createElement('input');
                    inputCantidad.type = 'hidden';
                    inputCantidad.name = 'cantidad[]';
                    inputCantidad.value = prod.cantidad;
                    formProductos.appendChild(inputCantidad);

                    // Precio unitario
                    const inputPrecio = document.createElement('input');
                    inputPrecio.type = 'hidden';
                    inputPrecio.name = 'precioUnitario[]';
                    inputPrecio.value = prod.precio;
                    formProductos.appendChild(inputPrecio);
                });
            }
            // Calculo el total
            if (formTotal) {
                let total = 0;
                for (let i = 0; i < carrito.length; i++) {
                    let precioNum = typeof carrito[i].precio === 'string'
                        ? parseFloat(String(carrito[i].precio).replace(',', '.').replace('€', ''))//quito los caracteres no numericos
                        : Number(carrito[i].precio);//si es un numero lo convierto a numero
                    total += precioNum * parseInt(carrito[i].cantidad);//sumo el precio del producto por la cantidad
                }
                formTotal.value = total.toFixed(2);//asigno el total al input oculto
            }

            formPedido.submit(); //envio el formulario
            localStorage.removeItem(obtenerKeyCarrito());
            actualizarContadorCarrito();
        });
    }
}); 
// evento para añadir un producto al carrito
document.body.addEventListener('click', function(evento) {
    if (evento.target.classList.contains('btnAgregar') || (evento.target.closest && evento.target.closest('.btnAgregar'))) {//si el elemento tiene la clase btnAgregar o si el elemento es un hijo de un elemento con la clase btnAgregar
        const boton = evento.target.closest('.btnAgregar');
        const codProducto = boton.getAttribute('data-codigo');
        const nombre = boton.getAttribute('data-nombre');
        const precio = boton.getAttribute('data-precio');
        const cantidadInput = document.getElementById('cantidad-' + codProducto);
        const cantidad = cantidadInput ? cantidadInput.value : 1;

        const producto = {
            codProducto: codProducto,
            nombre: nombre,
            precio: precio,
            cantidad: cantidad
        };

        anadirAlCarrito(producto);
    }
});

// funcion para actualizar el contador del carrito
function actualizarContadorCarrito() {
    const carrito = JSON.parse(localStorage.getItem(obtenerKeyCarrito())) || [];
    const contador = document.getElementById('contadorCarrito');
    if (contador) {
        let totalItems = 0;
        carrito.forEach(item => totalItems += parseInt(item.cantidad));
        contador.textContent = totalItems;
    }
}
