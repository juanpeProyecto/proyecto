<?php
// Funcion que me comprueba si hemos iniciado sesion  y nos bloquea si no lo hemos hecho
function comprobar_rol($rolesPermitidos = []) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION["usuario"]) || !in_array($_SESSION["rol"], $rolesPermitidos)) {
        header("Location: index.php?redirigido=true");
        exit();
    }
}
?>


