<?php
// Script para optimizar las conexiones a la base de datos
require_once 'bd.php';

echo "<h1>Optimizador de Conexiones a la Base de Datos</h1>";
echo "<p>Este script ayuda a solucionar problemas de demasiadas conexiones simultáneas.</p>";

// 1. Verificar conexiones activas
$conexion = conectarBD();
if (!$conexion) {
    echo "<p style='color:red'>Error al conectar con la base de datos</p>";
    exit;
}

// Mostrar diagnóstico
echo "<h2>Diagnóstico de Conexiones:</h2>";

// Ver conexiones activas
$query_show_status = "SHOW GLOBAL STATUS LIKE 'max_used_connections'";
$result_status = $conexion->query($query_show_status);
if ($result_status && $result_status->num_rows > 0) {
    $row = $result_status->fetch_assoc();
    echo "<p>Máximo de conexiones utilizadas: <strong>" . $row['Value'] . "</strong></p>";
}

// Ver límite de conexiones
$query_show_variables = "SHOW VARIABLES LIKE 'max_connections'";
$result_variables = $conexion->query($query_show_variables);
if ($result_variables && $result_variables->num_rows > 0) {
    $row = $result_variables->fetch_assoc();
    echo "<p>Límite de conexiones configurado: <strong>" . $row['Value'] . "</strong></p>";
}

// Ver conexiones por usuario
echo "<h2>Conexiones por usuario:</h2>";
$query_user_connections = "SELECT user, count(*) as connections FROM information_schema.processlist GROUP BY user";
$result_user_connections = $conexion->query($query_user_connections);

if ($result_user_connections && $result_user_connections->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Usuario</th><th>Conexiones activas</th></tr>";
    while ($row = $result_user_connections->fetch_assoc()) {
        echo "<tr><td>" . $row['user'] . "</td><td>" . $row['connections'] . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No se pudo obtener información de conexiones por usuario</p>";
}

// Obtener información sobre valores permitidos en Estado
echo "<h2>Valores permitidos para la columna Estado:</h2>";

// Verificar si está en DetallePedidos
$query_detalle = "SHOW COLUMNS FROM DetallePedidos LIKE 'estado'";
$result_detalle = $conexion->query($query_detalle);
if ($result_detalle && $result_detalle->num_rows > 0) {
    $row = $result_detalle->fetch_assoc();
    echo "<p>Columna Estado en DetallePedidos:<br> <strong>" . $row['Type'] . "</strong></p>";
    
    // Si es ENUM, extraemos los valores permitidos
    if (strpos($row['Type'], 'enum') === 0) {
        preg_match("/^enum\(\'(.*)\'\)$/", $row['Type'], $matches);
        $valores = explode("','", $matches[1]);
        echo "<p>Valores permitidos:</p>";
        echo "<ul>";
        foreach ($valores as $valor) {
            echo "<li><code>" . $valor . "</code></li>";
        }
        echo "</ul>";
        
        echo "<p>Asegúrate de usar solo estos valores exactos en la columna Estado.</p>";
    }
}

// Verificar si hay también en Pedidos
$query_pedidos = "SHOW COLUMNS FROM Pedidos LIKE 'Estado'";
$result_pedidos = $conexion->query($query_pedidos);
if ($result_pedidos && $result_pedidos->num_rows > 0) {
    $row = $result_pedidos->fetch_assoc();
    echo "<p>Columna Estado en Pedidos:<br> <strong>" . $row['Type'] . "</strong></p>";
    
    // Si es ENUM, extraemos los valores permitidos
    if (strpos($row['Type'], 'enum') === 0) {
        preg_match("/^enum\(\'(.*)\'\)$/", $row['Type'], $matches);
        $valores = explode("','", $matches[1]);
        echo "<p>Valores permitidos:</p>";
        echo "<ul>";
        foreach ($valores as $valor) {
            echo "<li><code>" . $valor . "</code></li>";
        }
        echo "</ul>";
    }
}

// Mostrar script de modificación para el error "Data truncated"
echo "<h2>Solución para el error 'Data truncated'</h2>";
echo "<p>Para corregir este error, puedes ejecutar el siguiente código SQL:</p>";
echo "<pre>
-- Primero, verificar los valores actuales de la columna 'estado'
SELECT DISTINCT estado FROM DetallePedidos;

-- Después, modificar la definición de la columna para aceptar cualquier valor que estés utilizando
ALTER TABLE DetallePedidos 
MODIFY COLUMN estado ENUM('pendiente','preparando','listo','servido','completado','finalizado','cancelado') NOT NULL DEFAULT 'pendiente';
</pre>";

// Recomendaciones para conexiones
echo "<h2>Recomendaciones para optimizar conexiones:</h2>";
echo "<ol>";
echo "<li>Usa una única conexión por sesión y ciérrala al finalizar</li>";
echo "<li>Utiliza un pool de conexiones si es posible</li>";
echo "<li>Cierra explícitamente las conexiones con \$conexion->close()</li>";
echo "<li>No abras múltiples conexiones simultáneas en bucles</li>";
echo "</ol>";

// Liberación de conexiones sin usar
echo "<h2>¿Quieres liberar conexiones inactivas?</h2>";
echo "<form action='optimizar_conexiones.php' method='post'>";
echo "<input type='hidden' name='liberar' value='1'>";
echo "<button type='submit' style='background-color: #ff6b6b; color: white; padding: 10px 15px; border: none; cursor: pointer; border-radius: 5px;'>Liberar conexiones inactivas</button>";
echo "</form>";

// Si se solicita liberar conexiones
if (isset($_POST['liberar']) && $_POST['liberar'] == '1') {
    $query_kill = "
        SELECT CONCAT('KILL ', id, ';') as kill_cmd
        FROM information_schema.processlist 
        WHERE command = 'Sleep' AND time > 30
    ";
    
    $result_kill = $conexion->query($query_kill);
    if ($result_kill && $result_kill->num_rows > 0) {
        echo "<h3>Conexiones liberadas:</h3>";
        echo "<ul>";
        $count = 0;
        while ($row = $result_kill->fetch_assoc()) {
            $kill_cmd = $row['kill_cmd'];
            if ($conexion->query($kill_cmd)) {
                echo "<li>Conexión terminada: " . htmlspecialchars($kill_cmd) . "</li>";
                $count++;
            }
        }
        echo "</ul>";
        echo "<p>Se liberaron $count conexiones inactivas.</p>";
    } else {
        echo "<p>No se encontraron conexiones inactivas para liberar.</p>";
    }
}

// Cerramos explícitamente la conexión al finalizar
$conexion->close();
echo "<p style='color:green'>Conexión cerrada explícitamente al finalizar.</p>";
?>

<div style="margin-top: 30px; padding: 15px; border: 1px solid #ccc; background-color: #f5f5f5; border-radius: 5px;">
    <h3>Enlaces útiles:</h3>
    <a href="camarero.php" style="display: inline-block; margin: 10px; padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;">Volver a Camarero</a>
    <a href="cocina.php" style="display: inline-block; margin: 10px; padding: 10px 15px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 5px;">Volver a Cocina</a>
</div>
