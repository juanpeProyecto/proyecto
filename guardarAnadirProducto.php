<?php
// Incluyo la conexión a la base de datos y control de sesiones
require_once "bd.php";
require_once "sesiones.php";
require_once "funciones.php";
comprobar_rol(["administrador"]);

// Inicializo variables para mensajes
$errores = [];
$exito = '';

// Proceso el formulario solo si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recojo los datos del formulario
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio = $_POST['precio'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $categoria = $_POST['codCategoria'] ?? '';
    $quienLoAtiende = $_POST['quien'] ?? '';

    // si un campo esta vacio lo añado al array de errores
    if (!$nombre || !$descripcion || !$precio || !$stock || !$categoria || !$quienLoAtiende) {
        $errores[] = "Todos los campos son obligatorios.";
    }

    // Proceso la imagen si se ha subido
    $nombreArchivo = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['foto']['tmp_name'];
        $originalName = basename($_FILES['foto']['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $tamanioMax = 2 * 1024 * 1024; // el tamaño maximo que pondre en las fotos es de 2MB

        // Valido el tipo de archivo
        if (!in_array($extension, $permitidas)) {
            $errores[] = 'Solo se permiten imágenes JPG, PNG, GIF o WEBP.';
        }
        // Valido el tamaño
        if ($_FILES['foto']['size'] > $tamanioMax) {
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
        }
    } else {
        $errores[] = 'No se ha seleccionado ninguna imagen o hubo un error al subirla.';
    }

    // Si no hay errores inserto el producto en la base de datos
    if (empty($errores)) {
        try {
            $res = anadirProducto($nombre, $descripcion, $precio, $stock, $categoria, $quienLoAtiende, $nombreArchivo);
            if ($res) {
                $exito = 'Producto añadido correctamente.';
            } else {
                $errores[] = 'El producto no ha sido añadido.';
            }
        } catch (Exception $e) {
            $errores[] = 'Error al añadir el producto: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardar añadir Producto</title>
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <?php if (isset($exito) && $exito): ?>
    <meta http-equiv="refresh" content="1;url=productos.php"> <!--nos lleva directamente a los productos cuando pasa un segundo -->
    <?php endif; ?>
</head>
<body class="bg-[#E0FAF4] flex flex-col items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-4 flex flex-col items-center">
        <?php if (!empty($errores)): ?>
            <div class="w-full p-6 bg-[#72B0E8] text-white rounded-xl text-center font-bold flex items-center justify-center gap-4">
                <span class="material-symbols-outlined text-4xl text-white">error</span>
                <p class="text-3xl font-bold"><?= htmlspecialchars($errores[0]) ?></p>
            </div>
        <?php elseif ($exito): ?>
            <div class="w-full p-6 bg-[#72E8AC] text-white rounded-xl text-center font-bold flex items-center justify-center gap-4">
                <span class="material-symbols-outlined text-4xl text-white scale-150">check_circle</span>
                <p class="text-3xl font-bold"><?= htmlspecialchars($exito) ?></p>
            </div>
            <p class="text-lg mt-3 text-gray-700 font-medium">el producto se ha creado correctamente</p>
        <?php endif; ?>
    </div>
</body>
</html>
