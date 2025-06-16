<?php
// ¡IMPORTANTE! No debe haber salida HTML antes de la gestión de sesión y headers:
require_once "sesiones.php";
comprobar_rol(["camarero", "cocinero", "barra"]);
// Ahora sí, empieza el HTML:
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de empleados</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link href="src/output.css" rel="stylesheet">
</head>
<body class="bg-[#E0FAF4] min-h-screen">
    <div class="w-full">
        <header class="w-full flex flex-row items-center justify-between px-6 py-3 bg-white/90 shadow-lg fixed top-0 left-0 z-50">
            <div class="font-bold text-lg sm:text-xl">
                <span class="text-[#256353]">Bienvenido, </span>
                <span class="text-[#51B2E0]"><?php echo isset($_SESSION['usuario']) ? htmlspecialchars($_SESSION['usuario']) : 'Usuario'; ?></span>
            </div>
            
            <div class="flex items-center gap-3">    
                <a href="logout.php" class="flex items-center gap-1 bg-[#72E8AC] hover:bg-[#51B2E0] text-[#256353] hover:text-white transition-all duration-200 px-3 py-2 rounded-lg shadow font-semibold text-sm border border-[#51E8B0]">
                    <span class="material-symbols-outlined text-sm">logout</span>
                    <span class="hidden sm:inline">Cerrar sesión</span>
                </a>
            </div>
        </header>
    </div>
</body>
</html>