<?php
session_start();
if (isset($_GET['numMesa'])) {//si se ha especificado un número de mesa en la URL
    $numMesa = $_GET['numMesa'];//obtengo el número de mesa de la URL
    $_SESSION['numMesa'] = $numMesa; // Guardo el numero de la mesa en una variable de sesion
} elseif (isset($_SESSION['numMesa'])) {//si no se ha especificado un número de mesa en la URL, pero si existe en la sesión
    $numMesa = $_SESSION['numMesa'];//obtengo el número de mesa de la sesión
} else {
    // Si no hay número de mesa, redirijo al menú
    header('Location: menu.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <title>Carrito de compras</title>
</head>
<body class="bg-gradient-to-br from-[#E0FAF4] via-[#72E8D4] to-[#51B2E0] min-h-screen w-full overflow-x-hidden px-1 sm:px-0">
    <a href="menu.php" class="fixed top-4 left-4 z-50 flex items-center gap-2 bg-[#51B2E0] hover:bg-[#72E8AC] text-white hover:text-[#256353] transition-all duration-200 px-4 py-2 rounded-xl shadow-lg font-semibold text-lg border-2 border-[#51B2E0]">
        <span class="material-symbols-outlined">arrow_back</span>
        <span class="hidden sm:inline">Volver al menú</span>
    </a>
    <header class="mb-6 md:mb-10">
        <div class="w-full flex justify-center">
            <img src="img/logo-proyecto.png" class="max-w-[100px] sm:max-w-[280px] w-full h-auto" alt="Logo del restaurante">
        </div>
    </header>
    <main>
        <div class="w-full max-w-xs sm:max-w-lg md:max-w-2xl xl:max-w-4xl mx-auto mt-0 p-2 sm:p-4 md:p-6 rounded-2xl shadow-lg bg-white/80">
            <h1 class="text-3xl font-bold text-[#256353] text-center mb-8">Tu carrito</h1>
            <div id="carritoContenido" class="space-y-4"></div> 
            <div id="carritoTotal" class="text-right text-xl font-bold text-[#21476B] mt-8"></div>
            <div class="mt-8">
                <form id="formPedido" action="confirmarPedido.php" method="POST"><!--envio el formulario a confirmarPedido.php con los datos del carrito-->
                    <label for="observaciones" class="block text-[#256353] font-semibold mb-2">Observaciones para el pedido (alergias,ncesidades especiales....)</label>
                    <textarea id="observaciones" rows="3" class="w-full rounded-xl border-2 border-[#72E8AC] bg-[#E0FAF4] text-[#256353] p-3 focus:outline-none focus:border-[#51B2E0] resize-none" placeholder="Escribe aquí tus observaciones..."></textarea>
            
                    <input type="hidden" name="numMesa" id="formNumMesa">
                    <input type="hidden" name="observaciones" id="formObservaciones">
                    <input type="hidden" name="total" id="formTotal">
                    <div id="formProductos"></div>
                    <div id="formObservacionesPorProducto"></div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-between mt-8">
                        <button id="btnVaciar" type="button" class="flex items-center justify-center gap-2 bg-[#51B2E0] hover:bg-[#72E8AC] text-white hover:text-[#256353] px-6 py-2 rounded-lg font-semibold shadow transition-all duration-200 w-full sm:w-auto"><span class="material-symbols-outlined hidden sm:inline">delete</span>Vaciar carrito</button>
                        <button id="btnFinalizar" type="submit" class="flex items-center justify-center bg-[#51B2E0] hover:bg-[#72E8AC] text-white hover:text-[#256353] px-6 py-2 rounded-lg font-semibold shadow w-full sm:w-auto">Finalizar pedido</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </main>
</body>
<script src="js/carrito.js"></script>
</html>
