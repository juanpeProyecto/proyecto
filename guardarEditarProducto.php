<?php
// Incluimos la conexión a la base de datos y control de sesiones
require_once "bd.php";
require_once "sesiones.php";
require_once "funciones.php";
// comprobar_rol(["administrador"]);

// Inicializamos variables para mensajes
$errores = [];
$exito = '';

// Procesamos el formulario solo si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recogemos los datos del formulario
    $codProducto = $_POST['codProducto'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio = $_POST['precio'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $foto_actual = $_POST['foto_actual'] ?? '';

    // Validación básica de campos obligatorios
    if (!$codProducto || !$nombre || !$descripcion || !$precio || !$stock || !$categoria) {
        $errores[] = "Todos los campos son obligatorios excepto la foto.";
    }

    // Variable para almacenar el nombre de la imagen
    $nombreArchivo = $foto_actual;

    // Proceso la imagen si se ha subido una nueva
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK && $_FILES['foto']['size'] > 0) {
        $tmpName = $_FILES['foto']['tmp_name'];
        $originalName = basename($_FILES['foto']['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        // Valido el tipo de archivo
        if (!in_array($extension, $permitidas)) {
            $errores[] = 'Solo se permiten imágenes JPG, PNG, GIF o WEBP.';
        }
        // Valido el tamaño
        if ($_FILES['foto']['size'] > $maxSize) {
            $errores[] = 'La imagen es demasiado grande. Máximo 2MB.';
        }
        // Si pasa las validaciones, muevo la imagen
        if (empty($errores)) {
            $rutaDestino = 'img/productos/';
            if (!is_dir($rutaDestino)) {
                mkdir($rutaDestino, 0777, true);
            }
            $nombreArchivo = uniqid('img_') . '.' . $extension;
            $rutaCompleta = $rutaDestino . $nombreArchivo;
            if (!move_uploaded_file($tmpName, $rutaCompleta)) {
                $errores[] = 'Error al guardar la imagen en el servidor.';
            }
            
            // Si habbia una imagen anterior y no es la default, la eliminamos
            if (!empty($foto_actual) && $foto_actual !== 'default.png' && file_exists($rutaDestino . $foto_actual)) {
                unlink($rutaDestino . $foto_actual);
            }
        }
    }

    // Si no hay errores, actualizo el producto en la base de datos
    if (empty($errores)) {
        try {
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK && $_FILES['foto']['size'] > 0) {
                $res = editarProducto($codProducto, $nombre, $descripcion, $precio, $stock, $categoria, $nombreArchivo);
            } else {
                $res = editarProducto($codProducto, $nombre, $descripcion, $precio, $stock, $categoria);
            }
            if ($res) {
                $exito = "Producto editado correctamente.";
            } else {
                $errores[] = "Error al editar el producto.";
            }
        } catch (Exception $e) {
            $errores[] = 'Error al editar el producto: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardar editar producto</title>
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <?php if (isset($exito) && $exito): ?>
    <meta http-equiv="refresh" content="1;url=productos.php"> <!--nos lleva directemente a los productos cuando pasan 3 segundos -->
    <?php endif; ?>
</head>
<body class="bg-[#E0FAF4] flex flex-col items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-4 flex flex-col items-center">
        <?php if (!empty($errores)): ?>
            <div class="w-full p-6 bg-[#72B0E8] text-white rounded-xl text-center font-bold shadow-lg flex items-center justify-center gap-4">
                <span class="material-symbols-outlined text-4xl text-white">error</span>
                <p class="text-3xl font-bold"><?= htmlspecialchars($errores[0]) ?></p>
            </div>
        <?php elseif ($exito): ?>
            <div class="w-full p-6 bg-[#72E8AC] text-white rounded-xl text-center font-bold shadow-lg flex items-center justify-center gap-4">
                <span class="material-symbols-outlined text-4xl text-white scale-150">check_circle</span>
                <p class="text-3xl font-bold"><?= htmlspecialchars($exito) //obtengo el mensaje de exito?> </p>
            </div>
            <p class="text-lg mt-3 text-gray-700 font-medium">Redirigiendo automáticamente a los productos...</p>
        <?php else: ?>
            <div class="w-full p-6 bg-[#72B0E8] text-white rounded-xl text-center font-bold shadow-lg flex items-center justify-center gap-4">
                <span class="material-symbols-outlined text-4xl text-white">info</span>
                <p class="text-3xl font-bold">No se recibieron datos para procesar.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>