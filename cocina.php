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
            
            // Utilizamos la función modularizada con los parámetros en el orden correcto
            $resultado = actualizarEstadoProductoCompleto($codPedido, $codProducto, $estado, $area, $numMesa, $codEmpleado);
            
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cocina</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <style>
        /* Estilos para el indicador de carga */
        #cargando {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            transition: opacity 0.3s ease-out;
        }
        
        .oculto {
            opacity: 0;
            pointer-events: none;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Reset de estilos para evitar conflictos */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f0f4f8;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 20px;
        }
        
        .mesa-title {
            color: #2d3748;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 1.5rem 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
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

    <!-- Indicador de carga -->
    <div id="cargando">
        <div class="spinner"></div>
    </div>
    
    <!-- Contenedor oculto para contadores (usado por inicializarCocina.js) -->
    <div class="hidden">
        <span id="totalPedidos">0</span>
        <span id="totalProductosPendientes">0</span>
        <span id="totalProductosListos">0</span>
    </div>

    <style>
        /* Estilos simplificados para la interfaz de cocina */
        .mesa-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin: 1.5rem 0 0.75rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .pedido-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-left: 4px solid #51B2E0;
        }
        
        .pedido-num {
            font-weight: 600;
            color: #1f2937;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .producto {
            background-color: #f9fafb;
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            border: 1px solid #e5e7eb;
        }
        
        .producto-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .producto-nombre {
            font-weight: 500;
            color: #111827;
            font-size: 1rem;
        }
        
        .producto-cantidad {
            background-color: #e5e7eb;
            color: #4b5563;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .btn-container {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .btn-estado {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .btn-preparar {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .btn-preparar:hover:not(:disabled) {
            background-color: #fde68a;
        }
        
        .btn-listo {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .btn-listo:hover:not(:disabled) {
            background-color: #bbf7d0;
        }
        
        .btn-estado:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
    
    <div class="container mx-auto p-4">
        <!-- Contenedor donde se cargarán los pedidos mediante JavaScript -->
        <div id="contenedorPedidos">
            <div class="text-center py-8">
                <div class="spinner mx-auto"></div>
                <p class="mt-4 text-gray-600">Cargando pedidos...</p>
            </div>
        </div>
    </div>
    
    <!-- Plantilla para nuevos pedidos (oculta) -->
    <template id="plantillaMesa">
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">Mesa <span class="mesa-numero"></span></h2>
            <div class="pedidos-container grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Los pedidos se insertarán aquí -->
            </div>
        </div>
    </template>
    
    <template id="plantillaPedido">
        <div class="pedido-card bg-white rounded-lg shadow-md p-4 mb-4 border-l-4 border-blue-500" data-cod-pedido="">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-800">Pedido #<span class="pedido-numero"></span></h3>
                <span class="estado-pedido text-sm font-medium px-2.5 py-0.5 rounded"></span>
            </div>
            <div class="productos-container">
                <!-- Los productos se insertarán aquí -->
            </div>
            <div class="mt-3 pt-3 border-t border-gray-100">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">Hora: <span class="hora-pedido font-medium"></span></span>
                    <button class="completar-pedido bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-1.5 px-3 rounded transition-colors">
                        Completar pedido
                    </button>
                </div>
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
    <script src="js/gestionPedidos.js"></script>
    <script src="js/cocina.js"></script>
    
    <!-- Todo el código JavaScript ha sido movido a los archivos externos -->
</body>
</html>
