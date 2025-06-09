<?php
    require "cabeceraAdmin.php"; //
    require_once "bd.php";
    require_once "funciones.php";
    //require "sesiones.php";
    //comprobar_rol(["administrador"]);
    $conexion = conectarBD();
    $todosEmpleados = cargarEmpleados($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Empleados</title> 
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>
<body class="bg-gradient-to-br from-[#E0FAF4] via-[#72E8D4] to-[#51B2E0] min-h-screen w-full flex flex-col items-center overflow-x-hidden px-2 sm:px-0 text-2xl">
    <div class="w-full max-w-7xl bg-[#E0FAF4]/95 border-4 border-[#72E8AC] rounded-[2.5rem] mt-6 shadow-[0_8px_40px_0_rgba(81,178,224,0.18)] p-4 flex flex-col items-center">
        <h1 class="text-4xl font-bold text-[#256353] mb-6 w-full text-center">Gestión de Empleados</h1>
        <?php
            if (count($todosEmpleados) > 0)  //si hay empleados creo la tabla con sus columnas
            {
        ?>
        <div class="w-full rounded-3xl overflow-x-auto">
    <table class="min-w-[600px] w-full text-left rounded-3xl overflow-hidden text-xs sm:text-base md:text-lg lg:text-2xl shadow-xl border-separate border-spacing-0">
                <tr class="bg-gradient-to-r from-[#72E8AC]/90 to-[#51E080]/80 text-[#256353]">
                    <th class="text-center py-2 sm:py-4 md:py-6 px-2 sm:px-4 md:px-8 font-bold uppercase tracking-widest border-b-4 border-[#51E080] text-xs sm:text-base md:text-lg lg:text-2xl whitespace-nowrap">Nombre</th>
                    <th class="text-center py-2 sm:py-4 md:py-6 px-2 sm:px-4 md:px-8 font-bold uppercase tracking-widest border-b-4 border-[#51E080] text-xs sm:text-base md:text-lg lg:text-2xl whitespace-nowrap">Rol</th>
                    <th class="text-center py-2 sm:py-4 md:py-6 px-2 sm:px-4 md:px-8 font-bold uppercase tracking-widest border-b-4 border-[#51E080] text-xs sm:text-base md:text-lg lg:text-2xl whitespace-nowrap">Correo</th>
                    <th class="text-center py-2 sm:py-4 md:py-6 px-2 sm:px-4 md:px-8 font-bold uppercase tracking-widest border-b-4 border-[#51E080] text-xs sm:text-base md:text-lg lg:text-2xl whitespace-nowrap">Teléfono</th>
                    <th class="py-2 sm:py-4 md:py-6 px-2 sm:px-4 md:px-8 text-center font-bold uppercase tracking-widest border-b-4 border-[#51E080] text-xs sm:text-base md:text-lg lg:text-2xl whitespace-nowrap">Acciones</th>
                </tr>
                <?php 
                foreach ($todosEmpleados as $emp): //recorro todos los empleados
                ?>
                <tr class="border-b">
                    <td class="text-center py-2 sm:py-4 md:py-6 px-2 sm:px-4 md:px-8 font-semibold text-[#256353] text-xs sm:text-base md:text-lg lg:text-2xl transition-transform duration-200 bg-[#E0FAF4]/80 whitespace-normal break-words">
                        <?= htmlspecialchars($emp['Nombre']) ?>
                    </td>
                    <td class="text-center py-3 sm:py-7 px-1 sm:px-4 text-[#21476B] text-xs sm:text-base md:text-lg lg:text-2xl transition-transform duration-200 bg-[#E0FAF4]/80 whitespace-normal break-words">
                        <?= 
                            htmlspecialchars($emp['Rol'])
                        ?>
                    </td>
                    <td class="text-center py-3 sm:py-7 px-1 sm:px-4 text-[#21476B] text-xs sm:text-base md:text-lg lg:text-2xl transition-transform duration-200 bg-[#E0FAF4]/80 whitespace-normal break-words">
                        <?= 
                            htmlspecialchars($emp['Correo'])
                        ?>
                    </td>
                    <td class="text-center py-3 sm:py-7 px-1 sm:px-4 text-[#21476B] text-xs sm:text-base md:text-lg lg:text-2xl transition-transform duration-200 bg-[#E0FAF4]/80 whitespace-normal break-words">
                        <?= 
                            htmlspecialchars($emp['Telefono'])
                        ?>
                    </td>
                    <td class="py-3 sm:py-7 px-1 sm:px-4 flex flex-col sm:flex-row gap-2 sm:gap-4 items-center justify-center text-xs sm:text-base md:text-lg lg:text-2xl bg-[#E0FAF4]/80 whitespace-normal w-full">
                        <a href="editarEmpleado.php?codEmpleado=<?= $emp['codEmpleado'] ?>" title="Editar" class="flex flex-row sm:flex-col items-center justify-center gap-2 bg-[#72E8AC] hover:scale-105 text-[#256353] hover:text-white transition-all duration-200 px-4 py-3 rounded-2xl font-bold text-xl text-center border-2 border-[#72E884] w-full sm:w-auto">
                            <span class="material-symbols-outlined scale-125 hover:text-white transition-all duration-200">edit</span>
                        </a>
                        <a href="borrarEmpleado.php?codEmpleado=<?= $emp['codEmpleado'] ?>" title="Borrar" class="flex flex-row sm:flex-col items-center justify-center gap-2 bg-[#72B0E8] hover:scale-105 hover:text-white transition-all duration-200 px-4 py-3 rounded-2xl font-bold text-xl text-center border-2 border-[#72E8AC] w-full sm:w-auto">
                            <span class="material-symbols-outlined scale-125 hover:text-white transition-all duration-200">delete</span>
                        </a>
                    </td>
                </tr>
                <?php   
                    endforeach; //utilizo endforeach para cerrar el bucle sin tener la necesidad de usar llaves
                ?>
            </table>
        </div>
        <div class="w-full flex justify-center items-center mt-12">
            <a href="anadirEmpleado.php" class="w-full sm:w-auto flex flex-row items-center justify-center gap-2 bg-[#72E8AC] hover:bg-[#72E884] text-[#256353] hover:text-white transition-all duration-200 px-4 sm:px-8 py-2 sm:py-4 rounded-2xl font-bold text-base sm:text-xl text-center border-2 border-[#72E884]">
                <span class="material-symbols-outlined scale-125 hover:text-white transition-all duration-200">add_circle</span>
                <span class="text-center hover:text-white transition-all duration-200">Añadir empleado</span>
            </a>
        </div>
    </div>
        <?php 
            } else { 
        ?>
            <p class="text-center text-red-600 text-xl">No se pudieron cargar los empleados.</p>
        <?php 
        } 
        ?>
    </div>
</body>
</html>
<?php 
    $conexion->close(); 
?>

