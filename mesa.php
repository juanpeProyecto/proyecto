<?php
require_once "bd.php";
require_once "funciones.php";

$numMesa = isset($_GET['numMesa']) ? $_GET['numMesa'] : null;//obtengo el numero de la mesa de la URL

if ($numMesa) {
    ocuparMesa($numMesa);//si hay numero de mesa, la ocupo
   
    header("Location: menu.php?numMesa=" . urlencode($numMesa)); // Redirige directamente al menú con el número de mesa
    exit();
} else {
    // Si no hay niuumero de mesa, muestro mensaje de error
    echo 'Mesa no especificada';
    exit();
}

