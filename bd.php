<?php
    /*funcion que conecta con la base de datos*/
    function conectarBD() {
        $conexion = new mysqli("localhost", "root", "", "Restaurante");
        if ($conexion->connect_error) {
            throw new Exception("Error de conexión a la base de datos: " . $conexion->connect_error);
        }
        $conexion->set_charset("utf8");
        return $conexion;
    }
?>
