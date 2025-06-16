<?php
//si no es ajax no se ejecuta el script
    $esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
              (isset($_GET['action']) || isset($_POST['action']) || $_SERVER['REQUEST_METHOD'] === 'POST');
    error_log("camarero.php: Script INICIADO. REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . ". Es AJAX: " . (isset($esAjax) && $esAjax ? 'Sí' : 'No') . ". GET: " . json_encode($_GET) . ". POST: " . json_encode($_POST) . ". RAW POST: " . file_get_contents('php://input'));
    
    if ($esAjax) {
        ob_start();
        ini_set('display_errors', 0); 
        error_reporting(0); 

        // registro una función para capturar errores fatales y devolver JSON
        register_shutdown_function(function() {
            $error = error_get_last();
            // verifico si ocurrió un error 
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
                // limpio cualquier buffer de salida si aún está activo
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                // si las cabeceras no se han enviado, establecer las de JSON
                if (!headers_sent()) {
                    header('Content-Type: application/json');
                    http_response_code(500); // Error interno del servidor
                }
                
                // Creo un mensaje de error JSON
                $errorMessage = "Error fatal en el servidor: " . $error['message'] . " en " . $error['file'] . " línea " . $error['line'];
                // intento codificar el mensaje de error a JSON
                $jsonError = json_encode(['success' => false, 'error' => $errorMessage]);
                
                if ($jsonError === false) {
                    // si la codificación del mensaje de error falla, envio un error genérico
                    echo '{"success":false,"error":"Error fatal en el servidor y error al codificar el mensaje de error."}';
                } else {
                    echo $jsonError;
                }
                
                // Regist o el error detallado en el log del servidor para depuración interna
                error_log("AJAX Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
            }
        });
    }
    
    require_once "funciones.php";
    require_once('bd.php');
    
    // Solo inclui  r la cabecera si NO es una petición AJAX
    if (!$esAjax) {
        require "cabeceraTrabajador.php";
    }
    // Función para enviar notificaciones WebSocket
    function enviarNotificacion($datos) {
        // host y puerto del servidor que utilizo para el socket
        $host = 'localhost';
        $port = 8080;
        
        try {
            // Creo un socket TCP/IP
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            
            // Si no se pudo crear el socket, lanzo una excepcoin
            if ($socket === false) {
                throw new Exception("socket_create() falló: " . socket_strerror(socket_last_error()));
            }
            
            // Intento conectar al servidor WebSocket
            $result = @socket_connect($socket, $host, $port);
            
            // Si no se pudo conectar, simplemente registro y continuar (no debe bloquear la aplicación)
            if ($result === false) {
                error_log("No se pudo conectar al servidor WebSocket: " . socket_strerror(socket_last_error($socket)));
                return false;
            }
            
            // Preparo el mensaje como JSON
            $mensaje = json_encode($datos);
            
            // Envio el mensaje
            socket_write($socket, $mensaje, strlen($mensaje));
            
            // Cierro el socket
            socket_close($socket);
            
            return true;
            
        } 
        catch (Exception $e) {
            error_log("Error al enviar notificación: " . $e->getMessage());
            return false;
        }
    }
    
    // Función para obtener los pedidos con productos listos para servir
    function procesarObtenerPedidosListos() {
        // Previene cualquier salida que no sea JSON
        ob_start();
        
        try {
            $conexion = conectarBD();
            
            //consulta para obtener los pedidos con productos listos para servir
            $sqlPedidos = "SELECT DISTINCT p.codPedido, p.numMesa, p.Fecha, p.Estado, p.Total 
                           FROM Pedidos p 
                           WHERE p.Estado NOT IN ('servido', 'completado', 'cancelado')
                           AND EXISTS (
                               SELECT 1 FROM DetallePedidos dp 
                               WHERE dp.codPedido = p.codPedido 
                               AND dp.Estado = 'listo'
                           )
                           ORDER BY p.Fecha ASC";
            
            $resultadoPedidos = $conexion->query($sqlPedidos);
            
            if (!$resultadoPedidos) {
                error_log("Error en la consulta: " . $conexion->error);
                throw new Exception("Error al obtener pedidos: " . $conexion->error);
            }
            
            $pedidos = [];
            
            while ($filaPedido = $resultadoPedidos->fetch_assoc()) {
                $codPedido = $filaPedido['codPedido'];
                
                // Consultaque obtiene SOLO productos en estado 'listo' (no 'servido', 'cancelado', etc.)
                // Esta consulta es crucial para evitar que los productos ya servidos vuelvan a aparecer
                
                // Verifico si existe la tabla Categorias
                $checkCategorias = $conexion->query("SHOW TABLES LIKE 'Categorias'");
                
                if ($checkCategorias->num_rows > 0) {
                    // Si existe la tabla Categorias, usamos el JOIN para obtener la categoría
                    $sqlProductos = "SELECT dp.codDetallePedido, dp.codProducto, p.nombre, dp.Cantidad, dp.Estado, dp.Observaciones, 
                                     COALESCE(c.nombre, 'Sin categoría') as categoria
                                     FROM DetallePedidos dp
                                     INNER JOIN Productos p ON dp.codProducto = p.codProducto
                                     LEFT JOIN Categorias c ON p.codCategoria = c.codCategoria
                                     WHERE dp.codPedido = $codPedido 
                                     AND dp.Estado = 'listo' 
                                     ORDER BY c.nombre, p.nombre";
                } else {
                    // Si no existe Categorias, mostramos todos los productos listos
                    $sqlProductos = "SELECT dp.codDetallePedido, dp.codProducto, p.nombre, dp.Cantidad, dp.Estado, dp.Observaciones,
                                     'Sin categorizar' as categoria
                                     FROM DetallePedidos dp
                                     INNER JOIN Productos p ON dp.codProducto = p.codProducto
                                     WHERE dp.codPedido = $codPedido 
                                     AND dp.Estado = 'listo'";                
                }
                
                $resultadoProductos = $conexion->query($sqlProductos);
                
                if (!$resultadoProductos) {
                    error_log("Error en consulta de productos: " . $conexion->error);
                    continue;
                }
                
                $productos = [];
                while ($filaProducto = $resultadoProductos->fetch_assoc()) {
                    $productos[] = [
                        'codDetalle' => $filaProducto['codDetallePedido'],
                        'codProducto' => $filaProducto['codProducto'],
                        'nombre' => $filaProducto['nombre'],
                        'cantidad' => $filaProducto['Cantidad'],
                        'estado' => $filaProducto['Estado'],
                        'categoria' => $filaProducto['categoria']
                       
                    ];
                }
                
                // Solo agrego el pedido si tiene productos listos
                if (count($productos) > 0) {
                    $pedidos[] = [
                        'cod' => $codPedido,
                        'fecha' => $filaPedido['Fecha'],
                        'mesa' => $filaPedido['numMesa'],
                        'estado' => $filaPedido['Estado'],
                        'productos' => $productos
                    ];
                }
            }
            
            if (count($pedidos) == 0 && $resultadoPedidos->num_rows > 0) {
                // Reinicio el puntero del resultado
                $resultadoPedidos->data_seek(0);
                $primerPedido = $resultadoPedidos->fetch_assoc();
                $codPedidoDebug = $primerPedido['codPedido'];
                
                // Verifico cuántos productos hay en este pedido y cuáles son sus estados
                $sqlDebug = "SELECT dp.Estado, COUNT(*) as total 
                            FROM DetallePedidos dp 
                            WHERE dp.codPedido = $codPedidoDebug 
                            GROUP BY dp.Estado";
                            
                $resultadoDebug = $conexion->query($sqlDebug);
                $estadosProductos = [];
                
                if ($resultadoDebug) {
                    while ($filaDebug = $resultadoDebug->fetch_assoc()) {
                        $estadosProductos[] = [
                            'estado' => $filaDebug['Estado'],
                            'cantidad' => $filaDebug['total']
                        ];
                    }
                }
                
                // Verifico si la tabla Categorias exisste
                $checkCategorias = $conexion->query("SHOW TABLES LIKE 'Categorias'");
                $categoriasExiste = $checkCategorias->num_rows > 0;
                
                // Añado esta información al debug
                $debugExtendido = [
                    'primerPedidoId' => $codPedidoDebug,
                    'estadosDeProductos' => $estadosProductos,
                    'tablaCategoriaExiste' => $categoriasExiste,
                    'sql_productos_primer_pedido' => str_replace('$codPedido', $codPedidoDebug, $sqlProductos)
                ];
            } else {
                $debugExtendido = [];
            }
            
            // Añado información de debug
            $debug = [
                'totalPedidosEncontrados' => count($pedidos),
                'totalPedidosSQL' => $resultadoPedidos->num_rows,
                'filtroSQL' => $sqlPedidos,
                'timestamp' => date('Y-m-d H:i:s'),
                'debug_extendido' => $debugExtendido
            ];
            
            // Limpio cualquier salida previa
            ob_end_clean();
            
            // Respondo con los pedidos listos y la información de debug
            echo json_encode([
                'success' => true, 
                'pedidos' => $pedidos,
                'debug' => $debug
            ]);
            
        } catch (Exception $e) {
            error_log("Error en procesarObtenerPedidosListos: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    // Función para marcar un producto como servido
    function procesarMarcarComoServido() {
        ob_start();
        header('Content-Type: application/json');
        $conexion = null;

        try {
            $conexion = conectarBD();

            // El frontend debe enviar 'codDetalle' en el cuerpo de la petición JSON
            $input = json_decode(file_get_contents('php://input'), true);
            $codDetalle = $input['codDetalle'] ?? 0;
            $codDetalle = (int)$codDetalle;

            if ($codDetalle <= 0) {
                throw new Exception("Falta el parámetro 'codDetalle' o es inválido.");
            }
            
            error_log("procesarMarcarComoServido: Procesando codDetalle=$codDetalle");

            // 1. Obtengo la información del producto ANTES de actualizar para la notificación
            $sqlInfo = "SELECT codPedido, codProducto FROM DetallePedidos WHERE codDetallePedido = ?";
            $stmtInfo = $conexion->prepare($sqlInfo);
            $stmtInfo->bind_param("i", $codDetalle);
            $stmtInfo->execute();
            $resultInfo = $stmtInfo->get_result();
            $infoProducto = $resultInfo->fetch_assoc();
            $stmtInfo->close();

            if (!$infoProducto) {
                error_log("procesarMarcarComoServido: No se encontró el detalle con cod=$codDetalle");
                throw new Exception("No se encontró el detalle del pedido con código: $codDetalle");
            }
            
            $codPedido = $infoProducto['codPedido'];
            $codProducto = $infoProducto['codProducto'];
            error_log("procesarMarcarComoServido: Encontrado detalle con codPedido=$codPedido, codProducto=$codProducto");

            // 2. Actualizo el estado del producto a 'servido'
            $sql = "UPDATE DetallePedidos SET Estado = 'servido' WHERE codDetallePedido = ? AND Estado = 'listo'";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $codDetalle);
            $stmt->execute();
            
            $productoActualizado = $stmt->affected_rows > 0;
            error_log("procesarMarcarComoServido: Actualización realizada. affected_rows={$stmt->affected_rows}");
            $stmt->close();

            $pedidoCompleto = false;
            if ($productoActualizado) {
                // Notifico sobre el producto servido
                enviarNotificacion([
                    'tipo' => 'productoServido',
                    'codPedido' => $codPedido,
                    'codProducto' => $codProducto,
                    'codDetalle' => $codDetalle,
                    'mensaje' => "Producto servido."
                ]);

                // 3. Verifico si el pedido completo está servido
                $sqlVerificar = "SELECT COUNT(*) as total FROM DetallePedidos WHERE codPedido = ? AND Estado != 'servido'";
                $stmtVerificar = $conexion->prepare($sqlVerificar);
                $stmtVerificar->bind_param("i", $codPedido);
                $stmtVerificar->execute();
                $resultadoVerificar = $stmtVerificar->get_result()->fetch_assoc();
                $stmtVerificar->close();

                if ($resultadoVerificar['total'] == 0) {
                    $pedidoCompleto = true;
                    $sqlPedido = "UPDATE Pedidos SET Estado = 'servido' WHERE codPedido = ?";
                    $stmtPedido = $conexion->prepare($sqlPedido);
                    $stmtPedido->bind_param("i", $codPedido);
                    $stmtPedido->execute();
                    $stmtPedido->close();

                    // Notifico que el pedido completo está servido
                    enviarNotificacion([
                        'tipo' => 'pedidoServido',
                        'codPedido' => $codPedido,
                        'mensaje' => "Pedido #$codPedido completamente servido."
                    ]);
                }
            }

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'pedidoCompleto' => $pedidoCompleto,
                'actualizacion_realizada' => $productoActualizado,
                'detalle' => $codDetalle
            ]);

        } catch (Exception $e) {
            error_log("procesarMarcarComoServido ERROR: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        } finally {
            if ($conexion) {
                $conexion->close();
            }
        }
    }
    
    // Procesador de API unificado para camarero
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || 
        (isset($_GET['action']) || isset($_POST['action']) || $_SERVER['REQUEST_METHOD'] === 'POST')) {
        
        // Determino la acción solicitada - aceptar tanto 'action' como 'accion'
        $action = $_GET['action'] ?? $_POST['action'] ?? $_GET['accion'] ?? $_POST['accion'] ?? '';
        
        // Si no hay acción explícita pero es un POST con ciertos parámetros, inferimos la acción
        if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['codPedido']) && isset($_POST['estado'])) {
                $action = 'cambiarEstadoPedido';
            } else if (file_get_contents('php://input')) {
                // Probablemente JSON, intento decodificar para determinar la acción
                $data = json_decode(file_get_contents('php://input'), true);
                if (isset($data['codPedido']) && isset($data['estado'])) {
                    $action = 'cambiarEstadoPedido';
                }
            }
        } else if (empty($action) && isset($_GET['area'])) {
            $action = 'obtenerPedidosListos';
        }
        
        // Establezco cabecera para respuesta JSON
        header('Content-Type: application/json');
        
        switch ($action) {
            case 'marcarComoServido':
                error_log("camarero.php: Acción 'marcarComoServido' DETECTADA en SWITCH. Llamando a procesarMarcarComoServido().");
                procesarMarcarComoServido();
                exit;
                
            case 'obtenerPedidosListos':
                procesarObtenerPedidosListos();
                exit;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
                exit;
        }
    }
    
    comprobar_rol(["camarero"]);
    $conexion = conectarBD();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camarero - Pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-[#E0FAF4]to-[#51B2E0] min-h-screen [&_.botones-fijos]:z-[999]!">
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

<div class="container mx-auto px-4 pt-24 pb-8">
    <div class="bg-blue-50 bg-opacity-80 rounded-lg shadow-lg">
        <div id="contenedorPedidos" class="p-4">
            <div id="sin-pedidos" class="text-center py-12 px-4">
                <span class="material-symbols-outlined text-gray-400 text-5xl mb-3">restaurant</span>
                <p class="text-xl text-gray-700 font-medium mb-2">No hay pedidos pendientes</p>
                <p class="text-gray-500">Los pedidos listos para servir aparecerán aquí</p>
            </div>
        </div>
    </div>
</div>

<template id="plantillaMesa" class="mt-8">
    <div class="mb-6">
        <div class="bg-blue-600 text-white p-3 rounded-t-lg flex items-center">
            <span class="material-symbols-outlined mr-2">table_restaurant</span>
            <h2 class="text-xl font-bold">Mesa <span class="mesa-numero"></span></h2>
        </div>
        <div class="pedidos-container">
            <!-- Los pedidos se insertarán aquí -->
        </div>
    </div>
</template>

<template id="plantillaPedido">
    <div class="pedido-card bg-white rounded-lg shadow overflow-hidden border-l-4 border-blue-500" data-cod-pedido="">
        <div class="flex items-center justify-between p-3">
            <div class="flex items-center">
                <span class="material-symbols-outlined text-blue-600 mr-2">lock</span>
                <span class="text-gray-700 font-medium">Pedido #<span class="pedido-numero"></span></span>
            </div>
            <div class="estado-pedido px-2.5 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">Listo para servir</div>
        </div>
        
        <div class="productos-container px-3 pb-3">
            <!-- Los productos se insertarán aquí -->
        </div>
        
        <div class="px-3 pb-3 flex items-center justify-between">
            <div class="flex items-center text-gray-500 text-xs">
                <span class="material-symbols-outlined text-gray-400 mr-1" style="font-size: 16px;">schedule</span>
                <span class="hora-pedido"></span> - <span class="fecha-pedido"></span>
            </div>
        </div>
    </div>
</template>

<template id="plantillaProducto">
    <div class="producto bg-gray-50 rounded p-3 mb-2">
        <div class="flex justify-between items-start">
            <span class="nombre font-medium text-gray-800">Producto</span>
            <div class="flex items-center">
                <span class="material-symbols-outlined text-amber-600 text-sm mr-1">tag</span>
                <span class="cantidad text-amber-600 font-medium">Cant: 1</span>
            </div>
        </div>
        <button class="btnServir servir w-full p-3 mt-2 bg-[#72E8AC] hover:bg-[#60C99F] text-[#256353] font-bold text-lg rounded-lg transition-colors flex items-center justify-center shadow-md border-2 border-[#60C99F]">
            <span class="material-symbols-outlined mr-2">check_circle</span>
            SERVIR
        </button>
    </div>
</template>
<script src="js/websocket.js"></script>
<script src="js/camarero.js"></script>
</body>
</html>