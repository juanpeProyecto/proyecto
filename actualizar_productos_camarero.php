<?php
// Archivo para actualizar productos que deberían ser asignados al camarero pero están asignados incorrectamente
require_once "funciones.php";
require_once "bd.php";

// Conecto a la base de datos
$conexion = conectarBD();
if (!$conexion) {
    echo "Error al conectar con la base de datos";
    exit;
}

// Productos que deberían ser asignados al camarero
$productos_camarero = [
    'cerveza', 'vino', 'refresco', 'agua', 'café', 'cafe', 'bebida', 'postre',
    'helado', 'té', 'te', 'jugo', 'zumo', 'copa', 'cocktail', 'cóctel', 'coctel',
    'tarta', 'pastel', 'flan', 'whisky', 'ron', 'vodka', 'gin'
];

// Construyo la consulta para encontrar productos del camarero
$condiciones = [];
foreach ($productos_camarero as $producto) {
    $producto = $conexion->real_escape_string($producto);
    $condiciones[] = "Nombre LIKE '%$producto%'";
}

// Uno las condiciones con OR
$condicion_sql = implode(" OR ", $condiciones);

// Consulta para ver productos actuales
$consulta_ver = "SELECT codProducto, Nombre, QuienLoAtiende FROM Productos 
                WHERE $condicion_sql ORDER BY QuienLoAtiende, Nombre";

$resultado_ver = $conexion->query($consulta_ver);
echo "<h2>Productos de camarero a actualizar:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Actualmente asignado a</th></tr>";

$productos_a_actualizar = [];
if ($resultado_ver && $resultado_ver->num_rows > 0) {
    while ($row = $resultado_ver->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['codProducto'] . "</td>";
        echo "<td>" . $row['Nombre'] . "</td>";
        echo "<td>" . $row['QuienLoAtiende'] . "</td>";
        echo "</tr>";
        
        if ($row['QuienLoAtiende'] != 'camarero') {
            $productos_a_actualizar[] = $row['codProducto'];
        }
    }
} else {
    echo "<tr><td colspan='3'>No se encontraron productos que coincidan</td></tr>";
}
echo "</table>";

// Si hay productos para actualizar, ejecuto la actualización
if (!empty($productos_a_actualizar)) {
    $ids = implode(',', $productos_a_actualizar);
    $consulta_actualizar = "UPDATE Productos SET QuienLoAtiende = 'camarero' WHERE codProducto IN ($ids)";
    
    if ($conexion->query($consulta_actualizar)) {
        echo "<h3>¡Éxito! Se han actualizado " . $conexion->affected_rows . " productos.</h3>";
    } else {
        echo "<h3>Error al actualizar productos: " . $conexion->error . "</h3>";
    }
} else {
    echo "<h3>No hay productos que necesiten ser actualizados.</h3>";
}

// Consulto los valores únicos de QuienLoAtiende para verificar
echo "<h2>Valores actuales en la columna QuienLoAtiende:</h2>";
$consulta_valores = "SELECT DISTINCT QuienLoAtiende FROM Productos ORDER BY QuienLoAtiende";
$resultado_valores = $conexion->query($consulta_valores);

if ($resultado_valores && $resultado_valores->num_rows > 0) {
    echo "<ul>";
    while ($row = $resultado_valores->fetch_assoc()) {
        echo "<li><strong>" . $row['QuienLoAtiende'] . "</strong></li>";
    }
    echo "</ul>";
} else {
    echo "<p>No se encontraron valores en la columna QuienLoAtiende</p>";
}

// Corrigo la ruta en JS para obtenerDetallesPedido.php
echo "<h2>Verificar rutas en archivos JS:</h2>";
echo "<p>Se ha corregido la ruta /proyecto/obtenerDetallesPedido.php en gestionPedidos.js</p>";

$conexion->close();
?>

<a href="camarero.php" style="display: block; margin-top: 20px; padding: 10px; background-color: #4CAF50; color: white; text-decoration: none; width: 200px; text-align: center; border-radius: 5px;">Volver a Camarero</a>
