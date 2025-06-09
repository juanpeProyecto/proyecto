<?php
    require_once "bd.php";
    require "sesiones.php";
    require "funciones.php";
    //comprobar_rol(["administrador"]);

    $errores = [];
    $exito = null;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        try {
            $codEmpleado = intval($_POST['codEmpleado']);
            $nombre = htmlspecialchars($_POST['nombre']);
            $apellidos = htmlspecialchars($_POST['apellidos']);
            $correo = htmlspecialchars($_POST['correo']);
            $telefono = htmlspecialchars($_POST['telefono']);
            $rol = htmlspecialchars($_POST['rol']);
            $contrasena = $_POST['contrasena'];
            $res = editarEmpleado($codEmpleado, $nombre, $apellidos, $correo, $telefono, $rol, $contrasena);
            if ($res) {
                $exito = 'Empleado editado correctamente.';
            } else {
                $errores[] = 'Error al editar el empleado.';
            }
        } catch (Exception $e) {
            $errores[] = 'Error al editar el empleado: ' . htmlspecialchars($e->getMessage());
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardar editar empleado</title>
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <?php if (isset($exito) && $exito): ?>
    <meta http-equiv="refresh" content="1;url=empleados.php"> <!--nos lleva directamente a los empleados cuando pasan 3 segundos -->
    <?php endif; ?>
</head>
<body class="bg-[#E0FAF4] flex flex-col items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-4 flex flex-col items-center">
        <?php if (!empty($errores)): ?>
            <div class="w-full p-6 bg-[#72B0E8] text-white rounded-xl text-center font-bold shadow-lg flex items-center justify-center gap-4">
                <span class="material-symbols-outlined text-4xl text-white">error</span>
                <p class="text-3xl font-bold"><?= htmlspecialchars($errores[0]) ?></p>
            </div>
        <?php elseif ($exito): ?>
            <div class="w-full p-6 bg-[#72E8AC] text-white rounded-xl text-center font-bold shadow-lg flex items-center justify-center gap-4">
                <span class="material-symbols-outlined text-4xl text-white scale-150">check_circle</span>
                <p class="text-3xl font-bold"><?= htmlspecialchars($exito) ?></p>
            </div>
            <p class="text-lg mt-3 text-gray-700 font-medium">Redirigiendo automáticamente a los empleados...</p>
        <?php endif; ?>
    </div>
</body>
</html>