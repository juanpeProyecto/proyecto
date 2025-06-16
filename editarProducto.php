<?php
    require "cabeceraAdmin.php";
    require_once "bd.php";
    require_once "funciones.php";
    $conexion = conectarBD();
    comprobar_rol(["administrador"]);
    // Obtengo el producto por código
    $codProducto = isset($_GET['codProducto']) ? $_GET['codProducto'] : null;
    $producto = null;
    if ($codProducto) {
        $producto = cargarProductoPorCodigo($conexion, $codProducto); 
    }
    // Si no hay producto nos redirige a la pagina de los productos
    if (!$producto) {
        header("Location: productos.php");
        exit;
    }
    // Cargo las categorías
    $categorias = cargarCategorias($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar producto</title>
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>
<body class="bg-gradient-to-br from-[#E0FAF4] to-[#51B2E0] flex flex-col items-center px-2 text-2xl min-h-screen justify-center">
    <div class="w-full max-w-3xl bg-white/95 border-2 border-[#51B2E0] hover:border-[#72E8AC] rounded-2xl shadow-xl p-16">
        <?php if (!$producto): ?>
            <p class="text-red-600 text-center font-bold mt-8">El producto no se ha podiddo encontrar o no es valido</p>
        <?php else: ?>
        <form action="guardarEditarProducto.php" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
            <input type="hidden" name="codProducto" value="<?= htmlspecialchars($producto['codProducto']) ?>">
            <div class="">
                <label for="nombre" class="mb-2 font-semibold text-[#2773A5]">Nombre</label>
                <input type="text" id="nombre" name="nombre" required placeholder="Nombre" value="<?= htmlspecialchars($producto['Nombre']) ?>" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="descripcion" class="mb-2 font-semibold text-[#2773A5]">Descripción</label>
                <input type="text" id="descripcion" name="descripcion" required value="<?= htmlspecialchars($producto['Descripcion']) ?>" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="stock" class="mb-2 font-semibold text-[#2773A5]">Stock</label>
                <input type="number" id="stock" name="stock" min="0" required value="<?= htmlspecialchars($producto['Stock']) ?>" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="precio" class="mb-2 font-semibold text-[#2773A5]">Precio (€)</label>
                <input type="number" step="0.01" id="precio" name="precio" min="0" required value="<?= htmlspecialchars($producto['Precio']) ?>" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="categoria" class="block mb-2 font-semibold text-[#2773A5]">Categoría</label>
                <select id="categoria" name="categoria" required class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5]">
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['codCategoria']) ?>" <?= $cat['codCategoria'] == $producto['codCategoria'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['Nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="">
                <label for="foto" class="mb-2 font-semibold text-[#2773A5]">Foto</label>
                <input type="file" name="foto" id="foto" accept="image/*" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5]">
                <input type="hidden" name="foto_actual" value="<?= ($producto['Foto'] !== '0' && !empty($producto['Foto'])) ? htmlspecialchars($producto['Foto']) : '' ?>">
                <div class="mt-2">
                    <img src="img/productos/<?= htmlspecialchars($producto['Foto']) ?>" alt="Foto actual" class="w-28 h-28 object-cover rounded-xl border border-[#51E080] aspect-square min-w-[7rem] min-h-[7rem] max-w-[7rem] max-h-[7rem]" onerror="this.onerror=null;this.src='img/productos/default.png';" /><!--si hay un error al editar la foto pondremos lafoto default.png por defecto-->
                </div>
            </div>
            <button type="submit" class="bg-[#51E080] hover:bg-[#53E051] text-white font-bold py-4 rounded-md transition text-lg sm:text-xl mt-2 cursor-pointer">Guardar Cambios</button>
            <button type="reset" class="bg-[#51B2E0] hover:bg-[#51E080] text-white font-bold py-4 rounded-md transition text-lg sm:text-xl cursor-pointer">Borrar</button>
        </form>
        <?php
            endif;
        ?>
    </div>
</body>
</html>
