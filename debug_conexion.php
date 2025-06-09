<?php
// Archivo para depurar problemas de conexión a la base de datos
require_once "bd.php";

// Intentar establecer conexión
$conexion = @conectarBD();

// Mostrar resultados
echo "<h1>Depuración de Conexión a Base de Datos</h1>";
echo "<pre>";
if ($conexion) {
    echo "✅ Conexión exitosa a la base de datos\n";
    echo "Información de conexión:\n";
    echo "- Host: " . $conexion->host_info . "\n";
    echo "- Versión del servidor: " . $conexion->server_info . "\n";
    echo "- Charset: " . $conexion->character_set_name() . "\n";
} else {
    echo "❌ Error al conectar a la base de datos\n";
    echo "Error mysqli: " . mysqli_connect_error() . "\n";
    echo "Código de error: " . mysqli_connect_errno() . "\n";
}
echo "</pre>";

// Verificar la función actualizarEstadoProductoCompleto
echo "<h2>Verificando la función actualizarEstadoProductoCompleto</h2>";
echo "<pre>";

// Cargar funciones
require_once "funciones.php";

// Verificar que $conexion es la misma después de incluir funciones.php
if ($conexion) {
    echo "✅ \$conexion sigue siendo válida después de incluir funciones.php\n";
} else {
    echo "❌ \$conexion se ha perdido después de incluir funciones.php\n";
}

// Verificar la variable global en la función
function test_conexion_global() {
    global $conexion;
    echo "Dentro de una función test:\n";
    if ($conexion) {
        echo "✅ La variable global \$conexion es accesible y válida\n";
    } else {
        echo "❌ La variable global \$conexion es NULL o no accesible\n";
    }
}

test_conexion_global();

echo "</pre>";
?>
