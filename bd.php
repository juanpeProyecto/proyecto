<?php
    /*funcion que conecta con la base de datos*/
    function conectarBD() {
    $host = getenv('MYSQL_HOST') ?: 'localhost';
    $user = getenv('MYSQL_USER') ?: 'root';
    $pass = getenv('MYSQL_PASSWORD') ?: '';
    $db   = getenv('MYSQL_DATABASE') ?: 'Restaurante';
    $port = getenv('MYSQL_PORT') ?: 3306;

    $conexion = new mysqli($host, $user, $pass, $db, $port);
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión a la base de datos: " . $conexion->connect_error);
    }
    $conexion->set_charset("utf8");
    return $conexion;
}
?>
