<?php
    require "sesiones.php";
    comprobar_rol(["administrador"]);
    if (session_status() === PHP_SESSION_NONE) //Si no hay sesion
    {
        session_start();
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
</head>
<body>
    <header class="w-full max-w-6xl flex flex-col lg:flex-row items-center justify-between px-2 lg:px-8 py-2 lg:py-4 mt-6 mb-8 rounded-2xl shadow-xl bg-white/90 border-2 border-[#72E8AC] gap-3 lg:gap-0">
        <div class="font-bold text-lg lg:text-2xl w-full lg:w-auto text-center lg:text-left mb-2 lg:mb-0">
            <span class="text-[#256353]">Bienvenido, </span>
            <span class="text-[#51B2E0]">
                <?php echo isset($_SESSION['usuario']) ? htmlspecialchars($_SESSION['usuario']) : 'Usuario'; ?>
            </span>
        </div>
        <div class="flex flex-col lg:flex-row justify-center items-center gap-2 lg:gap-4 w-full lg:w-auto">
            <a href="categorias.php" class="flex items-center justify-center gap-2 bg-[#72E8AC] hover:bg-[#51B2E0] text-[#256353] hover:text-white transition-all duration-200 px-4 py-2 rounded-xl shadow font-semibold text-base border-2 border-[#51E8B0] w-full lg:w-auto">
                <span class="material-symbols-outlined">category</span>
                <span class="flex-1 text-center">Categorías</span>
            </a>
            <a href="productos.php" class="flex items-center justify-center gap-2 bg-[#51B2E0] hover:bg-[#72B0E8] text-[#256353] hover:text-white transition-all duration-200 px-4 py-2 rounded-xl shadow font-semibold text-base border-2 border-[#51B2E0] w-full lg:w-auto">
                <span class="material-symbols-outlined">restaurant_menu</span>
                <span class="flex-1 text-center">Productos</span>
            </a>
            <a href="empleados.php" class="flex items-center justify-center gap-2 bg-[#72E8D4] hover:bg-[#51B2E0] text-[#256353] hover:text-white transition-all duration-200 px-4 py-2 rounded-xl shadow font-semibold text-base border-2 border-[#72E8D4] w-full lg:w-auto">
                <span class="material-symbols-outlined">group</span>
                <span class="flex-1 text-center">Empleados</span>
            </a>
            <a href="menu.php" class="flex items-center justify-center gap-2 bg-[#72B0E8] hover:bg-[#51B2E0] text-[#256353] hover:text-white transition-all duration-200 px-4 py-2 rounded-xl shadow font-semibold text-base border-2 border-[#51B2E0] w-full lg:w-auto">
                <span class="material-symbols-outlined">menu_book</span>
                <span class="flex-1 text-center">Ver menú</span>
            </a>
            <a href="logout.php" class="flex items-center justify-center gap-2 bg-[#72E8AC] hover:bg-[#51B2E0] text-[#256353] hover:text-white transition-all duration-200 px-4 py-2 rounded-xl shadow font-semibold text-base border-2 border-[#51E8B0] w-full lg:w-auto">
                <span class="material-symbols-outlined">logout</span>
                <span class="flex-1 text-center">Cerrar sesión</span>
            </a>
        </div>
    </header>
</body>
</html>
