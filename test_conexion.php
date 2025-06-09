<?php
// Script para probar la conexión y las funciones críticas

// Configurar logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'test_conexion.log');

// Incluir archivos necesarios
require_once "bd.php";
require_once "funciones.php";

// Verificar la conexión
$conexion = conectarBD();
echo "<h2>Prueba de conexión a base de datos</h2>";
if ($conexion) {
    echo "✅ Conexión exitosa<br>";
} else {
    echo "❌ Error de conexión<br>";
    exit;
}

// Prueba de las funciones críticas
echo "<h2>Prueba de funciones</h2>";

// 1. Probar obtenerPedidosPendientesArea
echo "<h3>Prueba de obtenerPedidosPendientesArea('cocina')</h3>";
try {
    $resultadoCocina = obtenerPedidosPendientesArea('cocina');
    echo "Resultado: <pre>" . json_encode($resultadoCocina, JSON_PRETTY_PRINT) . "</pre>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 2. Probar enviar una notificación WebSocket
echo "<h3>Prueba de enviarNotificacionWebSocket</h3>";
if (function_exists('enviarNotificacionWebSocket')) {
    try {
        $datosTest = [
            'tipo' => 'test',
            'mensaje' => 'Prueba de notificación a las ' . date('H:i:s')
        ];
        $result = enviarNotificacionWebSocket($datosTest);
        echo "✅ Notificación enviada correctamente<br>";
    } catch (Exception $e) {
        echo "❌ Error al enviar notificación: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ La función enviarNotificacionWebSocket no está disponible<br>";
}

// 3. Verificar que se puede acceder a la conexión globalmente
echo "<h3>Prueba de acceso global a la conexión</h3>";
function testConexionGlobal() {
    global $conexion;
    if ($conexion && $conexion instanceof mysqli) {
        return true;
    } else {
        return false;
    }
}

if (testConexionGlobal()) {
    echo "✅ Se puede acceder a la conexión globalmente<br>";
} else {
    echo "❌ No se puede acceder a la conexión globalmente<br>";
}
?>

<p><a href="cocina.php">Volver a la página de cocina</a></p>
