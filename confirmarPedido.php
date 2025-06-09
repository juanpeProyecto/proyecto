<?php
require_once "funciones.php";

// inicializo 2 variables para la conexion y el codigo del pedido
$conexion = null;
$codPedido = null;

try {
    // Obtengo los datos del formulario
    $numMesa = isset($_POST['numMesa']) ? trim((string)$_POST['numMesa']) : null; 
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : ''; 
    $total = isset($_POST['total']) ? floatval(str_replace([',', '€'], ['.', ''], $_POST['total'])) : 0; 
    $codProductoArr = isset($_POST['codProducto']) ? (array)$_POST['codProducto'] : []; 
    $cantidadArr = isset($_POST['cantidad']) ? (array)$_POST['cantidad'] : []; 
    $precioUnitarioArr = isset($_POST['precioUnitario']) ? (array)$_POST['precioUnitario'] : []; 
    $observacionesProductoArr = isset($_POST['observacionesProducto']) ? (array)$_POST['observacionesProducto'] : []; // observaciones por producto

    // valido los datos
    if (!$numMesa || empty($codProductoArr) || empty($cantidadArr) || empty($precioUnitarioArr)) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="src/output.css" rel="stylesheet">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
        <title>Confirmar Pedido</title>
    </head>
    <body class="bg-gradient-to-br from-[#E0FAF4] via-[#72E8D4] to-[#51B2E0] min-h-screen flex items-center justify-center p-4">
        <div class="bg-white/90 rounded-2xl shadow-lg p-8 max-w-lg w-full text-center">
            <span class="material-symbols-outlined text-5xl text-[#E57373] mb-4">error</span>
            <h2 class="text-2xl font-bold text-[#E57373] mb-4">Error al procesar el pedido</h2>
            <p class="text-[#256353] mb-6">
                <?php 
                if (!$numMesa) {
                    echo 'No se ha especificado un número de mesa válido.';
                } else {
                    echo 'Datos de productos incompletos. No se pudo procesar el pedido.';
                }
                ?>
            </p>
            <a href="carrito.php<?php echo isset($numMesa) ? '?numMesa='.urlencode($numMesa) : '';?>" class="inline-flex items-center justify-center gap-2 bg-[#51B2E0] hover:bg-[#72E8AC] text-white hover:text-[#256353] px-6 py-2 rounded-lg font-semibold shadow transition-all duration-200">
                <span class="material-symbols-outlined">arrow_back</span>
                Volver al carrito
            </a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

    // valido los datos
    if (!$numMesa || empty($codProductoArr) || empty($cantidadArr) || empty($precioUnitarioArr)) {
        throw new Exception("Datos del formulario incompletos");
    }

    // preparo el array de productos para la funcion insertarPedido
    $productos = [];

    // valido que los arrays tengan la misma longitud
    if (count($codProductoArr) !== count($cantidadArr) || 
        count($codProductoArr) !== count($precioUnitarioArr)) {
        throw new Exception("Error: Los datos de prodductos estan incompletos o son incorrectos");
    }

    // Creo un array de productos  y añado los datos de cada producto
    for ($i = 0; $i < count($codProductoArr); $i++) {
        $productos[] = [
            'codProducto' => $codProductoArr[$i],
            'cantidad' => $cantidadArr[$i],
            'precioUnitario' => floatval(str_replace([',', '€'], ['.', ''], $precioUnitarioArr[$i])),
            'observaciones' => $observacionesProductoArr[$i] ?? ''
        ];
    }

    // inserto el pedido en la base de datos
    $resultado = insertarPedido($numMesa, $observaciones, $total, $productos);

    // verifico si hubo un error al insertar el pedido y si lo ha habido lanzo una excepcion de error
    if (!$resultado['success']) {
        throw new Exception($resultado['error']);
    }

    $codPedido = $resultado['codPedido'];
    $conexion = conectarBD(); 
    
    // inicio una transaccion para que si falla algo no se haga ningun cambio
    $conexion->begin_transaction();

    // envio el evento WebSocket a cocina para recarga en tiempo real
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        // preparo el evento WebSocket para nuevo pedido
        
        $wsClient = new \WebSocket\Client("ws://localhost:8080");
        
        // preparo el mensaje como JSON
        $mensaje = json_encode([
            "tipo" => "nuevoPedido",
            "cod" => $codPedido,
            "numMesa" => $numMesa,
            "timestamp" => time()
        ]);
        
        // envio el mensaje con el pedido que ha realizado el cliente
        $wsClient->send($mensaje);
        
        // cierro la conexion
        $wsClient->close();
    } catch (Exception $e) {
       echo "Error al enviar el mensaje: " . $e->getMessage(); //si hay un error al enviar el mensaje lo muestro para avisar al usuario
    }

    $conexion->commit(); //confirmo la transaccion si todo ha salido bien
    
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="src/output.css" rel="stylesheet">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
        <title>Pedido realizado</title>
    </head>
    <body class="bg-gradient-to-br from-[#E0FAF4] via-[#72E8D4] to-[#51B2E0] min-h-screen flex items-center justify-center p-4">
        <div class="bg-white/90 rounded-2xl shadow-lg p-8 max-w-lg w-full text-center">
            <div class="flex justify-center mb-6">
                <span class="material-symbols-outlined text-6xl text-[#72E8AC]">check_circle</span>
            </div>
            <h2 class="text-2xl font-bold text-[#256353] mb-6">¡Pedido realizado con éxito!</h2>
            
            <div class="bg-[#E0FAF4] rounded-xl p-6 mb-6 shadow-inner">
                <div class="flex items-center justify-between mb-4 border-b border-[#72E8AC]/30 pb-2">
                    <span class="text-[#256353] font-semibold">Número de mesa:</span>
                    <span class="font-bold text-[#51B2E0] text-xl"><?= htmlspecialchars($numMesa) ?></span>
                </div>
                
                <div class="flex items-center justify-between mb-4 border-b border-[#72E8AC]/30 pb-2">
                    <span class="text-[#256353] font-semibold">Total:</span>
                    <span class="font-bold text-[#51B2E0] text-xl"><?= number_format($total, 2, ',', '.') ?>€</span>
                </div>
                
                <?php if (!empty($observaciones)): ?>
                <div class="mt-4 text-left">
                    <p class="text-[#256353] font-semibold mb-2">Observaciones:</p>
                    <p class="italic text-[#21476B] bg-white/70 p-3 rounded-lg"><?= htmlspecialchars($observaciones) ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="menu.php?numMesa=<?= urlencode($numMesa) ?>" class="inline-flex items-center justify-center gap-2 bg-[#51B2E0] hover:bg-[#72E8AC] text-black hover:text-[#fff] px-6 py-3 rounded-lg font-semibold transition-all duration-200">
                    <span class="material-symbols-outlined">restaurant_menu</span>
                    Volver al menú
                </a>
            </div>
            
        </div>
        <script src="js/confirmarPedido.js?v=<?php echo time(); ?>"></script>
    </body>
    </html>
    <?php
} catch (Exception $e) { //si hay un error vuelvvo a la pagina de carrito sin hacer ningun cambio
    if ($conexion && $conexion instanceof mysqli) { 
        $conexion->rollback(); //si falla la transaccion no hacemos ningun cambio
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="src/output.css" rel="stylesheet">
        <title>Error en el pedido</title>
    </head>
    <body class="bg-gradient-to-br from-[#E0FAF4] via-[#72E8D4] to-[#51B2E0] min-h-screen flex items-center justify-center">
    <div class="bg-white/90 rounded-2xl shadow-lg p-8 max-w-lg w-full text-center">
        <h2 class="text-2xl font-bold text-[#E57373] mb-4">Error al procesar el pedido</h2>
        <p class="text-[#256353] mb-6"><?= htmlspecialchars($e->getMessage()) ?></p>
        <a href="carrito.php" class="text-white bg-[#51B2E0] hover:bg-[#72E8AC] hover:text-[#256353] px-6 py-2 rounded-lg font-semibold shadow transition-all duration-200">Volver al carrito</a>
    </div>
    </body>
    </html>
    <?php
}
$conexion->close();
