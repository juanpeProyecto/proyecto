<?php
require_once "bd.php";
require_once __DIR__ . '/enviarNotificacion.php';

// ============== FUNCIONES API COCINA ==============

/**
 * Obtiene un pedido específico con todos sus detalles
 * @param int $codPedido - Código del pedido a obtener
 * @return array - Datos del pedido y sus productos
 */
function obtenerDetallePedido($codPedido) {
    try {
        $conexion = conectarBD();
        
        // Consulta para obtener los datos básicos del pedido
        $consulta = "SELECT codPedido, numMesa, Fecha, Observaciones, Estado, Total 
                    FROM Pedidos 
                    WHERE codPedido = ?";
        
        $stmt = $conexion->prepare($consulta);
        $stmt->bind_param("i", $codPedido);
        $stmt->execute();
        $resultadoPedido = $stmt->get_result();
        
        if ($resultadoPedido->num_rows === 0) {
            return ['error' => 'Pedido no encontrado'];
        }
        
        $pedido = $resultadoPedido->fetch_assoc();
        
        // Consulta para obtener los detalles de los productos del pedido
        $consultaProductos = "SELECT pd.codProducto, pd.cantidad, pd.precioUnitario, pd.observaciones, pd.estado,
                            p.nombre, p.imagenURL 
                            FROM DetallePedidos pd
                            JOIN productos p ON pd.codProducto = p.codProducto
                            WHERE pd.codPedido = ?";
        
        $stmtProductos = $conexion->prepare($consultaProductos);
        $stmtProductos->bind_param("i", $codPedido);
        $stmtProductos->execute();
        $resultadoProductos = $stmtProductos->get_result();
        
        $productos = [];
        while ($producto = $resultadoProductos->fetch_assoc()) {
            $productos[] = $producto;
        }
        
        $pedido['productos'] = $productos;
        
        return $pedido;
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Actualiza el estado de un producto y recalcula el estado global del pedido
 * 
 * @param int $codPedido Código del pedido
 * @param int $codProducto Código del producto
 * @param string $estado Nuevo estado del producto ('pendiente', 'preparando', 'listo')
 * @param string $area Área que realiza la actualización ('cocina', 'barra')
 * @return array Resultado de la operación con información del estado del pedido
 */
function actualizarEstadoProductoCompleto($codPedido, $codProducto, $estado, $area = 'cocina', $numMesa = null, $codEmpleado = null) {
    global $conexion;
    
    // Aseguramos que hay una conexión válida, si no, la creamos
    if (!$conexion || !($conexion instanceof mysqli)) {
        error_log("Conexión nula en actualizarEstadoProductoCompleto, intentando reconectar");
        $conexion = conectarBD();
        
        if (!$conexion) {
            error_log("No se pudo establecer conexión a la base de datos");
            return ['success' => false, 'error' => 'No se pudo conectar a la base de datos'];
        }
        $created_new_connection = true;
    }
    
    // Aseguramos que solo se incluya un archivo para evitar redefinción de funciones
    if (!function_exists('enviarNotificacionWebSocket')) {
        require_once __DIR__ . '/enviarNotificacion.php';
    }
    
    try {
        // Iniciamos una transacción para garantizar la integridad
        $conexion->begin_transaction();
        
        // 1. Actualizamos el estado del producto
        error_log("Actualizando estado del producto - Pedido: $codPedido, Producto: $codProducto, Estado: $estado, Área: $area");
        
        // Primero verificamos si el registro existe
        $sqlCheck = "SELECT COUNT(*) as existe FROM DetallePedidos WHERE codPedido = ? AND codProducto = ?";
        $stmtCheck = $conexion->prepare($sqlCheck);
        $stmtCheck->bind_param("ii", $codPedido, $codProducto);
        $stmtCheck->execute();
        $resultadoCheck = $stmtCheck->get_result()->fetch_assoc();
        
        if ($resultadoCheck['existe'] == 0) {
            throw new Exception("No se encontró el producto $codProducto en el pedido $codPedido");
        }
        
        $sqlUpdateProducto = "UPDATE DetallePedidos SET estado = ? WHERE codPedido = ? AND codProducto = ?";
        $stmtProducto = $conexion->prepare($sqlUpdateProducto);
        if (!$stmtProducto) {
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }
        
        $stmtProducto->bind_param("sii", $estado, $codPedido, $codProducto);
        $resultadoUpdate = $stmtProducto->execute();
        
        if (!$resultadoUpdate) {
            throw new Exception("Error al ejecutar la actualización: " . $stmtProducto->error);
        }
        
        if ($stmtProducto->affected_rows <= 0) {
            throw new Exception("No se afectaron filas al actualizar el estado del producto. ¿El estado era el mismo?");
        }
        
        error_log("Producto actualizado correctamente. Filas afectadas: " . $stmtProducto->affected_rows);
        
        // 2. Contamos productos en diferentes estados para decidir el estado global del pedido
        $sqlContar = "SELECT 
                        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendiente,
                        SUM(CASE WHEN estado = 'preparando' THEN 1 ELSE 0 END) as preparando,
                        SUM(CASE WHEN estado = 'listo' THEN 1 ELSE 0 END) as listos,
                        COUNT(*) as total
                    FROM DetallePedidos 
                    WHERE codPedido = ?";
        $stmtContar = $conexion->prepare($sqlContar);
        $stmtContar->bind_param("i", $codPedido);
        $stmtContar->execute();
        $resultado = $stmtContar->get_result()->fetch_assoc();
        
        // 3. Determinar el nuevo estado del pedido
        $estadoPedido = 'pendiente'; // Estado por defecto
        $pedidoCompleto = false;
        $conteos = $resultado;
        
        // Si hay productos pendientes, el pedido está pendiente
        if ($conteos['pendiente'] > 0) {
            $estadoPedido = 'pendiente';
        }
        
        // Si hay productos en preparación y ninguno pendiente, el pedido está preparándose
        if ($conteos['preparando'] > 0 && $conteos['pendiente'] == 0) {
            $estadoPedido = 'preparando';
        }
        
        // Si todos los productos están listos, actualizamos el estado del pedido a 'listo'
        if ($conteos['total'] > 0 && $conteos['total'] == $conteos['listos']) {
            $estadoPedido = 'listo';
            $pedidoCompleto = true;
        }
        
        // 4. Actualizamos el estado del pedido
        $sqlUpdatePedido = "UPDATE Pedidos SET estado = ? WHERE codPedido = ?";
        $stmtPedido = $conexion->prepare($sqlUpdatePedido);
        $stmtPedido->bind_param("si", $estadoPedido, $codPedido);
        $stmtPedido->execute();
        
        // 5. Obtener información de la mesa para la respuesta
        $sqlMesa = "SELECT numMesa FROM Pedidos WHERE codPedido = ?";
        $stmtMesa = $conexion->prepare($sqlMesa);
        $stmtMesa->bind_param("i", $codPedido);
        $stmtMesa->execute();
        $resultadoMesa = $stmtMesa->get_result()->fetch_assoc();
        $numMesa = $resultadoMesa['numMesa'];
        
        // Confirmar la transacción
        $conexion->commit();
        
        // 6. Enviar notificación al servidor WebSocket
        try {
            // Aseguramos que la función de notificación esté disponible
            if (!function_exists('enviarNotificacionWebSocket')) {
                require_once __DIR__ . '/enviarNotificacion.php';
            }
            
            // Preparamos los datos del mensaje
            $datosNotificacion = [
                'tipo' => 'actualizacionEstadoProducto',
                'codPedido' => $codPedido,
                'codProducto' => $codProducto,
                'estado' => $estado,
                'area' => $area,
                'numMesa' => $numMesa
            ];
            
            // Enviamos la notificación
            enviarNotificacionWebSocket($datosNotificacion);
            error_log('Notificación WebSocket enviada: ' . json_encode($datosNotificacion));
        } catch (\Exception $wsEx) {
            error_log('Error al enviar notificación: ' . $wsEx->getMessage());
            // No lanzamos excepción para que no interfiera con la respuesta JSON
        }
        
        // Devolver resultado exitoso con conexión si se creó una nueva
        $result = [
            'success' => true,
            'mensaje' => 'Estado actualizado correctamente',
            'estadoPedido' => $estadoPedido,
            'estadoProducto' => $estado,
            'pedidoCompleto' => $pedidoCompleto,
            'numMesa' => $numMesa,
            'conteos' => $conteos
        ];
        
        // Incluir la conexión creada en la respuesta si creamos una nueva
        if (isset($created_new_connection) && $created_new_connection) {
            $result['conexion'] = $conexion;
        }
        
        return $result;
        
    } catch (Exception $e) {
        // Si hay error, revertimos la transacción
        $conexion->rollback();
        
        $result = [
            'success' => false,
            'mensaje' => 'Error al actualizar el estado: ' . $e->getMessage()
        ];
        
        // Incluir la conexión creada en la respuesta si creamos una nueva
        if (isset($created_new_connection) && $created_new_connection) {
            $result['conexion'] = $conexion;
        }
        
        return $result;
    }
}

/**
 * Actualiza el estado global de un pedido
 * @param int $codPedido - Código del pedido
 * @param string $estado - Nuevo estado
 * @return array - Resultado de la operación
 */
function cambiarEstadoPedido($codPedido, $estado) {
    try {
        $conexion = conectarBD();
        
        // Actualizar estado del pedido
        $stmt = $conexion->prepare("UPDATE Pedidos SET Estado = ? WHERE codPedido = ?");
        $stmt->bind_param("si", $estado, $codPedido);
        $result = $stmt->execute();
        
        if ($result) {
            return ['success' => true, 'message' => 'Estado actualizado correctamente'];
        } else {
            return ['success' => false, 'error' => 'Error al actualizar el estado del pedido'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Obtiene todos los pedidos pendientes o en preparación para un área específica
 * @param string $area - Área para la que obtener los pedidos (cocina, barra, etc.)
 * @return array - Lista de pedidos con sus productos
 */
function obtenerPedidosPendientesArea($area = '') {
    try {
        // Comprobar que podemos conectar a la base de datos
        $conexion = conectarBD();
        if (!$conexion) {
            error_log("Error al conectar con la base de datos");
            return ['error' => 'Error de conexión'];
        }
        
        // Consulta base para pedidos con estado pendiente o preparando
        $consulta = "SELECT p.codPedido, p.numMesa, p.Fecha, p.Observaciones, p.Estado, p.Total 
                    FROM Pedidos p
                    WHERE p.Estado IN ('pendiente', 'preparando')";
                    
        // Filtrar por área si se especifica
        if ($area === 'cocina') {
            // Para cocina, mostrar solo pedidos con productos pendientes o en preparación para cocina
            $consulta = "SELECT DISTINCT p.codPedido, p.numMesa, p.Fecha, p.Observaciones, p.Estado, p.Total 
                        FROM Pedidos p
                        JOIN DetallePedidos d ON p.codPedido = d.codPedido
                        JOIN Productos pr ON d.codProducto = pr.codProducto
                        WHERE p.Estado IN ('pendiente', 'preparando')
                        AND d.estado IN ('pendiente', 'preparando')
                        AND pr.QuienLoAtiende = 'cocinero'
                        ORDER BY p.Fecha DESC";
        } else {
            // Ordenar por fecha
            $consulta .= " ORDER BY p.Fecha DESC";
        }
        
        $stmt = $conexion->prepare($consulta);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $pedidos = [];
        while ($fila = $resultado->fetch_assoc()) {
            // Convertimos a camelCase para la respuesta JSON
            $pedido = [
                'cod' => (int)$fila['codPedido'],
                'numMesa' => $fila['numMesa'],
                'fecha' => $fila['Fecha'],
                'observaciones' => $fila['Observaciones'],
                'estado' => $fila['Estado'],
                'total' => (float)$fila['Total']
            ];
            
            // Obtenemos los productos del pedido
            $consultaProductos = "SELECT d.codProducto, d.cantidad, d.estado, p.Nombre, p.Descripcion, p.Precio, p.QuienLoAtiende, p.Foto AS Imagen 
                                FROM DetallePedidos d
                                JOIN Productos p ON d.codProducto = p.codProducto
                                WHERE d.codPedido = ?";
                                
            // Filtrar por área si es necesario
            if ($area === 'cocina') {
                $consultaProductos .= " AND p.QuienLoAtiende = 'cocinero'";
            } else if ($area === 'barra') {
                $consultaProductos .= " AND p.QuienLoAtiende = 'camarero'";
            }
            
            $stmtProductos = $conexion->prepare($consultaProductos);
            $stmtProductos->bind_param("i", $fila['codPedido']);
            $stmtProductos->execute();
            $resultadoProductos = $stmtProductos->get_result();
            
            $productos = [];
            while ($filaProducto = $resultadoProductos->fetch_assoc()) {
                $productos[] = [
                    'cod' => (int)$filaProducto['codProducto'],
                    'cantidad' => (int)$filaProducto['cantidad'], 
                    'nombre' => $filaProducto['Nombre'],
                    'descripcion' => $filaProducto['Descripcion'],
                    'precio' => (float)$filaProducto['Precio'],
                    'tipo' => $filaProducto['QuienLoAtiende'],
                    'imagen' => $filaProducto['Imagen'],
                    'estado' => $filaProducto['estado']
                ];
            }
            
            $pedido['productos'] = $productos;
            if (count($productos) > 0) {  // Solo incluimos pedidos con productos
                $pedidos[] = $pedido;
            }
        }
        
        return ['success' => true, 'pedidos' => $pedidos];
        
    } catch (Exception $e) {
        error_log("Error en obtenerPedidosPendientesArea: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}


// Funcion que devuelve un array asociativo con todas las categorias ordenadas por nombre
function cargarCategorias($conexion) {
    $sql = "SELECT * FROM Categorias ORDER BY Nombre ASC";
    $resultado = $conexion->query($sql);
    $categorias = [];
    while ($cat = $resultado->fetch_assoc()) {
        $categorias[] = $cat;
    }
    return $categorias;
}

// Funcion que devuelve un array asociativo con el codigo, nombre y descripcion de todas las categorias ordenadas por nombre
function obtenerCategorias() {
    $conexion = conectarBD();
    $query = "SELECT codCategoria, Nombre, Descripcion FROM Categorias ORDER BY Nombre";
    $resultado = mysqli_query($conexion, $query);
    
    $categorias = [];
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $categorias[] = $fila;
    }
    
    $conexion->close();
    return $categorias;
}

// Funciion que devuelve un array asociativo con los productos de una categoria especifica
function obtenerProductosPorCategoria($codCategoria) {
    $conexion = conectarBD();
    $codCategoria = mysqli_real_escape_string($conexion, $codCategoria);
    
    $query = "SELECT codProducto, Nombre, Descripcion, Precio, Foto FROM Productos 
              WHERE codCategoria = '$codCategoria' AND Stock > 0 
              ORDER BY Nombre";
    
    $resultado = mysqli_query($conexion, $query);
    
    $productos = [];
    while ($fila = mysqli_fetch_assoc($resultado)) {
        // Formateo el precio para mostrar dos decimales
        $fila['Precio'] = number_format($fila['Precio'], 2, ',', '.') . '€';
        $productos[] = $fila;
    }
    
    $conexion->close();
    return $productos;
}

// Función para borrar un producto por su código
function borrarProducto($codProducto) {
    $conexion = conectarBD();
    $sql = "DELETE FROM Productos WHERE codProducto = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $codProducto);
    $res = $stmt->execute();
    $stmt->close();
    $conexion->close();
    return $res;
}

// Función para borrar un empleado por su código
function borrarEmpleado($codEmpleado) {
    $conexion = conectarBD();
    $sql = "DELETE FROM Empleados WHERE codEmpleado = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $codEmpleado);
    $res = $stmt->execute();
    $stmt->close();
    $conexion->close();
    return $res;
}

// Función para añadir una categoría
function anadirCategoria($nombre, $descripcion) {
    $conexion = conectarBD();
    $stmt = $conexion->prepare("INSERT INTO CATEGORIAS (Nombre, Descripcion) VALUES (?, ?)");
    $stmt->bind_param("ss", $nombre, $descripcion);
    $res = $stmt->execute();
    $stmt->close();
    $conexion->close();
    return $res;
}

// Función para añadir un empleado
function anadirEmpleado($nombre, $apellidos, $correo, $telefono, $rol, $contrasena) {
    $conexion = conectarBD();
    $claveHash = password_hash($contrasena, PASSWORD_DEFAULT);
    $stmt = $conexion->prepare("INSERT INTO EMPLEADOS (Nombre, Apellidos, Correo, Telefono, Rol, Clave) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nombre, $apellidos, $correo, $telefono, $rol, $claveHash);
    $res = $stmt->execute();
    $stmt->close();
    $conexion->close();
    return $res;
}

// Función para editar una categoría
function editarCategoria($codCategoria, $nombre, $descripcion) {
    $conexion = conectarBD();
    $stmt = $conexion->prepare("UPDATE Categorias SET Nombre = ?, Descripcion = ? WHERE codCategoria = ?");
    $stmt->bind_param("ssi", $nombre, $descripcion, $codCategoria);
    $res = $stmt->execute();
    $stmt->close();
    $conexion->close();
    return $res;
}

// Funcion para editar un empleado
function editarEmpleado($codEmpleado, $nombre, $apellidos, $correo, $telefono, $rol, $contrasena = null) {
    $conexion = conectarBD();
    if (!empty($contrasena)) {
        $claveHash = password_hash($contrasena, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("UPDATE Empleados SET Nombre=?, Apellidos=?, Correo=?, Clave=?, Telefono=?, Rol=? WHERE codEmpleado=?");
        $stmt->bind_param("ssssssi", $nombre, $apellidos, $correo, $claveHash, $telefono, $rol, $codEmpleado);
    } else {
        $stmt = $conexion->prepare("UPDATE Empleados SET Nombre=?, Apellidos=?, Correo=?, Telefono=?, Rol=? WHERE codEmpleado=?");
        $stmt->bind_param("sssssi", $nombre, $apellidos, $correo, $telefono, $rol, $codEmpleado);
    }
    $res = $stmt->execute();
    $stmt->close();
    $conexion->close();
    return $res;
}

// Funcion para editar un producto
function editarProducto($codProducto, $nombre, $descripcion, $precio, $stock, $codCategoria, $fotoNombre = null) {
    $conexion = conectarBD();
    if ($fotoNombre !== null) {
        $stmt = $conexion->prepare("UPDATE Productos SET Nombre = ?, Descripcion = ?, Precio = ?, Stock = ?, codCategoria = ?, Foto = ? WHERE codProducto = ?");
        $stmt->bind_param("ssdissi", $nombre, $descripcion, $precio, $stock, $codCategoria, $fotoNombre, $codProducto);
    } else {
        $stmt = $conexion->prepare("UPDATE Productos SET Nombre = ?, Descripcion = ?, Precio = ?, Stock = ?, codCategoria = ? WHERE codProducto = ?");
        $stmt->bind_param("ssdisi", $nombre, $descripcion, $precio, $stock, $codCategoria, $codProducto);
    }
    $res = $stmt->execute();
    $stmt->close();
    $conexion->close();
    return $res;
}

// Funcion para añadir un producto
function anadirProducto($nombre, $descripcion, $precio, $stock, $codCategoria, $quienLoAtiende, $fotoNombre) {
    $conexion = conectarBD();
    $stmt = $conexion->prepare("INSERT INTO PRODUCTOS (Nombre, Descripcion, Precio, Stock, codCategoria, QuienLoAtiende, Foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdisss", $nombre, $descripcion, $precio, $stock, $codCategoria, $quienLoAtiende, $fotoNombre);
    $res = $stmt->execute();
    $stmt->close();
    $conexion->close();
    return $res;
}

// Funcion para borrar una categoría por su código
function borrarCategoria($codCategoria) {
    $conexion = conectarBD();
    $sql = "DELETE FROM Categorias WHERE codCategoria = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $codCategoria);
    $res = $stmt->execute();
    $stmt->close();
    $conexion->close();
    return $res;
}

// Funcion que devuelve un array asociativo con un producto por su codigo
function cargarProductoPorCodigo($conexion, $codProducto) {
    $sql = "SELECT * FROM Productos WHERE codProducto = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $codProducto);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $producto = $resultado->fetch_assoc();
    $stmt->close();
    return $producto;
}

// Funcionn que deevuelve un array asociativo con todos los productos junto con el nombre de su categoría ordenados por nombre
function cargarProductos($conexion = null) {
    if ($conexion === null) {
        $conexion = conectarBD();
    }
    $sql = "
        SELECT 
            productos.*, 
            categorias.Nombre AS NombreCategoria
        FROM 
            Productos AS productos
        LEFT JOIN 
            Categorias AS categorias
        ON 
            productos.codCategoria = categorias.codCategoria
        ORDER BY 
            productos.Nombre ASC
    ";
    $resultado = $conexion->query($sql);
    $productos = [];
    while ($producto = $resultado->fetch_assoc()) {
        $productos[] = $producto;
    }
    return $productos;
}

// Funcinon que devuelve un array asociativo con todos los campos de cada empleado ordenados por nombre
function cargarEmpleados($conexion) {
    $sql = "SELECT codEmpleado, Nombre, Apellidos, Correo, Telefono, Rol FROM Empleados ORDER BY Nombre ASC";
    $resultado = $conexion->query($sql);
    $empleados = [];
    while ($emp = $resultado->fetch_assoc()) {
        $empleados[] = $emp;
    }
    return $empleados;
}

//funcion que cambia el estado de una mesa solo si está vacía
function ocuparMesa($numMesa) {
    $conexion = conectarBD();
    $sql = "UPDATE Mesas SET estado='ocupada' WHERE numMesa=? AND estado='vacia'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $numMesa);
    $stmt->execute();
    $stmt->close();
    $conexion->close();
}

//funcion que Obtiene el estado actual de una mesa
function obtenerEstadoMesa($numMesa) {
    $conexion = conectarBD();
    $sql = "SELECT Estado FROM Mesas WHERE numMesa = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $numMesa);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    $stmt->close();
    $conexion->close();
    
    return $fila ? $fila['Estado'] : null;
}

//funcion que actualiza el estado de un producto en un pedido
function actualizarEstadoProducto($estado, $codPedido, $codProducto) {
    $conexion = conectarBD();
    $stmt = $conexion->prepare("UPDATE DetallePedidos SET Estado=? WHERE codPedido=? AND codProducto=?");
    $stmt->bind_param("sii", $estado, $codPedido, $codProducto);
    $resultado = $stmt->execute();
    $stmt->close();
    $conexion->close();
    return $resultado;
}

//funcion que actualiza el campo QuienLoAtiende de todos los productos
function actualizarProductosQuienLoAtiende($quien) {
    $conexion = conectarBD();
    $stmt = $conexion->prepare("UPDATE Productos SET QuienLoAtiende = ?");
    $stmt->bind_param("s", $quien);
    $resultado = $stmt->execute();
    $stmt->close();
    $conexion->close();
    return $resultado;
}

// Función que devuelve todos los pedidos pendientes o en preparación agrupados por mesa y producto
function obtenerPedidosPendientes() {
    $conexion = conectarBD();
    $sql = "SELECT p.numMesa, dp.codPedido, dp.codProducto, pr.Nombre as nombreProducto, dp.Cantidad as cantidad, 
                   dp.Observaciones as ObservacionesProducto, dp.Estado, pr.Precio
            FROM Pedidos p
            JOIN DetallePedidos dp ON p.codPedido = dp.codPedido
            JOIN Productos pr ON dp.codProducto = pr.codProducto
            WHERE (dp.Estado = 'pendiente' OR dp.Estado = 'preparando') AND pr.QuienLoAtiende = 'cocinero'
            ORDER BY p.numMesa ASC, pr.Nombre ASC";
    $resultado = $conexion->query($sql);
    $pedidosAgrupados = [];
    while ($fila = $resultado->fetch_assoc()) {
        $numMesa = $fila['numMesa'];
        $codProducto = $fila['codProducto'];
        $estado = $fila['Estado'];
        $clave = $codProducto . '-' . $estado;
        if (!isset($pedidosAgrupados[$numMesa])) {
            $pedidosAgrupados[$numMesa] = [
                'productos' => []
            ];
        }
        if (!isset($pedidosAgrupados[$numMesa]['productos'][$clave]) || !is_array($pedidosAgrupados[$numMesa]['productos'][$clave])) {
            $pedidosAgrupados[$numMesa]['productos'][$clave] = [
                'codPedido' => $fila['codPedido'],
                'codProducto' => $codProducto,
                'nombre' => $fila['nombreProducto'],
                'cantidad' => (int)$fila['cantidad'],
                'precio' => (float)$fila['Precio'],
                'estado' => $estado,
                'observaciones' => $fila['ObservacionesProducto'] ?? ''
            ];
        } else {
            $pedidosAgrupados[$numMesa]['productos'][$clave]['cantidad'] += (int)$fila['cantidad'];
            if (!empty($fila['ObservacionesProducto'])) {
                $pedidosAgrupados[$numMesa]['productos'][$clave]['observaciones'] .= ', ' . $fila['ObservacionesProducto'];
            }
        }
    }
    $conexion->close();
    return $pedidosAgrupados;
}

function obtenerCategoriaPorId($codCategoria) {
    $conexion = conectarBD();
    $stmt = $conexion->prepare("SELECT * FROM Categorias WHERE codCategoria = ?");
    $stmt->bind_param("i", $codCategoria);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $categoria = $resultado->fetch_assoc();
    $stmt->close();
    $conexion->close();
    return $categoria;
}
function obtenerEmpleadoPorId($codEmpleado) {
    $conexion = conectarBD();
    $stmt = $conexion->prepare("SELECT * FROM Empleados WHERE codEmpleado = ?");
    $stmt->bind_param("i", $codEmpleado);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $empleado = $resultado->fetch_assoc();
    $stmt->close();
    $conexion->close();
    return $empleado;
}
function verificarPedidoExiste($codPedido) {
    $conexion = conectarBD();
    $consulta = "SELECT codPedido FROM pedidos WHERE codPedido = ?";
    $sentencia = $conexion->prepare($consulta);
    $sentencia->bind_param("i", $codPedido);
    $sentencia->execute();
    $resultado = $sentencia->get_result();
    $existe = $resultado->num_rows > 0;
    $sentencia->close();
    $conexion->close();
    return $existe;
}
function actualizarEstadoPedido($codPedido, $nuevoEstado) {
    $conexion = conectarBD();
    $consulta = "UPDATE detallepedidos 
                SET Estado = ? 
                WHERE codPedido = ? 
                AND codProducto IN (
                    SELECT codProducto FROM productos WHERE QuienLoAtiende = 'cocinero'
                )";
    $sentencia = $conexion->prepare($consulta);
    $sentencia->bind_param("si", $nuevoEstado, $codPedido);
    $resultado = $sentencia->execute();
    $sentencia->close();
    $conexion->close();
    return $resultado;
}
function registrarCambioEstadoPedido($codPedido, $nuevoEstado, $codEmpleado) {
    $conexion = conectarBD();
    $consulta = "
        INSERT INTO empleadodetallespedidos (codEmpleado, Fecha, codDetallePedido, cambioEstado)
        SELECT 
            ?, 
            NOW(), 
            dp.codDetallePedido, 
            ?
        FROM detallepedidos dp
        WHERE dp.codPedido = ?
        AND dp.codProducto IN (
            SELECT codProducto FROM productos WHERE QuienLoAtiende = 'cocinero'
        )";
    $sentencia = $conexion->prepare($consulta);
    $sentencia->bind_param("isi", $codEmpleado, $nuevoEstado, $codPedido);
    $resultado = $sentencia->execute();
    $sentencia->close();
    $conexion->close();
    return $resultado;
}
/**
 * Función auxiliar que facilita el envío de notificaciones WebSocket de cambio de estado
 * Esta es solo una envoltura alrededor de la función principal enviarNotificacionWebSocket
 */
function notificarCambioEstadoPedido($codPedido, $nuevoEstado) {
    
    try {
        $datos = [
            "tipo" => "cambioEstado",
            "codPedido" => $codPedido,
            "nuevoEstado" => $nuevoEstado,
            "timestamp" => time()
        ];
        
        return enviarNotificacionWebSocket($datos);
    } catch (\Exception $e) {
        // No interrumpimos el flujo si falla la notificación WebSocket
        error_log("Error al enviar notificación WebSocket: " . $e->getMessage());
    }
}

// Función para enviar notificación de nuevo pedido
function enviarNotificacionNuevoPedido($codPedido) {
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        
        $wsClient = new \WebSocket\Client("ws://localhost:8080");
        
        $mensaje = json_encode([
            "tipo" => "nuevoPedido",
            "codPedido" => $codPedido,
            "timestamp" => time()
        ]);
        
        $wsClient->send($mensaje);
        $wsClient->close();
        return true;
    } catch (\Exception $e) {
        // No interrumpimos el flujo si falla la notificación WebSocket
        error_log("Error al enviar notificación WebSocket: " . $e->getMessage());
        return false;
    }
}

function actualizarEstadoDetallePedido($codDetallePedido, $nuevoEstado) {
    $conexion = conectarBD();
    $sql = "UPDATE DetallePedidos SET Estado = ? WHERE codDetallePedido = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $nuevoEstado, $codDetallePedido);
    $resultado = $stmt->execute();
    $stmt->close();
    $conexion->close();
    return $resultado;
}

function obtenerCodPedidoPorDetalle($codDetallePedido) {
    $conexion = conectarBD();
    $sql = "SELECT codPedido FROM DetallePedidos WHERE codDetallePedido = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $codDetallePedido);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    $stmt->close();
    $conexion->close();
    return $fila ? $fila['codPedido'] : false;
}

function actualizarProductosPorCategoria($codCategoria, $quienAtiende = 'cocinero') {
    $conexion = conectarBD();
    $sql = "UPDATE Productos SET QuienLoAtiende = ? WHERE codCategoria = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $quienAtiende, $codCategoria);
    $stmt->execute();
    $filasAfectadas = $stmt->affected_rows;
    $stmt->close();
    $conexion->close();
    return $filasAfectadas;
}
function obtenerProductosConAtendidoPor() {
    $conexion = conectarBD();
    $sql = "SELECT codProducto, Nombre, QuienLoAtiende FROM Productos";
    $result = $conexion->query($sql);
    $productos = [];
    
    while($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    
    $conexion->close();
    return $productos;
}

function insertarPedido($numMesa, $observaciones, $total, $productos) {
    $conexion = conectarBD();
    $conexion->begin_transaction();
    
    try {
        // Validar que la mesa sea un número válido
        if (!is_numeric($numMesa) || $numMesa <= 0) {
            throw new Exception("Número de mesa no válido");
        }
        
        // Insertar el pedido
        $stmt = $conexion->prepare("INSERT INTO pedidos (numMesa, fecha, observaciones, Total) VALUES (?, NOW(), ?, ?)");
        $stmt->bind_param("isd", $numMesa, $observaciones, $total);
        $stmt->execute();
        $codPedido = $conexion->insert_id;
        $stmt->close();
        
        // Insertar cada producto en DetallePedidos y actualizar stock
        foreach ($productos as $producto) {
            // Descontar stock
            $cantidad = $producto['cantidad'];
            $codProducto = $producto['codProducto'];
            $stmtStock = $conexion->prepare("UPDATE productos SET Stock = Stock - ? WHERE codProducto = ? AND Stock >= ?");
            $stmtStock->bind_param("iii", $cantidad, $codProducto, $cantidad);
            $stmtStock->execute();
            
            if ($stmtStock->affected_rows == 0) {
                throw new Exception("No hay suficiente stock para el producto " . $producto['codProducto']);
            }
            $stmtStock->close();
            
            // Insertar detalle del pedido
            $stmtDetalle = $conexion->prepare("INSERT INTO detallepedidos 
                (codPedido, codProducto, cantidad, precioUnitario, Estado, Observaciones) 
                VALUES (?, ?, ?, ?, 'pendiente', ?)");
            $observacionesProducto = $producto['observaciones'] ?? '';
            $stmtDetalle->bind_param("iiids", 
                $codPedido, 
                $producto['codProducto'], 
                $producto['cantidad'], 
                $producto['precioUnitario'], 
                $observacionesProducto
            );
            $stmtDetalle->execute();
            $stmtDetalle->close();
        }
        
        $conexion->commit();
        
        // Enviar notificación WebSocket para actualización en tiempo real
        enviarNotificacionNuevoPedido($codPedido);
        
        return ['success' => true, 'codPedido' => $codPedido];
        
    } catch (Exception $e) {
        $conexion->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    } finally {
        if (isset($conexion)) {
            $conexion->close();
        }
    }
}

// Función que obtiene los pedidos pendientes o en preparación para cocina
function obtenerPedidosCocina() {
    $conexion = conectarBD();
    
    // Primero obtenemos todos los detalles de pedidos pendientes o en preparación
    $sql = "SELECT 
                GROUP_CONCAT(d.codDetallePedido) as codsPedido, 
                p.numMesa, 
                d.Estado as estado, 
                SUM(d.Cantidad) as cantidad, 
                pr.Nombre as nombre,
                pr.codProducto as codProducto
            FROM detallepedidos d
            JOIN pedidos p ON d.codPedido = p.codPedido
            JOIN productos pr ON d.codProducto = pr.codProducto
            WHERE (d.Estado = 'pendiente' OR d.Estado = 'preparando') AND pr.QuienLoAtiende = 'cocinero'
            GROUP BY p.numMesa, d.Estado, pr.codProducto
            ORDER BY p.numMesa ASC, d.Estado ASC, pr.Nombre ASC";

    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $pedidos = $resultado->fetch_all(MYSQLI_ASSOC);
    
    // Procesamos los resultados para formatearlos adecuadamente
    foreach ($pedidos as &$pedido) {
        // Tomamos el primer ID de la lista concatenada como referencia
        $codsPedidoArray = explode(',', $pedido['codsPedido']);
        $pedido['codPedido'] = $codsPedidoArray[0];
        // Eliminamos el campo temporal
        unset($pedido['codsPedido']);
    }
    
    $conexion->close();
    return $pedidos;
}


