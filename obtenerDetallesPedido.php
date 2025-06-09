<?php
require_once 'funciones.php';

header('Content-Type: application/json');

// Verificamos que se haya proporcionado un código de pedido
$codPedido = isset($_GET['cod']) ? (int)$_GET['cod'] : 0;

if (!$codPedido) {
    echo json_encode(['error' => 'Código de pedido no válido']);
    exit;
}

// Utilizamos la función modularizada para obtener los detalles del pedido
$resultado = obtenerDetallePedido($codPedido);

if (isset($resultado['error'])) {
    echo json_encode(['error' => $resultado['error']]);
    exit;
}

// Preparamos la respuesta con el formato esperado por el frontend
$respuesta = [
    'codPedido' => $resultado['codPedido'],
    'numMesa' => $resultado['numMesa'],
    'fecha' => $resultado['Fecha'],
    'observaciones' => $resultado['Observaciones'],
    'estado' => $resultado['Estado'],
    'total' => (float)$resultado['Total'],
    'productos' => []
];

// Formatear productos con la estructura esperada
foreach ($resultado['productos'] as $producto) {
    $respuesta['productos'][] = [
        'codProducto' => $producto['codProducto'],
        'nombre' => $producto['nombre'],
        'cantidad' => $producto['cantidad'],
        'precioUnitario' => (float)$producto['precioUnitario'],
        'observaciones' => $producto['observaciones'],
        'imagenURL' => $producto['imagenURL'],
        'estado' => $producto['estado']
    ];
}

echo json_encode($respuesta);
