<?php
    require_once "bd.php";
    require_once "funciones.php";
    require_once "sesiones.php";
    comprobar_rol(["administrador"]);

    $codCategoria = intval($_POST['codCategoria']); //hago un casting a int para que no me de error
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];

    try {
        $res = editarCategoria($codCategoria, $nombre, $descripcion);
        if ($res) {
            $exito = 'Categoría editada correctamente.';
        } else {
            $errores[] = 'No se ha podido editar la categoría.';
        }
    } catch (Exception $e) {
        $errores[] = 'Error al actualizar la categoría: ' . htmlspecialchars($e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardar editar categoría</title>
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <?php if (isset($exito) && $exito): ?>
    <meta http-equiv="refresh" content="1;url=categorias.php"> <!--nos lleva directamente a las categorias cuando pasa un segundo -->
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
            <p class="text-lg mt-3 text-gray-700 font-medium">la categoria se ha editado correctamente</p>
        <?php endif; ?>
    </div>
</body>
</html>
