<?php
    //aqui lo que hago es comprobar si la peticion es ajax
    $esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
              (isset($_GET['action']) || isset($_POST['action']) || $_SERVER['REQUEST_METHOD'] === 'POST');
    
    if ($esAjax) { //si es ajax 
        ob_start();//inicializo el buffer
        ini_set('display_errors', 0); //deshabilito la salida de errores
        error_reporting(0); //deshabilito la reporte de errores

        // Registra una función para capturar errores fatales y devolver un formato JSON
        register_shutdown_function(function() {
            $error = error_get_last();
            // Verifica si ocurrió algún errorr
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
                // Limpio cualquier buffer de salida si aún está activo
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                // Si las cabeceras no se han enviado, establezco las de JSON
                if (!headers_sent()) {
                    header('Content-Type: application/json');
                    http_response_code(500); // Error interno del servidor
                }
                
                // Creo un mensaje de error JSON
                $errorMessage = "Error fatal en el servidor: " . $error['message'] . " en " . $error['file'] . " línea " . $error['line'];
                // Intento codificar el mensaje de error a JSON
                $jsonError = json_encode(['success' => false, 'error' => $errorMessage]);
                
                if ($jsonError === false) {
                    // Si la codificación del mensaje de error falla, enviar un error genérico
                    echo '{"success":false,"error":"Error fatal en el servidor y error al codificar el mensaje de error."}';
                } else {
                    echo $jsonError;
                }
                
                // Registro el error detallado en el log del servidor para depuración interna
                error_log("AJAX Fatal Error (cocina.php): " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
            }
        });
    }
    
    require_once "funciones.php";
    require_once('bd.php');
    
    // Procesador de API unificado para cocina
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || 
        (isset($_GET['action']) || isset($_POST['action']) || $_SERVER['REQUEST_METHOD'] === 'POST')) {
        
        // Determino la acción solicitada
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['codPedido']) && isset($_POST['estado'])) {
                $action = 'cambiarEstadoPedido';
            } else if (file_get_contents('php://input')) {
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
        
        // Establezco la cabecera para respuesta JSON
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
        // Previene cualquier salida que no sea JSON
        ob_start();
        
        // Configuro errores para que no se muestren
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        ini_set('error_log', 'debug_cocina.log');
        
        try {
            // Recibo los datos POST
            $input = file_get_contents('php://input');
            error_log("JSON recibido: " . $input);
            
            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) { //Si hay un error al decodificar el JSON lo que hago es enviar un error
                error_log("Error al decodificar JSON: " . json_last_error_msg());
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Error al decodificar JSON']);
                exit;
            }
            
            // Verifico que tenga los datos necesarios
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
            
            // Utilizo la función modularizada con los parámetros en el orden correcto
            $resultado = actualizarEstadoProductoCompleto($codPedido, $codProducto, $estado, $area, $numMesa, $codEmpleado);
            
            // Si se requirió una conexión nueva, también actualizo la variable global
            global $conexion;
            if (!$conexion && isset($resultado['conexion'])) {
                $conexion = $resultado['conexion'];
                unset($resultado['conexion']);
            }
            
            // Intento enviar notificación WebSocket directamente desde aquí como respaldo
            try {
                // Aseguro que la función esté disponible
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
                // No afecta la respuesta principal
            }
            error_log("Resultado de actualización: " . print_r($resultado, true));
            
            // Limpio cualquier salida previa
            ob_end_clean();
            
            // Aseguro que la salida sea JSON válido
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
        
        // Utilizo la función modularizada
        $resultado = cambiarEstadoPedido($codPedido, $estado);
        echo json_encode($resultado);
    }
    
    function procesarObtenerPedidosPendientes() {
        // Preveno cualquier salida que no sea JSON
        ob_start();
        
        // Configuro errores para que no se muestren en la salida
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        ini_set('error_log', 'debug_cocina.log');
        
        try {
            // Obtenemos el área para filtrar pedidos específicos
            $area = isset($_GET['area']) ? $_GET['area'] : '';
            
            error_log("Obteniendo pedidos pendientes para área: $area");
            
            // Utilizo la función modularizada
            $resultado = obtenerPedidosPendientesArea($area);
            
            // Limpio cualquier salida previa
            ob_end_clean();
            
            // Aseguro que la salida sea JSON válido
            header('Content-Type: application/json');
            echo json_encode($resultado);
            
        } catch (Exception $e) {
            error_log("Excepción en procesarObtenerPedidosPendientes: " . $e->getMessage());
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
        }
    }
    
    // Solo incluyo la cabecera si NO es una petición AJAX
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        require "cabeceraTrabajador.php";
    }
    comprobar_rol(["cocina"]);
    $conexion = conectarBD();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cocina</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
</head>
<body class="bg-gradient-to-br from-[#E0FAF4] to-[#51B2E0] min-h-screen">
    <?php
    // Función para definir la clase CSS según estado
    function getEstadoClass($estado) {
        switch ($estado) {
            case "pendiente":
                return "bg-[#E0FAF4] text-[#256353]";
            case "en preparación":
                return "bg-[#72E8D4]/30 text-[#256353]";
            case "listo":
                return "bg-[#72E8AC]/50 text-[#256353]";
            case "servido":
                return "bg-[#72E8AC] text-[#256353]";
            case "rechazado":
                return "bg-red-100 text-red-700";
            default:
                return "bg-gray-100 text-[#256353]";
        }
    }
    ?>


    </div>
    <!-- lo utilizo para poder enviar los datos de los pedidos a la cocina -->
    <div class="hidden">
        <span id="totalPedidos">0</span>
        <span id="totalProductosPendientes">0</span>
        <span id="productosListos">0</span>
        <span id="productosTotal">0</span>
    </div>
    
   
    <div class="container mx-auto px-4 pt-24 pb-8">
       
        <div class="bg-emerald-50 bg-opacity-90 rounded-lg shadow-lg">            
            <div id="contenedorPedidos" class="p-4">
                
                <div id="sin-pedidos" class="text-center py-12 px-4 hidden">
                    <span class="material-symbols-outlined text-gray-400 text-5xl mb-3">kitchen</span>
                    <p class="text-xl text-gray-700 font-medium mb-2">No hay pedidos pendientes</p>
                    <p class="text-gray-500">Los pedidos pendientes aparecerán aquí</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Plantilla para los  nuevos pedidos -->
    <template id="plantillaMesa" class="mt-8">
        <div class="mb-6 border-b border-gray-200 pb-2">
            <h2 class="text-xl font-bold text-gray-800">Mesa <span class="mesa-numero"></span></h2>
            <div class="pedidos-container">
                <!-- Los pedidos se insertan aqui -->
            </div>
        </div>
    </template>
    
    <template id="plantillaPedido">
        <div class="pedido-card bg-white rounded-lg shadow mb-4 border-l-4 border-green-500" data-cod-pedido="">
            <div class="flex justify-between items-center p-3">
                <div class="estado-pedido px-2.5 py-0.5 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">Pendiente</div>
            </div>
            
            <div class="observacionGeneralPedido p-3 pt-0 text-sm text-gray-700 hidden"></div>
            <div class="productos-container px-3 pb-3">
                <!-- Los productos se insertan aqui -->
            </div>
            
            <div id="pedido-card-footer" class="px-3 pb-3 border-t border-gray-100 pt-2 flex justify-between items-center">
                
                <div class="text-gray-500 text-sm"><span class="hora-pedido"></span></div>
            </div>
        </div>
    </template>
    
    <!-- Plantilla para productos que inicialmente esta oculta-->
    <template id="plantillaProducto">
        <div class="producto flex justify-between items-center py-3 border-b border-gray-100">
            <div>
                <div class="nombre font-medium text-gray-800">Tortilla de patatas</div>
                <div class="text-gray-500 text-sm">Cant: <span class="cantidad">1</span></div>
                <div class="observacionesProducto text-xs text-gray-600 italic mt-1"></div>
            </div>
            <div class="estado-producto font-medium text-xs">Pendiente</div>
            <div class="flex gap-1">
                <button class="print-button bg-transparent hover:bg-gray-100 p-1.5 rounded">
                    <span class="material-symbols-outlined text-gray-500">print</span>
                </button>
                <button class="completar-button bg-transparent hover:bg-gray-100 p-1.5 rounded">
                    <span class="material-symbols-outlined text-gray-500">check_circle</span>
                </button>
            </div>
        </div>
    </template>
    <script src="js/gestionPedidos.js"></script>
    <script src="js/cocina.js"></script>
</body>
</html>
