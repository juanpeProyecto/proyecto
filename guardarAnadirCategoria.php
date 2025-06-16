<?php
    require_once "bd.php";
    require_once "sesiones.php";
    require_once "funciones.php";
    comprobar_rol("administrador");
    
    $errores = [];
    $exito = '';
    
    // Valido los campos
    if (isset($_REQUEST['nombre']) && isset($_REQUEST['descripcion'])) {
        if (empty($_REQUEST['nombre']) || empty($_REQUEST['descripcion'])) {
            $errores[] = 'Faltan datos obligatorios de la categoría.';
        } else {
            try {
                $nombre = $_REQUEST['nombre'];
                $descripcion = $_REQUEST['descripcion'];
                $resul = anadirCategoria($nombre, $descripcion);
                if ($resul) {
                    $exito = 'Categoría creada correctamente.';
                } else {
                    $errores[] = 'La categoría no ha sido creada.';
                }
            } catch (Exception $e) {
                $errores[] = 'Error al añadir la categoría: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardar y añadir Categoría</title>
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <?php if (isset($exito) && $exito): ?>
        <meta http-equiv="refresh" content="1;url=categorias.php"> <!--nos lleva directamente a las categorias cuando pasan 3 segundos-->
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
            <p class="text-lg mt-3 text-gray-700 font-medium">La categoría se ha creado correctamente</p>
        <?php endif; ?>
    </div>
</body>
</html>
