<?php
// Funcion que me comprueba si hemos iniciado sesion 
function comprobar_rol($rolesPermitidos = []) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION["usuario"]) || !in_array($_SESSION["rol"], $rolesPermitidos)) {
        header("Location: login.php?redirigido=true");
        exit();
    }
}


