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
echo "¡Conexión OK!";
?>