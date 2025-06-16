<?php
    require_once "bd.php";
    require_once "sesiones.php";
    require_once "funciones.php";
    comprobar_rol(["administrador"]);
    
    $exito = false; //siempre sera false a no ser que se demuestre lo contrario :)
    $errores = [];
    
    if (isset($_GET['codCategoria'])) { //si
        $codCategoria = $_GET['codCategoria'];
        try 
        {
            borrarCategoria($codCategoria); //intento borrar la categoria seleccionada con la consulta
            $exito = 'Categoría eliminada correctamente.';
        } 
        catch (Exception $e) 
        {
            $errores[] = 'Error al borrar la categoría: ' . htmlspecialchars($e->getMessage());
        }
    } 
    else 
    {
        $errores[] = 'No se ha especificado una categoría para eliminar';
    }
?>
<!DOCTYPE html>
<html lang="es">    
<head>
    <meta charset="UTF-8">
    <title>Borrar Categoría</title>
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <?php if ($exito): ?>
    <meta http-equiv="refresh" content="1;url=categorias.php"> <!--este meta sirve para redireccionarme a una url pasado x segundods-->
    <?php endif; ?>
</head>
<body class="bg-[#E0FAF4] flex flex-col items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-4 flex f lex-col items-center">
        <?php if (!empty($errores)): ?>
            <div class="w-full p-6 bg-[#72B0E8] text-white rounded-xl text-center font-bold shadow-lg flex items-center justify-center gap-4">
                <span class="material-symbols-outlined text-4xl text-white">error</span>
                <p class="text-3xl font-bold">La categoría no ha sido eliminada debido a un error</p>
            </div>
            <a href="categorias.php" class="inline-block mt-5 px-7 py-4 bg-[#72E8D4] text-white rounded-xl font-bold text-lg hover:bg-[#72B0E8] hover:scale-105 transition-all duration-200 shadow-lg">Volver a categorías</a>
        <?php elseif (!empty($exito)): ?>
            <div class="w-full p-6 bg-[#72E8AC] text-white rounded-xl text-center font-bold shadow-lg flex items-center justify-center gap-4">
                <span class="material-symbols-outlined text-4xl text-white scale-150">check_circle</span>
                <p class="text-3xl font-bold">La categoria elecciona ha sido borrada de forma exitosa</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>