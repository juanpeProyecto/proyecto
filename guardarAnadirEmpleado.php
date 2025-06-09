<?php
    require_once "bd.php";
    require_once "sesiones.php";
    require_once "funciones.php";
    //comprobar_rol("administrador");
    
    // Inicializo variables
    $errores = [];
    $exito = false;
    
    // Validacion de campos
    if (isset($_REQUEST['nombre']) && isset($_REQUEST['correo']) && isset($_REQUEST['apellidos']) && isset($_REQUEST['rol']) && isset($_REQUEST['contrasena']) && isset($_REQUEST['telefono'])) {
        if (empty($_REQUEST['nombre']) || empty($_REQUEST['correo']) || empty($_REQUEST['apellidos']) || empty($_REQUEST['rol']) || empty($_REQUEST['contrasena']) || empty($_REQUEST['telefono'])) {
            $errores[] = 'Faltan datos obligatorios del empleado.';
        } else {
            try {
                $nombre = $_REQUEST['nombre'];
                $apellidos = $_REQUEST['apellidos'];
                $correo = $_REQUEST['correo'];
                $telefono = $_REQUEST['telefono'];
                $rol = $_REQUEST['rol'];
                $clave = $_REQUEST['contrasena'];
                $resul = anadirEmpleado($nombre, $apellidos, $correo, $telefono, $rol, $clave);
                if ($resul) {
                    $exito = 'Empleado creado correctamente.';
                } else {
                    $errores[] = 'El empleado no ha sido creado.';
                }
            } catch (Exception $e) {
                $errores[] = 'Error al añadir el empleado: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardar y anadir Empleado</title>
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <?php if (isset($exito) && $exito): 
        //si la variable exito esta definida y es true
    ?>
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
            <div class="flex gap-4 mt-5">
                <a href="empleados.php" class="px-7 py-4 bg-[#72E8D4] text-white rounded-xl font-bold text-lg hover:bg-[#72B0E8] hover:scale-105 transition-all duration-200 shadow-lg">Ver empleados</a>
                <a href="anadirEmpleado.php" class="px-7 py-4 bg-[#72E8D4] text-white rounded-xl font-bold text-lg hover:bg-[#72B0E8] hover:scale-105 transition-all duration-200 shadow-lg">Volver al formulario</a>
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
