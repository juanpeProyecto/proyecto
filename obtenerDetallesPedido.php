<?php
// Control de errores y captura de errores fatales para formato JSON
ini_set('display_errors', 0);
error_reporting(0);
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (ob_get_level() > 0) ob_end_clean();
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        echo json_encode(['error' => "Error del servidor: " . $error['message']]);
    }
});

// Inicio buffer para control de salida
ob_start();

// Establezco cabecera JSON
header('Content-Type: application/json');

// Implemento retry con backoff exponencial para manejar múltiples intentos
$max_intentos = 3;
$intento = 0;
$espera_base = 200; // milisegundos
$resultado = null;

try {
    $codPedido = isset($_GET['cod']) ? (int)$_GET['cod'] : 0;
    
    if ($codPedido <= 0) {
        throw new Exception('Código de pedido no proporcionado o inválido.');
    }
    
    // Control de conexión a BD con reintentos
    while ($intento < $max_intentos) {
        $intento++;
        try {
            // Incluyo funciones y BD aquí para controlar mejor el tiempo de vida de la conexión
            require_once "funciones.php";
            require_once "bd.php";
            
            // Obtengo el detalle del pedido
            $detallePedido = obtenerDetallePedido($codPedido);
            
            if (isset($detallePedido['error'])) {
                http_response_code(404);
                $resultado = $detallePedido;
            } else {
                http_response_code(200);
                $resultado = $detallePedido;
            }
            
            // Si hay una conexión global disponible, la cierro explícitamente
            global $conexion;
            if (isset($conexion) && $conexion) {
                $conexion->close();
            }
            
            // Salgo del bucle de reintentos si llegó aquí sin errores
            break;
            
        } catch (Exception $e) {
            // Si es el último intento, propago la excepción
            if ($intento >= $max_intentos) {
                throw $e;
            }
            
            // Espero con backoff exponencial antes de reintentar
            $espera = $espera_base * pow(2, $intento - 1); // 200ms, 400ms, 800ms...
            usleep($espera * 1000); // usleep usa microsegundos
        }
    }
    
    // Devuelvo el resultado final
    echo json_encode($resultado);
    
} catch (Exception $e) {
    // Manejo unificado de errores
    http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Aseguro que cualquier buffer de salida se envíe y termine la ejecución
if (ob_get_level() > 0) {
    ob_end_flush();
}
?>
