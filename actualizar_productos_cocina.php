<?php
// Archivo para actualizar productos que deberían ser asignados al cocinero pero están asignados al camarero
require_once "funciones.php";
require_once "bd.php";

// Conecto a la base de datos
$conexion = conectarBD();
if (!$conexion) {
    echo "Error al conectar con la base de datos";
    exit;
}

// Productos que deberían ser asignados a cocina/cocinero
$productos_cocina = [
    'croquetas', 'tortilla', 'patatas', 'hamburguesa', 'bocadillo', 
    'sándwich', 'sandwich', 'pizza', 'pasta', 'arroz', 'ensalada', 
    'huevos', 'frito', 'plancha', 'parrilla', 'paella'
];

// Construyo la consulta para actualizar productos
$condiciones = [];
foreach ($productos_cocina as $producto) {
    $producto = $conexion->real_escape_string($producto);
    $condiciones[] = "Nombre LIKE '%$producto%'";
}

// Uno las condiciones con OR
$condicion_sql = implode(" OR ", $condiciones);

// Consulta para ver productos actuales
$consulta_ver = "SELECT codProducto, Nombre, QuienLoAtiende FROM Productos 
                WHERE $condicion_sql ORDER BY QuienLoAtiende, Nombre";

$resultado_ver = $conexion->query($consulta_ver);
echo "<h2>Productos a actualizar:</h2>";
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
        
        if ($row['QuienLoAtiende'] != 'cocinero') {
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
    $consulta_actualizar = "UPDATE Productos SET QuienLoAtiende = 'cocinero' WHERE codProducto IN ($ids)";
    
    if ($conexion->query($consulta_actualizar)) {
        echo "<h3>¡Éxito! Se han actualizado " . $conexion->affected_rows . " productos.</h3>";
    } else {
        echo "<h3>Error al actualizar productos: " . $conexion->error . "</h3>";
    }
} else {
    echo "<h3>No hay productos que necesiten ser actualizados.</h3>";
}

// Verificar los valores permitidos para el campo Estado
echo "<h2>Verificación de valores permitidos en columna Estado:</h2>";
$check_column = "SHOW COLUMNS FROM DetallePedidos LIKE 'estado'";
$result_column = $conexion->query($check_column);

if ($result_column && $result_column->num_rows > 0) {
    $column_info = $result_column->fetch_assoc();
    echo "<p>Tipo de columna Estado: <strong>" . $column_info['Type'] . "</strong></p>";
    
    // Si es un tipo ENUM, extraigo los valores permitidos
    if (strpos($column_info['Type'], 'enum') === 0) {
        preg_match("/^enum\(\'(.*)\'\)$/", $column_info['Type'], $matches);
        $valores = explode("','", $matches[1]);
        echo "<p>Valores permitidos: <strong>" . implode(", ", $valores) . "</strong></p>";
    }
} else {
    echo "<p>No se pudo obtener información de la columna Estado</p>";
}

$conexion->close();
?>

<a href="cocina.php" style="display: block; margin-top: 20px; padding: 10px; background-color: #4CAF50; color: white; text-decoration: none; width: 200px; text-align: center; border-radius: 5px;">Volver a Cocina</a>
