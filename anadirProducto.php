<?php
require "cabeceraAdmin.php";
require_once "bd.php";
require_once "sesiones.php";
//comprobar_rol(["administrador"]);

require_once "funciones.php";
// Obtengo todas las categorias
$conexion = conectarBD();
$categorias = cargarCategorias($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir Producto nuevo</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link href="src/output.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-[#E0FAF4] via-[#72E8D4] to-[#51B2E0] flex flex-col items-center px-2 text-2xl min-h-screen">
    <div class="w-full max-w-3xl bg-white/95 border-2 border-[#51B2E0] rounded-2xl shadow-xl p-16">
            <!-- enctype="multipart/form-data" lo que hace es que el formulario pueda enviar archivos -->
        <form action="guardarAnadirProducto.php" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
            <div class="">
                <label for="nombre" class="mb-2 font-semibold text-[#2773A5]">Nombre</label>
                <input type="text" id="nombre" name="nombre" required placeholder="Nombre" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC]  focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="descripcion" class="mb-2 font-semibold text-[#2773A5]">Descripción</label>
                <input type="text" id="descripcion" name="descripcion" required placeholder="Descripción" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="precio" class="mb-2 font-semibold text-[#2773A5]">Precio (€)</label>
                <input type="number" step="0.01" id="precio" name="precio" required placeholder="Precio" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="stock" class="mb-2 font-semibold text-[#2773A5]">Stock</label>
                <input type="number" id="stock" name="stock" required placeholder="Stock" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="codCategoria" class="mb-2 font-semibold text-[#2773A5]">Categoría</label>
                <select id="codCategoria" name="codCategoria" required class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5]">
                    <option value="">Selecciona una categoría</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['codCategoria'] ?>"><?= htmlspecialchars($cat['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="">
            <label for="quien" class="mb-2 font-semibold text-[#2773A5]">¿Quién lo atiende?</label>
            <select id="quien" name="quien" required class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5]">
                <option value="">Selecciona quién lo atiende</option>
                <option value="barra">Barra</option>
                <option value="camarero">Camarero</option>
                <option value="cocinero">Cocinero</option>
            </select>
        </div>
        <div class="flex flex-col gap-2">
            <label for="foto" class="mb-2 font-semibold text-[#2773A5]">Foto</label>
            <div class="flex items-center gap-4">
                <label class="bg-[#72E8AC] text-[#256353] px-4 py-2 rounded-lg cursor-pointer font-bold transition hover:bg-[#51E080]" for="foto">
                    Seleccionar imagen
                </label>
                <span id="nombreArchivo" class="text-[#2773A5]">Ningún archivo seleccionado</span>
            </div>
            <input type="file" id="foto" name="foto" accept="image/*" class="hidden">
        </div>     
        <button type="submit" class="w-full flex flex-row items-center justify-center gap-2 bg-[#72E8AC] hover:bg-[#72E884] text-[#256353] hover:text-white transition-all duration-200 px-8 py-4 rounded-2xl font-bold text-xl text-center border-2 border-[#72E884] mt-6">Añadir Producto</button>
        <button type="reset" class="w-full flex flex-row items-center justify-center gap-2 bg-[#51B2E0] hover:bg-[#51E080] text-white font-bold py-4 rounded-2xl shadow transition text-lg sm:text-xl cursor-pointer">Borrar</button>
    </div>
</body>
<script src="js/estiloBotonSeleccionarArchivo.js"></script>
</html>
