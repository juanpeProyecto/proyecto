<?php
require "cabeceraAdmin.php";
require_once 'sesiones.php';
comprobar_rol(["administrador"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="src/output.css" rel="stylesheet">
  <title>Panel de Administración</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>
<body class="bg-[#E0FAF4] flex flex-col items-center justify-center min-h-screen">
  <main class="w-full max-w-8xl bg-[#E0FAF4]/95 border-4 border-[#72E8AC] rounded-[2.5rem] shadow-[0_8px_40px_0_rgba(81,178,224,0.18)] p-8 mt-6 flex flex-col items-center">
    <h1 class="text-4xl font-bold text-[#256353] mb-6 w-full text-center">Panel de Administración</h1>
    <div class="w-full flex flex-col items-center gap-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 w-full max-w-4xl">
        <a href="categorias.php" class="flex flex-col items-center justify-center gap-2 bg-[#72E8AC] hover:bg-[#72E884] text-[#000] hover:text-white transition-all duration-200 px-6 py-5 sm:px-8 sm:py-6 rounded-2xl font-bold text-xl sm:text-2xl text-center border-2 border-[#72E884]">
          <span class="material-symbols-outlined scale-150 hover:text-white transition-all duration-200">category</span>
          <span class="w-full text-center hover:text-white transition-all duration-200">Categorías</span>
          <span class="w-full text-center text-lg text-white mt-1">Gestiona las categorías del menú</span>
        </a>
        <a href="empleados.php" class="flex flex-col items-center justify-center gap-2 bg-[#72B0E8] hover:bg-[#72E8AC] text-[#000] hover:text-white transition-all duration-200 px-6 py-5 sm:px-8 sm:py-6 rounded-2xl font-bold text-xl sm:text-2xl text-center border-2 border-[#72E8AC]">
          <span class="material-symbols-outlined scale-150 hover:text-white transition-all duration-200">group</span>
          <span class="w-full text-center hover:text-white transition-all duration-200">Empleados</span>
          <span class="w-full text-center text-lg text-white mt-1">Añade, edita o elimina empleados</span>
        </a>        
        <a href="productos.php" class="flex flex-col items-center justify-center gap-2 bg-[#72E8D4] hover:bg-[#72D4BA] text-[#000] hover:text-white transition-all duration-200 px-6 py-5 sm:px-8 sm:py-6 rounded-2xl font-bold text-xl sm:text-2xl text-center border-2 border-[#72D4BA]">
          <span class="material-symbols-outlined scale-150 hover:text-white transition-all duration-200">restaurant</span>
          <span class="w-full text-center hover:text-white transition-all duration-200">Productos</span>
          <span class="w-full text-center text-lg text-white mt-1">Añade, edita o elimina productos</span>
        </a>
        <a href="menu.php" class="flex flex-col items-center justify-center gap-2 bg-[#72B0E8] hover:bg-[#72E8AC] text-[#000] hover:text-white transition-all duration-200 px-6 py-5 sm:px-8 sm:py-6 rounded-2xl font-bold text-xl sm:text-2xl text-center border-2 border-[#72E8AC]">
          <span class="material-symbols-outlined scale-150 hover:text-white transition-all duration-200">menu_book</span>
          <span class="w-full text-center hover:text-white transition-all duration-200">Ver Menú</span>
          <span class="w-full text-center text-lg text-white mt-1">Vista previa del menu</span>
        </a>
      </div>
      <div class="w-full max-w-4xl">
        <a href="logout.php" class="w-full flex flex-col items-center justify-center gap-2 bg-[#72E8AC] hover:bg-[#72B0E8] text-[#256353] hover:text-white transition-all duration-200 px-6 py-5 sm:px-8 sm:py-6 rounded-2xl font-bold text-xl sm:text-2xl text-center border-2 border-[#72B0E8] focus:outline-none focus:ring-4 focus:ring-[#72B0E8]/50">
          <span class="material-symbols-outlined scale-150 hover:text-white transition-all duration-200">logout</span>
          <span class="w-full text-center hover:text-white transition-all duration-200">Cerrar sesión</span>
          <span class="w-full text-center text-lg text-white mt-1">Salir del panel de administración</span>
        </a>
      </div>
    </div>
</main>
</body>
</html>
