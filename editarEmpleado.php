<?php
    require "cabeceraAdmin.php";
    require_once "bd.php";
    require_once "funciones.php";
    require_once "sesiones.php";
    comprobar_rol(["administrador"]);

    if (!isset($_GET['codEmpleado'])) {//si no hay un empleado muestro mensaje de error
        echo '<p class="text-red-600 text-center font-bold mt-8">No se ha proporcionado un empleado válido.</p>';
        exit;
    }
    $codEmpleado = intval($_GET['codEmpleado']);//hago un casting a entero para poder hacer la consulta 
    $emp = obtenerEmpleadoPorId($codEmpleado);
    if (!$emp) {
        echo '<p class="text-red-600 text-center font-bold mt-8">Empleado no encontrado.</p>';
        exit;
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar empleado</title>
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>
<body class="bg-gradient-to-br from-[#E0FAF4] to-[#51B2E0] flex flex-col items-center px-2 text-2xl min-h-screen justify-center">
    <div class="w-full max-w-3xl bg-white/95 border-2 border-[#51B2E0] hover:border-[#72E8AC] rounded-2xl shadow-xl p-16">
        <form action="guardarEditarEmpleado.php" method="POST" class="flex flex-col gap-6">
             <input type="hidden" name="codEmpleado" value="<?= $codEmpleado ?>"> <!--oculto el codigo del empleado ya que es un valor que no se puede modificar -->
            <div class="">
                <label for="nombre" class="mb-2 font-semibold text-[#2773A5]">Nombre</label>
                <input type="text" id="nombre" name="nombre" required placeholder="Nombre" value="<?= htmlspecialchars($emp['Nombre']) ?>" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="apellidos" class="mb-2 font-semibold text-[#2773A5]">Apellidos</label>
                <input type="text" id="apellidos" name="apellidos" required placeholder="Apellidos" value="<?= htmlspecialchars($emp['Apellidos']) ?>" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="correo" class="mb-2 font-semibold text-[#2773A5]">Correo</label>
                <input type="email" id="correo" name="correo" required placeholder="Correo electrónico" value="<?= htmlspecialchars($emp['Correo']) ?>" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="contrasena" class="mb-2 font-semibold text-[#2773A5]">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena" placeholder="Nueva contraseña" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="telefono" class="mb-2 font-semibold text-[#2773A5]">Teléfono</label>
                <input type="text" id="telefono" name="telefono" required placeholder="Teléfono" maxlength="10" value="<?= htmlspecialchars($emp['Telefono']) ?>" class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]/60">
            </div>
            <div class="">
                <label for="rol" class="mb-2 font-semibold text-[#2773A5]">Rol</label>
                <select id="rol" name="rol" required class="pl-4 pr-4 py-4 w-full rounded-md border-2 border-[#51B2E0] hover:border-[#72E8AC] focus:border-[#72E8AC] focus:ring-0 outline-none focus:outline-none text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5]">
                    <option value="">Selecciona un rol</option>
                    <option value="administrador" <?= $emp['Rol']==='administrador' ? 'selected' : '' //si el rol es administrador lo selecciono ?>>Administrador</option>
                    <option value="camarero" <?= $emp['Rol']==='camarero' ? 'selected' : '' //si el rol es camarero lo selecciono ?>>Camarero</option>
                    <option value="cocinero" <?= $emp['Rol']==='cocinero' ? 'selected' : '' //si el rol es cocinero lo selecciono ?>>Cocinero</option>
                    <option value="barra" <?= $emp['Rol']==='barra' ? 'selected' : '' //si el rol es barra lo selecciono ?>>Barra</option>
                </select>
            </div>
            <button type="submit" class="bg-[#51E080] hover:bg-[#53E051] text-white font-bold py-4 rounded-md shadow transition text-lg sm:text-xl mt-2 cursor-pointer">Guardar Cambios</button>
            <button type="reset" class="bg-[#51B2E0] hover:bg-[#51E080] text-white font-bold py-4 rounded-md shadow transition text-lg sm:text-xl cursor-pointer">Borrar</button>
        </form>
    </div>
</body>
</html>
