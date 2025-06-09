<?php
    require "cabeceraAdmin.php";
    //require "sesiones.php";
    require_once "bd.php";
    //comprobar_rol(["administrador"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir empleado nuevo</title>
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>
<body class="bg-gradient-to-br from-[#E0FAF4] via-[#72E8D4] to-[#51B2E0] flex flex-col items-center px-2 text-2xl min-h-screen justify-center">
    <div class="w-full max-w-3xl bg-white/95 border-2 border-[#51B2E0] hover:border-[#72E8AC] rounded-2xl shadow-xl p-16">
        <form action="guardarAnadirEmpleado.php" method="POST" class="flex flex-col gap-6">
            <div class="">
                <label for="nombre" class="block mb-2 font-semibold text-[#2773A5]">Nombre</label>
                <input type="text" id="nombre" name="nombre" required placeholder="Nombre" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="apellidos" class="block mb-2 font-semibold text-[#2773A5]">Apellidos</label>
                <input type="text" id="apellidos" name="apellidos" required placeholder="Apellidos" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="correo" class="block mb-2 font-semibold text-[#2773A5]">Correo</label>
                <input type="email" id="correo" name="correo" required placeholder="Correo electrónico" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="contrasena" class="block mb-2 font-semibold text-[#2773A5]">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena" required placeholder="Nueva contraseña" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="telefono" class="block mb-2 font-semibold text-[#2773A5]">Teléfono</label>
                <input type="text" id="telefono" name="telefono" required placeholder="Teléfono" maxlength="10" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="rol" class="block mb-2 font-semibold text-[#2773A5]">Rol</label>
                <select id="rol" name="rol" required class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5]">
                    <option value="">Selecciona un rol</option>
                    <option value="administrador">Administrador</option>
                    <option value="camarero">Camarero</option>
                    <option value="cocinero">Cocinero</option>
                    <option value="barra">Barra</option>
                </select>
            </div>
            <button type="submit" class="w-full flex flex-row items-center justify-center gap-2 bg-[#72E8AC] hover:bg-[#72E884] text-[#256353] hover:text-white transition-all duration-200 px-8 py-4 rounded-2xl font-bold text-xl text-center border-2 border-[#72E884] mt-6">Añadir Empleado</button>
            <button type="reset" class="w-full flex flex-row items-center justify-center gap-2 bg-[#51B2E0] hover:bg-[#51E080] text-white font-bold py-4 rounded-2xl shadow transition text-lg sm:text-xl cursor-pointer">Borrar</button>
        </form>
    </div>
</body>
</html>