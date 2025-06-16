<?php
    require_once "bd.php";
    require_once "sesiones.php";
    require_once "funciones.php";
    comprobar_rol(["administrador"]);
    
    $errores = [];
    $exito = false;
    
    if (isset($_GET['codEmpleado'])) 
    {
        $codEmpleado = $_GET['codEmpleado']; //Obtengo el valor del codigo del empleado que he pinchado en el archivo empleados.php
        
        try 
        {
            borrarEmpleado($codEmpleado); //intento borrar el empleado con la consulta
            $exito = 'Empleado borrado correctamente.';
        } 
        catch (Exception $e)
        {
            $errores[] = 'Error al borrar el empleado seleccionadop: ' . htmlspecialchars($e->getMessage());
        }
    } 
    else 
    {
        $errores[] = 'No se ha especificado un empleado para eliminar';
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrar Empleado</title>
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <?php if (isset($exito)): ?>
    <meta http-equiv="refresh" content="1;url=empleados.php"> <!--nos lleva directemente a los empleados cuando pasan 1 segundo -->
    <?php endif; ?>
</head>
<body class="bg-[#E0FAF4] flex flex-col items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-4 flex flex-col items-center">
        <?php if (!empty($errores)):?> 
            <div class="w-full p-6 bg-[#72B0E8] text-white rounded-xl text-center font-bold shadow-lg flex items-center justify-center gap-4">
                <span class="material-symbols-outlined text-4xl text-white">error</span>
                <p class="text-3xl font-bold"><?= htmlspecialchars($errores[0]) ?></p> <!--muestro el error -->
            </div>
            <a href="empleados.php" class="inline-block mt-5 px-7 py-4 bg-[#72E8D4] text-white rounded-xl font-bold text-lg hover:bg-[#72B0E8] hover:scale-105 transition-all duration-200 shadow-lg">Volver a empleados</a>
        <?php elseif ($exito): ?>
            <div class="w-full p-6 bg-[#72E8AC] text-white rounded-xl text-center font-bold shadow-lg flex items-center justify-center gap-4">
                <span class="material-symbols-outlined text-4xl text-white scale-150">check_circle</span>
                <p class="text-3xl font-bold"><?= htmlspecialchars($exito) ?></p>
            </div>
            <p class="text-lg mt-3 text-gray-700 font-bold">El empleado se ha borrado de forma exitosa</p>
        <?php endif; ?>
    </div>
</body>
</html>