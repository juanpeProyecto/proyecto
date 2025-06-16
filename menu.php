<?php
  require_once "funciones.php";

  // Obtengo todas las categoriias
  $categorias = obtenerCategorias();
  // Obtengo la categoria que ha seleccionado el usuario
  $categoriaSeleccionada = isset($_GET['categoria']) ? intval($_GET['categoria']) : (count($categorias) > 0 ? $categorias[0]['codCategoria'] : 0);

  // Obtengo todos los productos de la categoria seleccionada
  $productos = [];
  if ($categoriaSeleccionada > 0) {
      $productos = obtenerProductosPorCategoria($categoriaSeleccionada);
  }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <title>Menú del restaurante</title>
</head>
<body class="bg-gradient-to-br from-[#E0FAF4] to-[#51B2E0] min-h-screen w-full overflow-x-hidden px-1 sm:px-0">
    <?php if (isset($_GET['numMesa'])): //si hay una mesa seleccionada ?>
        <div class="fixed top-4 left-4 bg-[#72E8AC] text-[#256353] px-4 py-2 rounded-xl shadow-lg font-bold text-lg border-2 border-[#51B2E0] z-50">
            Mesa <?php echo htmlspecialchars($_GET['numMesa']); //muestro el nummero de mesa para que el cliente vea el numero de mesa que tiene?>
        </div>
    <?php endif; ?>
    <a href="carrito.php<?php echo isset($_GET['numMesa']) ? '?numMesa=' . urlencode($_GET['numMesa']) : ''; //si hay una mesa seleccionada la paso?>" id="btnCarrito" class="fixed top-4 right-4 z-50 flex items-center gap-2 bg-[#51B2E0] hover:bg-[#72E8AC] text-white hover:text-[#256353] transition-all duration-200 px-4 py-2 rounded-xl shadow-lg font-semibold text-lg border-2 border-[#51B2E0]">
        <span class="material-symbols-outlined">shopping_cart</span>
        <span id="contadorCarrito" class="items-center justify-center w-8 h-8 p-1 bg-white text-red-500 rounded-full text-center text-sm font-bold">0</span>
    </a>
    <div class="w-full max-w-sm sm:max-w-2xl md:max-w-4xl mx-auto mt-0 p-2 sm:p-4 md:p-6 rounded-2xl shadow-lg bg-white/80">
      <header class="mb-6 md:mb-10">
        <div class="w-full flex justify-center">
          <img src="img/logo-proyecto.png" class="max-w-[100px] sm:max-w-[280px] w-full h-auto" alt="Logo del restaurante">
        </div>
      </header>
      <div id="avisoCarrito" class="fixed top-20 right-4 z-50 bg-[#51B2E0] text-white px-4 py-2 rounded-lg shadow-lg hidden transition-all duration-300"></div>
      <main>
        <div class="text-center mb-3">
          <h1 class="text-3xl font-bold text-[#256353]">Menú</h1>
          <h3 class="text-[#21476B] mt-2">Selecciona una categoría para ver los productos disponibles</h3>
        </div>

        <!-- Categorias -->
        <div class="flex flex-wrap justify-center gap-2 sm:gap-4 md:gap-6 mb-6">
          <?php foreach ($categorias as $categoria): ?>
            <a href="menu.php?categoria=<?php echo $categoria['codCategoria']; ?><?php echo isset($_GET['numMesa']) ? '&numMesa=' . urlencode($_GET['numMesa']) : ''; //si hay una mesa seleccionada, la pasamos?>"
               class="px-4 py-2 rounded-xl font-bold text-[#256353] 
                   <?php echo ($categoriaSeleccionada == $categoria['codCategoria']) ? //si la categoria seleccionada es la misma que la categoria actual
                       'bg-[#72E8AC]/20 border-[#72E8AC]' : 
                       'bg-[#E0FAF4] hover:bg-[#72E8AC]/30 border-transparent hover:border-[#72E8AC]'; ?> 
                   border-b-4 transition-all duration-200">
              <?php echo htmlspecialchars($categoria['Nombre']); ?>
            </a>
          <?php endforeach; ?>
        </div>
        <!-- Productos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-6">
          <?php if (empty($productos)): // si no hay productos en la categoria seleccionada ponemos mensaje para indicar que no hay productos ?>
            <div class="col-span-full text-center text-[#256353]">No hay productos en esta categoría.</div>
          <?php else: ?>
            <?php foreach ($productos as $producto)://recorro todos los productos ?> 
              <div class="bg-white/80 rounded-2xl shadow-lg p-3 sm:p-5 flex flex-col justify-between gap-2 items-start border border-[#72E8AC]/30 h-[400px]">
                <?php if (!empty($producto['Foto'])): // si hay foto del producto la mostramos ?>
                <div class="w-full h-56 flex justify-center items-center bg-white rounded-xl overflow-hidden mb-3">
                    <img src="img/productos/<?php echo htmlspecialchars($producto['Foto']); ?>"
                         alt="<?php echo htmlspecialchars($producto['Nombre']);?>"
                         class="object-contain max-h-full max-w-full m-auto">
                </div>
                <?php endif; ?>
                <div class="text-base sm:text-lg md:text-xl font-bold text-[#256353] truncate w-full"><?php echo htmlspecialchars($producto['Nombre']);//echo para que se muestre el nombre del producto ?></div>
                <div class="text-sm sm:text-base md:text-lg text-[#21476B] truncate w-full"><?php echo htmlspecialchars($producto['Descripcion'] ?? ''); ?></div>
                <div class="text-base sm:text-lg md:text-xl text-[#51B2E0] font-semibold"><?php echo $producto['Precio']; ?></div>
                <div class="mt-3 w-full flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2">
                  <div class="flex items-center border border-[#72E8AC] rounded-lg overflow-hidden">
                    <input type="number" 
                           id="cantidad-<?php echo $producto['codProducto'];?>" 
                           class="inputCantidad w-full sm:w-20 text-center border-0 focus:ring-0 text-[#256353] text-base py-2" 
                           value="1" 
                           min="1" 
                           max="100"
                           data-codigo-producto="<?php echo $producto['codProducto'];?>">
                  </div>
                  <button type="button" 
                          class="btnAgregar w-full sm:w-auto px-4 py-2 bg-[#72E8AC] hover:bg-[#51B2E0] text-[#256353] hover:text-white transition-all duration-200 rounded-lg font-semibold text-base"
                          data-codigo="<?php echo $producto['codProducto'];?>"
                          data-nombre="<?php echo addslashes($producto['Nombre']);//utilizo la propiedad addslashes para que los caracteres especiales se muestren correctamente?>"
                          data-precio="<?php echo addslashes($producto['Precio']);?>">
                          Añadir
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </main>
    </div>
    <script src="js/carrito.js"></script>
</body>
</html>