<?php
    require_once "funciones.php";
    require_once('bd.php');
    
    // Procesador de API unificado para cocina
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || 
        (isset($_GET['action']) || isset($_POST['action']) || $_SERVER['REQUEST_METHOD'] === 'POST')) {
        
        // Determinar la acción solicitada
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        // Si no hay acción explícita pero es un POST con ciertos parámetros, inferimos la acción
        if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['codPedido']) && isset($_POST['estado'])) {
                $action = 'cambiarEstadoPedido';
            } else if (file_get_contents('php://input')) {
                // Probablemente JSON, intentar decodificar para determinar la acción
                $data = json_decode(file_get_contents('php://input'), true);
                if (isset($data['codPedido']) && isset($data['codProducto']) && isset($data['estado'])) {
                    $action = 'actualizarEstadoProducto';
                } else if (isset($data['codPedido']) && isset($data['estado'])) {
                    $action = 'cambiarEstadoPedido';
                }
            }
        } else if (empty($action) && isset($_GET['area'])) {
            $action = 'obtenerPedidosPendientes';
        }
        
        // Establecer cabecera para respuesta JSON
        header('Content-Type: application/json');
        
        switch ($action) {
            case 'actualizarEstadoProducto':
                procesarActualizarEstadoProducto();
                exit;
                
            case 'cambiarEstadoPedido':
                procesarCambiarEstadoPedido();
                exit;
                
            case 'obtenerPedidosPendientes':
                procesarObtenerPedidosPendientes();
                exit;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
                exit;
        }
    }
    
    // Funciones de procesamiento de API
    
    function procesarActualizarEstadoProducto() {
        // Prevenir cualquier salida que no sea JSON
        ob_start();
        
        // Configurar errores para que no se muestren en la salida
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        ini_set('error_log', 'debug_cocina.log');
        
        try {
            // Recibimos los datos POST
            $input = file_get_contents('php://input');
            error_log("JSON recibido: " . $input);
            
            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error al decodificar JSON: " . json_last_error_msg());
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Error al decodificar JSON']);
                exit;
            }
            
            // Verificamos que tengamos los datos necesarios
            if (!isset($data['codPedido']) || !isset($data['codProducto']) || !isset($data['estado'])) {
                error_log("Faltan datos necesarios en la solicitud");
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Faltan datos necesarios']);
                exit;
            }
            
            $codPedido = (int)$data['codPedido'];
            $codProducto = (int)$data['codProducto'];
            $estado = $data['estado'];
            $area = $data['area'] ?? 'cocina';
            $numMesa = $data['numMesa'] ?? 0;
            $codEmpleado = $data['codEmpleado'] ?? 0;
            
            error_log("Procesando actualización para pedido: $codPedido, producto: $codProducto, estado: $estado, area: $area, numMesa: $numMesa");
            
            // Utilizamos la función modularizada
            $resultado = actualizarEstadoProductoCompleto($codPedido, $codProducto, $estado, $area);
            
            // Si se requirió una conexión nueva, también actualizar la variable global
            global $conexion;
            if (!$conexion && isset($resultado['conexion'])) {
                $conexion = $resultado['conexion'];
                unset($resultado['conexion']);
            }
            
            // Intentamos enviar notificación WebSocket directamente desde aquí como respaldo
            try {
                // Asegurarnos de que la función esté disponible
                if (!function_exists('enviarNotificacionWebSocket')) {
                    require_once __DIR__ . '/enviarNotificacion.php';
                }
                
                $datosNotificacion = [
                    'tipo' => 'actualizacionEstadoProducto',
                    'codPedido' => $codPedido,
                    'codProducto' => $codProducto,
                    'estado' => $estado,
                    'area' => $area,
                    'numMesa' => $numMesa
                ];
                
                error_log("Enviando notificación de respaldo desde cocina.php: " . json_encode($datosNotificacion));
                enviarNotificacionWebSocket($datosNotificacion);
            } catch (\Exception $e) {
                error_log("Error al enviar notificación de respaldo: " . $e->getMessage());
                // No afectar la respuesta principal
            }
            error_log("Resultado de actualización: " . print_r($resultado, true));
            
            // Limpiar cualquier salida previa
            ob_end_clean();
            
            // Asegurarnos de que la salida sea JSON válido
            header('Content-Type: application/json');
            echo json_encode($resultado);
            
        } catch (Exception $e) {
            error_log("Excepción en procesarActualizarEstadoProducto: " . $e->getMessage());
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
        }
    }
    
    function procesarCambiarEstadoPedido() {
        // Determinar si es POST o JSON
        if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
            $data = json_decode(file_get_contents('php://input'), true);
            $codPedido = isset($data['codPedido']) ? $data['codPedido'] : 0;
            $estado = isset($data['estado']) ? $data['estado'] : '';
        } else {
            $codPedido = isset($_POST['codPedido']) ? (int)$_POST['codPedido'] : 0;
            $estado = isset($_POST['estado']) ? $_POST['estado'] : '';
        }
        
        if (!$codPedido || !$estado) {
            echo json_encode(['success' => false, 'error' => 'Parámetros incompletos']);
            exit;
        }
        
        // Estados válidos
        $estadosValidos = ['pendiente', 'preparando', 'listo', 'entregado', 'completado'];
        if (!in_array($estado, $estadosValidos)) {
            echo json_encode(['success' => false, 'error' => 'Estado no válido']);
            exit;
        }
        
        // Utilizamos la función modularizada
        $resultado = cambiarEstadoPedido($codPedido, $estado);
        echo json_encode($resultado);
    }
    
    function procesarObtenerPedidosPendientes() {
        // Prevenir cualquier salida que no sea JSON
        ob_start();
        
        // Configurar errores para que no se muestren en la salida
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        ini_set('error_log', 'debug_cocina.log');
        
        try {
            // Obtenemos el área para filtrar pedidos específicos
            $area = isset($_GET['area']) ? $_GET['area'] : '';
            
            error_log("Obteniendo pedidos pendientes para área: $area");
            
            // Utilizamos la función modularizada
            $resultado = obtenerPedidosPendientesArea($area);
            
            // Limpiar cualquier salida previa
            ob_end_clean();
            
            // Asegurarnos de que la salida sea JSON válido
            header('Content-Type: application/json');
            echo json_encode($resultado);
            
        } catch (Exception $e) {
            error_log("Excepción en procesarObtenerPedidosPendientes: " . $e->getMessage());
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
        }
    }
    
    // A partir de aquí sigue el código normal de la página
    require "cabeceraTrabajador.php";
    //comprobar_rol(["cocina"]);
    $conexion = conectarBD();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cocina</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <style>
        body {
            background-color: #e6f5f0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .mesa-title {
            background-color: rgba(255,255,255,0.8);
            border-radius: 8px;
            padding: 10px 15px;
            font-weight: 600;
            color: #226855;
            font-size: 18px;
            margin-bottom: 20px;
            margin-top: 10px;
        }
        .pedido-card {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .pedido-num {
            background-color: #4ade80;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
        }
        .estado-chip {
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
        }
        .pendiente {
            background-color: #fde4e0;
            color: #f97066;
        }
        .preparando {
            background-color: #fff2c6;
            color: #eab308;
        }
        .producto-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .cantidad {
            color: #6b7280;
            margin-bottom: 15px;
        }
        .btn-container {
            display: flex;
            gap: 10px;
        }
        .btn-preparando {
            background-color: #86efac;
            color: #14532d;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 8px;
            flex: 1;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-preparando.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .btn-listo {
            background-color: #38bdf8;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 8px;
            flex: 1;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-listo.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .pedidos-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            padding: 0 10px;
        }
        .material-symbols-outlined {
            font-size: 18px;
            margin-right: 4px;
        }
    </style>
</head>
<body>
    <?php
    // Función para definir la clase CSS según estado
    function getEstadoClass($estado) {
        switch ($estado) {
            case 'pendiente':
                return 'pendiente';
            case 'preparando':
                return 'preparando';
            default:
                return 'pendiente';
        }
    }
    ?>

    <!-- Contenedor oculto para contadores (usado por inicializarCocina.js) -->
    <div class="hidden">
        <span id="totalPedidos">0</span>
        <span id="totalProductosPendientes">0</span>
        <span id="totalProductosListos">0</span>
    </div>

    <div class="container mx-auto p-4">
        <?php
        // Obtener las mesas distintas con pedidos activos que tengan productos pendientes o en preparación
        $sqlMesas = "SELECT p.numMesa 
                    FROM pedidos p 
                    INNER JOIN DetallePedidos dp ON p.codPedido = dp.codPedido 
                    INNER JOIN Productos pr ON dp.codProducto = pr.codProducto 
                    WHERE p.estado IN ('pendiente', 'preparando')
                    AND pr.QuienLoAtiende = 'cocinero'
                    AND dp.estado IN ('pendiente', 'preparando')
                    GROUP BY p.numMesa
                    HAVING COUNT(dp.codPedido) > 0
                    ORDER BY p.numMesa";
        $resultMesas = $conexion->query($sqlMesas);
    
        if ($resultMesas && $resultMesas->num_rows > 0) {
            while ($mesa = $resultMesas->fetch_assoc()) {
                echo "<div class='mesa-title'>Mesa {$mesa['numMesa']}</div>";
                echo "<div class='pedidos-container'>";
                
                // Obtener pedidos de esta mesa que tengan al menos un producto para cocina en estado pendiente o preparando
                $sqlPedidos = "SELECT p.codPedido, p.numMesa, p.estado, p.Fecha 
                          FROM pedidos p 
                          INNER JOIN DetallePedidos dp ON p.codPedido = dp.codPedido
                          INNER JOIN Productos pr ON dp.codProducto = pr.codProducto
                          WHERE p.numMesa = {$mesa['numMesa']} 
                          AND p.estado IN ('pendiente', 'preparando')
                          AND dp.estado IN ('pendiente', 'preparando')
                          AND pr.QuienLoAtiende = 'cocinero'
                          GROUP BY p.codPedido";
                $resultPedidos = $conexion->query($sqlPedidos);
                
                if ($resultPedidos && $resultPedidos->num_rows > 0) {
                    while ($pedido = $resultPedidos->fetch_assoc()) {
                        $estadoClass = getEstadoClass($pedido['estado']);
                        
                        echo "<div class='pedido-card' data-cod-pedido='{$pedido['codPedido']}'>";
                        echo "<div class='pedido-header'>";
                        echo "<div class='pedido-num'>Pedido #{$pedido['codPedido']}</div>";
                        echo "</div>";
                        
                        // Obtener productos del pedido que debe atender el cocinero
                        $sqlProductos = "SELECT dp.codProducto, dp.codPedido, dp.cantidad, dp.estado as estadoProducto, 
                                     pr.nombre, pr.QuienLoAtiende
                                     FROM DetallePedidos dp 
                                     INNER JOIN Productos pr ON dp.codProducto = pr.codProducto 
                                     WHERE dp.codPedido = {$pedido['codPedido']} 
                                     AND pr.QuienLoAtiende = 'cocinero' 
                                     AND dp.estado IN ('pendiente', 'preparando')";
                        $resultProductos = $conexion->query($sqlProductos);
                        
                        if ($resultProductos && $resultProductos->num_rows > 0) {
                            while ($producto = $resultProductos->fetch_assoc()) {
                                echo "<div class='producto' data-cod-producto='{$producto['codProducto']}' data-cod-pedido='{$pedido['codPedido']}'>";
                                echo "<div class='producto-name'>{$producto['nombre']}</div>";
                                echo "<div class='cantidad'>Cantidad: {$producto['cantidad']}</div>";
                                echo "<div class='btn-container'>";
                                
                                // Estado del producto para habilitar/deshabilitar botones
                                $estadoProducto = $producto['estadoProducto'];
                                $btnPreparandoDisabled = ($estadoProducto !== 'pendiente') ? 'disabled' : '';
                                $btnListoDisabled = ($estadoProducto === 'listo') ? 'disabled' : '';
                                
                                // Clase CSS para botones deshabilitados
                                $btnPreparandoClass = $btnPreparandoDisabled ? 'disabled' : '';
                                $btnListoClass = $btnListoDisabled ? 'disabled' : '';
                                
                                echo "<button 
                                    data-estado='preparando' 
                                    data-cod-pedido='{$pedido['codPedido']}' 
                                    data-cod-producto='{$producto['codProducto']}' 
                                    data-num-mesa='{$pedido['numMesa']}' 
                                    class='btn-preparando btn-estado-producto {$btnPreparandoClass}' 
                                    {$btnPreparandoDisabled}>
                                    <span class='material-symbols-outlined'>cooking</span> Preparando
                                  </button>";
                                
                                echo "<button 
                                    data-estado='listo' 
                                    data-cod-pedido='{$pedido['codPedido']}' 
                                    data-cod-producto='{$producto['codProducto']}' 
                                    data-num-mesa='{$pedido['numMesa']}' 
                                    class='btn-listo btn-estado-producto {$btnListoClass}' 
                                    {$btnListoDisabled}>
                                    <span class='material-symbols-outlined'>check_circle</span> Listo
                                  </button>";
                                
                                echo "</div>"; // Fin btn-container
                                echo "</div>"; // Fin producto
                            }
                        } else {
                            echo "<div class='text-gray-500 italic p-4'>No hay productos para cocina en este pedido</div>";
                        }
                        
                        // Botón para completar pedido
                        echo "<button class='btnCompletado bg-[#256353] hover:bg-[#1e5144] text-white px-4 py-2 rounded-md transition-colors duration-200 w-full flex items-center justify-center mt-3'>";
                        echo "<span class='material-symbols-outlined mr-2'>done_all</span>";
                        echo "<span class='font-medium'>Completar pedido</span>";
                        echo "</button>";
                        
                        echo "</div>"; // Cierre de la tarjeta del pedido
                    }
                } else {
                    echo '<div class="col-span-3 animate-pulse text-center p-6">'; 
                    echo '<p class="text-gray-500">No hay pedidos pendientes para cocina</p>';
                    echo '</div>';
                }
                echo "</div>"; // Cierre del contenedor de pedidos por mesa
            }
        } else {
            echo '<div class="col-span-3 bg-red-100 p-4 rounded-lg">';
            echo '<p class="text-red-500">No hay mesas con pedidos activos para cocina</p>';
            echo '</div>';
        }
        ?>
        </div>
    </div>
    
    <!-- Plantilla para nuevos pedidos (oculta) -->
    <template id="plantillaPedido">
        <div class="pedido bg-white rounded-lg shadow-md p-4 mb-4 border-l-4 border-[#51B2E0]">
            <div class="flex justify-between items-start mb-3">
                <div class="flex items-center">
                    <h2 class="text-xl font-bold codPedido">Pedido #0</h2>
                    <span class="ml-2 timestamp text-sm text-gray-500">
                        <span class="material-symbols-outlined text-xs align-middle">schedule</span>
                        00:00
                    </span>
                </div>
                <span class="numMesa bg-gray-100 text-gray-800 text-xs font-medium rounded-full px-2 py-1">Mesa 0</span>
            </div>
            <div class="detallesPedido py-2 space-y-3"></div>
            <div class="flex justify-end mt-4">
                <button class="btnCompletado bg-[#72E8AC] text-[#256353] px-3 py-1 rounded-md hover:bg-[#51B2E0] hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-sm align-middle mr-1">check_circle</span> Completado
                </button>
            </div>
        </div>
    </template>
    
    <!-- Plantilla para productos (oculta) -->
    <template id="plantillaProducto">
        <li class="producto bg-gray-50 p-3 rounded-lg flex flex-col border-l-2 border-gray-300">
            <div class="flex justify-between items-center mb-1">
                <div class="flex items-center">
                    <span class="cantidad bg-[#51B2E0] text-white px-2 py-0.5 rounded-full text-xs mr-2">1</span>
                    <span class="nombre font-medium">Producto</span>
                </div>
                <span class="precio text-sm text-gray-500">0.00 €</span>
            </div>
            <div class="text-xs italic mt-1 flex items-start observacionesProducto">
                <i class="fas fa-comment-alt text-gray-500 mr-1 mt-0.5"></i>
                <span>Observaciones del producto</span>
            </div>
        </li>
    </template>
    
    <!-- Scripts JavaScript unificados -->
    <script src="js/cocina.js"></script>
    <script src="js/gestionPedidos.js"></script>
    
    <!-- Todo el código JavaScript ha sido movido a los archivos externos -->
</body>
</html>
