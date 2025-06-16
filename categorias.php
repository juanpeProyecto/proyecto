<?php
    require "cabeceraAdmin.php"; 
    require_once "bd.php";
    require_once "funciones.php";
    require "sesiones.php";
    comprobar_rol(["administrador"]);
    $conexion = conectarBD();
    $todasCategorias = cargarCategorias($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Categorías</title> 
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>
<body class="bg-gradient-to-br from-[#E0FAF4] to-[#51B2E0] min-h-screen w-full flex flex-col items-center overflow-x-hidden px-2 sm:px-0 text-2xl">
    <div class="w-full max-w-6xl bg-[#E0FAF4] border-4 border-[#72E8AC] rounded-2xl mt-6 p-4 flex flex-col items-center">
        <h1 class="text-4xl font-bold text-[#256353] mb-6 w-full text-center">Gestión de Categorías</h1>
        <?php
            if (count($todasCategorias) > 0)  //si hay categorias creo la tabla con sus columnas
            {
        ?>
        <div class="w-full">
            <table class="w-full text-left overflow-hidden text-xs rounded-xl sm:text-base md:text-lg lg:text-2xl shadow-xl border-separate border-spacing-0 hidden lg:table">
                <tr class="bg-gradient-to-r from-[#72E8AC]/90 to-[#51E080]/80 text-[#256353]">
                    <th class="text-center py-3 sm:py-7 px-2 sm:px-10 font-bold border-b-4 border-[#51E080] sm:text-2xl whitespace-nowrap">Nombre</th>
                    <th class="text-center py-3 sm:py-7 px-2 sm:px-10 font-bold border-b-4 border-[#51E080] sm:text-2xl whitespace-nowrap">Descripción</th>
                    <th class="py-3 sm:py-7 px-2 sm:px-10 text-center font-bold border-b-4 border-[#51E080] sm:text-2xl whitespace-nowrap">Acciones</th>
                </tr>
                <?php 
                foreach ($todasCategorias as $cat): //recorro todas las categorias
                ?>
                <tr class="border-b">
                    <td class="text-center py-3 sm:py-7 px-2 sm:px-4 font-semibold text-[#256353] text-xs sm:text-base md:text-lg lg:text-2xl transition-transform duration-200 bg-[#E0FAF4]/80 whitespace-normal">
                        <?= htmlspecialchars($cat['Nombre']) ?>
                    </td>
                    <td class="text-center py-3 sm:py-7 px-1 sm:px-4 text-[#21476B] text-xs sm:text-base md:text-lg lg:text-2xl transition-transform duration-200 bg-[#E0FAF4]/80 whitespace-normal">
                        <?= 
                            htmlspecialchars($cat['Descripcion'])
                        ?>
                    </td>
                    <td class="py-3 sm:py-7 px-1 sm:px-4 flex flex-col sm:flex-row gap-2 sm:gap-4 items-center justify-center text-base sm:text-xl md:text-2xl lg:text-3xl bg-[#E0FAF4]/80 whitespace-normal w-full">
                        <a href="editarCategoria.php?codCategoria=<?= $cat['codCategoria'] ?>" title="Editar" class="flex flex-row sm:flex-col items-center justify-center gap-2 bg-[#72E8AC] hover:scale-105 text-[#256353] hover:text-white transition-all duration-200 px-4 py-3 rounded-2xl font-bold text-xl text-center border-2 border-[#72E884] w-full sm:w-auto">
                            <span class="material-symbols-outlined scale-125 hover:text-white transition-all duration-200">edit</span>
                        </a>
                        <a href="borrarCategoria.php?codCategoria=<?= $cat['codCategoria'] ?>" title="Borrar" class="flex flex-row sm:flex-col items-center justify-center gap-2 bg-[#72B0E8] hover:scale-105 hover:text-white transition-all duration-200 px-4 py-3 rounded-2xl font-bold text-xl text-center border-2 border-[#72E8AC] w-full sm:w-auto">
                            <span class="material-symbols-outlined scale-125 hover:text-white transition-all duration-200">delete</span>
                        </a>
                    </td>
                </tr>
                <?php   
                    endforeach; //utilizo endforeach para cerrar el bucle sin tener la necesidad de usar llaves
                ?>
            </table>
        </div>
        <!-- Vista para móvil/tablet: tarjetas -->
        <div class="flex flex-col gap-8 lg:hidden mt-8">
            <?php foreach ($todasCategorias as $cat): ?>
                <div class="bg-white/90 rounded-3xl p-6 shadow-xl flex flex-col gap-6">
                    <div class="flex flex-col gap-2">
                        <span class="font-bold text-[#256353] text-2xl leading-tight">
                            <?= htmlspecialchars($cat['Nombre']) ?>
                        </span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <span class="font-bold text-[#256353]">Descripción:</span>
                        <span class="text-[#21476B] text-lg break-words"><?= htmlspecialchars($cat['Descripcion']) ?></span>
                    </div>
                    <div class="flex flex-row gap-4 mt-6 w-full">
                        <a href="editarCategoria.php?codCategoria=<?= $cat['codCategoria'] ?>" title="Editar" class="flex flex-row items-center justify-center gap-2 bg-[#72E8AC] hover:bg-[#72E884] text-[#256353] hover:text-white transition-all duration-200 px-6 py-3 rounded-2xl font-bold text-xl text-center border-2 border-[#72E884] w-full">
                            <span class="material-symbols-outlined scale-125 hover:text-white transition-all duration-200">edit</span>
                        </a>
                        <a href="borrarCategoria.php?codCategoria=<?= $cat['codCategoria'] ?>" title="Borrar" class="flex flex-row items-center justify-center gap-2 bg-[#72B0E8] hover:bg-[#51B2E0] hover:text-white transition-all duration-200 px-6 py-3 rounded-2xl font-bold text-xl text-center border-2 border-[#72E8AC] w-full">
                            <span class="material-symbols-outlined scale-125 hover:text-white transition-all duration-200">delete</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="w-full flex justify-center items-center mt-12">
            <a href="anadirCategoria.php" class="w-full sm:w-auto flex flex-row items-center justify-center gap-2 bg-[#72E8AC] hover:bg-[#72E884] text-[#256353] hover:text-white transition-all duration-200 px-4 sm:px-8 py-2 sm:py-4 rounded-2xl font-bold text-base sm:text-xl text-center border-2 border-[#72E884]">
                <span class="material-symbols-outlined scale-125 hover:text-white transition-all duration-200">add_circle</span>
                <span class="text-center hover:text-white transition-all duration-200">Añadir categoría</span>
            </a>
        </div>
    </div>
        <?php 
            } else { 
        ?>
            <p class="text-center text-red-600 text-xl">No se pudieron cargar las categorías.</p>
        <?php 
        } 
        ?>
    </div>
</body>
</html>
<?php 
    $conexion->close(); 
?>
