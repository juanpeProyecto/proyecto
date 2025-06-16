<?php
    require "cabeceraAdmin.php";
    require_once "bd.php";
    require_once "sesiones.php";
    require_once "funciones.php";
    comprobar_rol(["administrador"]);

    $error = '';
    $categoria = null;
    if (!isset($_GET['codCategoria'])) { //si no se ha proporcionado una categoria nos saldra un mensaje de error
        $error = 'No se ha proporcionado una categoría válida';
    } else {
        $codCategoria = intval($_GET['codCategoria']); //hago un casting a entero
        $categoria = obtenerCategoriaPorId($codCategoria);
        if (!$categoria) {
            $error = 'Categoría no encontrada';
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar categoría</title>
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>
<body class="bg-gradient-to-br from-[#E0FAF4] to-[#51B2E0] flex flex-col items-center px-2 text-2xl min-h-screen">
    <div class="w-full max-w-3xl bg-white/95 border-2 border-[#51B2E0] hover:border-[#72E8AC] rounded-2xl shadow-xl p-16">
        <?php if ($error): ?>
            <p class="text-red-600 text-center font-bold mt-8"><?= htmlspecialchars($error) ?></p>
        <?php else: ?> 
        <form action="guardarEditarCategoria.php" method="POST" class="flex flex-col gap-6">
            <input type="hidden" name="codCategoria" value="<?= $codCategoria ?>">
            <div class="">
                <label for="nombre" class="block mb-2 font-semibold text-[#2773A5]">Nombre</label>
                <input type="text" id="nombre" name="nombre" required placeholder="Nombre" value="<?= htmlspecialchars($categoria['Nombre']) ?>" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="descripcion" class="block mb-2 font-semibold text-[#2773A5]">Descripción</label>
                <input type="text" id="descripcion" name="descripcion" required value="<?= htmlspecialchars($categoria['Descripcion']) ?>"class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <button type="submit" class="bg-[#51E080] hover:bg-[#53E051] text-white font-bold py-4 rounded-md shadow transition text-lg sm:text-xl mt-2 cursor-pointer">Guardar Cambios</button>
            <button type="reset" class="bg-[#51B2E0] hover:bg-[#51E080] text-white font-bold py-4 rounded-md shadow transition text-lg sm:text-xl cursor-pointer">Borrar</button>
        </form>
        <?php 
            endif;  //pongo endif para ahorarme poner llaves
        ?>
    </div>
</body>
</html>
