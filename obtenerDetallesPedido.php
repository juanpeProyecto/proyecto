<?php
require_once "funciones.php";
require_once "bd.php"; // Me aseguro de que la conexión a BD esté disponible si funciones.php la necesita globalmente.

header('Content-Type: application/json');

$codPedido = isset($_GET['cod']) ? (int)$_GET['cod'] : 0;

if ($codPedido > 0) {
    $detallePedido = obtenerDetallePedido($codPedido);
    if (isset($detallePedido['error'])) {
        http_response_code(404); // Le envio el código de error apropiado
        echo json_encode($detallePedido);
    } else {
        echo json_encode($detallePedido);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Código de pedido no proporcionado o inválido.']);
}
?>
