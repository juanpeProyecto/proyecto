
<?php
    require "cabeceraAdmin.php";
    require_once "bd.php";
    require_once "funciones.php";
    //require "sesiones.php";
    //comprobar_rol(["administrador"]); 
    $conexion = conectarBD();
    $todosProductos = cargarProductos($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Productos</title> 
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>
<body class="bg-gradient-to-br from-[#E0FAF4] via-[#72E8D4] to-[#51B2E0] min-h-screen w-full flex flex-col items-center overflow-x-hidden px-2 sm:px-0 text-2xl">
    <div class="w-full max-w-7xl bg-[#E0FAF4]/95 border-4 border-[#72E8AC] rounded-[2.5rem] mt-6 shadow-[0_8px_40px_0_rgba(81,178,224,0.18)] p-4 flex flex-col items-center">
        <h1 class="text-4xl font-bold text-[#256353] mb-6 w-full text-center">Gestión de Productos</h1>
        <?php 
            if (count($todosProductos) > 0) { //si hay productos
        ?>
        <div class="w-full rounded-3xl">
        <table class="w-full text-left rounded-3xl overflow-hidden text-xs sm:text-base md:text-lg lg:text-2xl shadow-xl border-separate border-spacing-0 hidden lg:table">
                <tr class="bg-gradient-to-r from-[#72E8AC]/90 to-[#51E080]/80 text-[#256353]">
                    <th class="py-2 md:py-4 lg:py-6 px-2 md:px-3 lg:px-4 font-bold uppercase tracking-widest border-b-4 border-[#51E080] text-base sm:text-2xl whitespace-nowrap text-center">Foto</th>
                    <th class="py-2 md:py-4 lg:py-6 px-2 md:px-3 lg:px-4 font-bold uppercase tracking-widest border-b-4 border-[#51E080] text-base sm:text-2xl whitespace-nowrap text-center">Nombre</th>
                    <th class="py-2 md:py-4 lg:py-6 px-2 md:px-3 lg:px-4 font-bold uppercase tracking-widest border-b-4 border-[#51E080] text-base sm:text-2xl whitespace-nowrap text-center">Categoría</th>
                    <th class="py-2 md:py-4 lg:py-6 px-2 md:px-3 lg:px-4 font-bold uppercase tracking-widest border-b-4 border-[#51E080] text-base sm:text-2xl whitespace-nowrap text-center">Descripción</th>
                    <th class="py-2 md:py-4 lg:py-6 px-2 md:px-3 lg:px-4 font-bold uppercase tracking-widest border-b-4 border-[#51E080] text-base sm:text-2xl whitespace-nowrap text-center">Stock</th>
                    <th class="py-2 md:py-4 lg:py-6 px-2 md:px-3 lg:px-4 font-bold uppercase tracking-widest border-b-4 border-[#51E080] text-base sm:text-2xl whitespace-nowrap text-center">Precio</th>
                    <th class="py-2 md:py-4 lg:py-6 px-2 md:px-3 lg:px-4 text-center font-bold uppercase tracking-widest border-b-4 border-[#51E080] text-base sm:text-2xl whitespace-nowrap">Acciones</th>
                </tr>
                <?php 
                    foreach ($todosProductos as $prod): //recorreo todos los productos
                ?>
                <tr class="border-b last:border-b-0 bg-[#E0FAF4]">
                    <td class="py-2 md:py-4 lg:py-6 px-2 md:px-3 lg:px-4 bg-[#E0FAF4]/80 whitespace-normal text-center flex justify-center items-center">
                        <img src="img/productos/<?= htmlspecialchars($prod['Foto']) ?>"
                        alt="<?= htmlspecialchars($prod['Nombre']) ?>"
                        class="w-28 h-28 object-cover rounded-xl shadow border border-[#51E080]"
                        style="aspect-ratio:1/1; min-width:7rem; min-height:7rem; max-width:7rem; max-height:7rem;"
                        onerror="this.onerror=null;this.src='img/productos/default.png';" />
                    </td>
                    <td class="py-2 md:py-4 lg:py-6 px-2 md:px-3 lg:px-4 font-semibold text-[#256353] text-xs sm:text-base md:text-lg lg:text-2xl bg-[#E0FAF4]/80 whitespace-normal text-center">
                        <?= htmlspecialchars($prod['Nombre']) /*inserto el nombre*/?>
                    </td>
                    <td class="py-2 md:py-4 lg:py-6 px-2 md:px-3 lg:px-4 text-[#256353] text-xs sm:text-base md:text-lg lg:text-2xl bg-[#E0FAF4]/80 whitespace-normal text-center">
                        <?= htmlspecialchars($prod['NombreCategoria'] ?? 'Sin categoría') ?>
                    </td>
                    <td class="py-2 md:py-4 lg:py-6 px-2 md:px-3 lg:px-4 text-[#21476B] text-xs sm:text-base md:text-lg lg:text-2xl bg-[#E0FAF4]/80 whitespace-normal text-center">
                        <?= htmlspecialchars($prod['Descripcion']) /*inserto la descripcion*/?>
                    </td>
                    <td class="py-2 md:py-4 lg:py-6 px-2 md:px-3 lg:px-4 text-[#21476B] text-xs sm:text-base md:text-lg lg:text-2xl bg-[#E0FAF4]/80 whitespace-normal text-center">
                        <?= htmlspecialchars($prod['Stock']) /*inserto el valor del stock*/?>
                    </td>
                    <td class="py-2 md:py-4 lg:py-6 px-2 md:px-3 lg:px-4 text-[#21476B] text-xs sm:text-base md:text-lg lg:text-2xl bg-[#E0FAF4]/80 whitespace-normal text-center">
                        <?= htmlspecialchars($prod['Precio']) /*inserto el valor del precio*/?> €
                    </td>
                    <td class="py-2 md:py-4 lg:py-6 px-2 md:px-3 lg:px-4 bg-[#E0FAF4]/80 flex flex-col sm:flex-row gap-2 sm:gap-4 justify-center items-center text-base sm:text-xl md:text-2xl lg:text-3xl w-full">
                        <a href="editarProducto.php?codProducto=<?= $prod['codProducto'] ?>" class="flex flex-row sm:flex-col items-center justify-center gap-2 bg-[#72E8AC] hover:scale-105 text-[#256353] hover:text-white transition-all duration-200 px-4 py-3 rounded-2xl font-bold text-xl text-center border-2 border-[#72E884] w-full sm:w-auto"><span class="material-symbols-outlined scale-125 hover:text-white transition-all duration-200">edit</span></a>
                        <a href="borrarProducto.php?codProducto=<?= $prod['codProducto'] ?>" class="flex flex-row sm:flex-col items-center justify-center gap-2 bg-[#72B0E8] hover:scale-105 hover:text-white transition-all duration-200 px-4 py-3 rounded-2xl font-bold text-xl text-center border-2 border-[#72E8AC] w-full sm:w-auto"><span class="material-symbols-outlined scale-125 hover:text-white transition-all duration-200">delete</span></a>
                    </td>
                </tr>
                <?php 
                    endforeach; 
                ?>
        </table>
        <!-- Vista para móvil -->
        <div class="flex flex-col gap-4 lg:hidden mt-6">
            <?php foreach ($todosProductos as $prod): ?>
                <div class="bg-white/90 rounded-xl p-4 shadow flex flex-col gap-2">
                    <div class="flex gap-4 items-center">
                        <img src="img/productos/<?= htmlspecialchars($prod['Foto']) ?>" alt="<?= htmlspecialchars($prod['Nombre']) ?>" class="w-20 h-20 object-cover rounded-xl border border-[#51E080]" onerror="this.onerror=null;this.src='img/productos/default.png';" />
                        <div class="flex flex-col">
                            <div class="font-bold text-[#256353] text-lg mb-1"> <?= htmlspecialchars($prod['Nombre']) ?> </div>
                            <div class="text-sm text-[#51B2E0]"> <?= htmlspecialchars($prod['NombreCategoria'] ?? 'Sin categoría') ?> </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="font-bold">Descripción:</span> <span class="text-[#21476B]"> <?= htmlspecialchars($prod['Descripcion']) ?> </span>
                    </div>
                    <div>
                        <span class="font-bold">Stock:</span> <?= htmlspecialchars($prod['Stock']) ?>
                    </div>
                    <div>
                        <span class="font-bold">Precio:</span> <?= htmlspecialchars($prod['Precio']) ?> €
                    </div>
                    <div class="flex flex-row gap-2 mt-3 w-full">
                        <a href="editarProducto.php?codProducto=<?= $prod['codProducto'] ?>" class="flex-1 flex items-center justify-center gap-2 bg-[#72E8AC] hover:scale-105 text-[#256353] hover:text-white transition-all duration-200 px-3 py-2 rounded-2xl font-bold text-base text-center border-2 border-[#72E884]">
                            <span class="material-symbols-outlined scale-125">edit</span>
                        </a>
                        <a href="borrarProducto.php?codProducto=<?= $prod['codProducto'] ?>" class="flex-1 flex items-center justify-center gap-2 bg-[#72B0E8] hover:scale-105 hover:text-white transition-all duration-200 px-3 py-2 rounded-2xl font-bold text-base text-center border-2 border-[#72E8AC]">
                            <span class="material-symbols-outlined scale-125">delete</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
            } else { 
        ?>
            <p class="text-[#256353] text-2xl mt-8">No hay productos registrados.</p>
        <?php 
            } 
        ?>
        <div class="w-full flex justify-center items-center mt-6">
            <a href="anadirProducto.php" class="w-auto flex flex-row items-center justify-center gap-2 bg-[#72E8AC] hover:bg-[#72E884] text-[#256353] hover:text-white transition-all duration-200 px-4 sm:px-8 py-2 sm:py-4 rounded-2xl font-bold text-base sm:text-xl text-center border-2 border-[#72E884]">
                <span class="material-symbols-outlined scale-125 hover:text-white transition-all duration-200">add_circle</span>
                <span class="text-center hover:text-white transition-all duration-200">Añadir producto</span>
            </a>
        </div>
    </div>
</body>
</html>
