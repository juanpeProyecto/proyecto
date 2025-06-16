<?php
$host = getenv('MYSQL_HOST');
$user = getenv('MYSQL_USER');
$pass = getenv('MYSQL_PASSWORD');
$db = getenv('MYSQL_DATABASE');
$port = getenv('MYSQL_PORT') ?: 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Fallo de conexión: " . $conn->connect_error);
}

$sql = "SELECT codEmpleado, Nombre, Apellidos, Correo, Rol, Clave FROM Empleados";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<h2>Empleados en la base de datos:</h2>";
    echo "<table border='1' cellpadding='6'><tr><th>ID</th><th>Nombre</th><th>Apellidos</th><th>Correo</th><th>Rol</th><th>Clave</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['codEmpleado']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Apellidos']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Correo']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Rol']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Clave']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No hay empleados en la base de datos.";
}
//clave bd nube
//Pk5WcimQ346Jz7mre3wt
$conn->close();
?>
