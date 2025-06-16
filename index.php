<?php
  session_start();
  
  // Cierro la sesión si se accede directamente a index.php
  if (basename($_SERVER['PHP_SELF']) === 'index.php' && !isset($_POST['usuario']) && isset($_SESSION["usuario"])) {
    session_unset();
    session_destroy();
    // Reinicio la sesión para el formulario de login
    session_start();
  }
  
  require_once "sesiones.php";
  require_once "bd.php";
  $conexion = conectarBD();
  
  // Si ya hay una sesión activa y no estamos enviando el formulario, redirigimos según el rol
  if (isset($_SESSION["usuario"]) && !isset($_POST['usuario'])) {
      switch ($_SESSION["rol"]) {
          case "administrador":
              header("Location: admin.php");
              break;
          case "camarero":
              header("Location: camarero.php");
              break;
          case "cocinero":
              header("Location: cocina.php");
              break;
          case "barra":
              header("Location: barra.php");
              break;
          default:
              header("Location: index.php?error=rol");
      }
      exit();
  }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <title>Iniciar sesión</title>
</head>
<body class="bg-[#50E0B0] min-h-screen flex flex-col justify-center items-center">
  <div class="w-full max-w-3xl bg-white/95 border-2 border-[#51B2E0] rounded-2xl shadow-xl p-16">
    <div class="flex flex-col items-center">
      <div class="flex flex-col sm:flex-row items-center justify-center gap-8 sm:gap-12 mb-8 w-full">
        <img src="img/barMinero.png" alt="Logo del bar" class="h-36 sm:h-52 w-auto max-w-[80vw] sm:max-w-[240px] drop-shadow-xl">
        <img src="img/logo-proyecto.png" alt="Logo del proyecto" class="h-36 sm:h-52 w-auto max-w-[80vw] sm:max-w-[240px] drop-shadow-xl">
      </div>
      <h2 class="text-2xl font-bold text-[#51E080] text-center mb-4">Panel de acceso</h2>
    </div>
    <form action="index.php" method="POST" class="flex flex-col gap-6">
      <div class="relative">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#51B2E0] text-3xl">person</span>
        <input type="text" id="usuario" name="usuario" required placeholder="Correo" class="pl-14 pr-4 py-4 w-full rounded-xl border-2 border-[#51B2E0] text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]">
      </div>
      <div class="relative">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#51B2E0] text-3xl">lock</span>
        <input type="password" id="contrasena" name="contrasena" required placeholder="Contraseña" class="pl-14 pr-4 py-4 w-full rounded-xl border-2 border-[#51B2E0] text-lg sm:text-xl bg-[#E0FAF4] text-[#2773A5] placeholder-[#51B2E0]">
      </div>
      <button type="submit" class="bg-[#51E080] hover:bg-[#53E051] text-white font-bold py-4 rounded-xl shadow transition text-lg sm:text-xl mt-2 cursor-pointer">Entrar</button>
      <button type="reset" class="bg-[#51B2E0] hover:bg-[#51E080] text-white font-bold py-4 rounded-xl shadow transition text-lg sm:text-xl cursor-pointer">Borrar</button>
    </form>
  </div>
</body>
</html>
<?php
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $usuario = $_POST['usuario'] ?? '';
      $contrasena = $_POST['contrasena'] ?? '';

      // Busco al usuario en la base de datos y lo guardo en una variable
      $stmt = $conexion->prepare("SELECT * FROM Empleados WHERE Correo = ? LIMIT 1");
      $stmt->bind_param('s', $usuario);
      $stmt->execute();
      $resultado = $stmt->get_result();

      if ($resultado && $resultado->num_rows === 1) {
          $empleado = $resultado->fetch_assoc();
          
          // Verifico la contraseña
          if (password_verify($contrasena, $empleado["Clave"])) {
              // si la verificación fue exitosa
              $_SESSION["usuario"] = $empleado["Nombre"];
              $_SESSION["rol"] = $empleado["Rol"];
              // dependiendo del rol que tengamos la aplicacion nos redirigirra a un archivo o a otro
              switch ($_SESSION["rol"]) {
                  case "administrador":
                      header("Location: admin.php");
                      break;
                  case "camarero":
                      header("Location: camarero.php");
                      break;
                  case "cocinero":
                      header("Location: cocina.php");
                      break;
                  case "barra":
                      header("Location: barra.php");
                      break;
                  default:
                      header("Location: index.php?error=rol");
              }
              exit();
          } else {
              $error = "Contraseña incorrecta"; //si el usuario se equivoca en la contraseña lo avisamos con un mensaje
          }
      } else {
          $error = "Usuario no encontrado"; //si el usuario no se encuentra nos saldrá este error
      }
  }
  if (isset($error)): 
?>
    <div class="text-red-600 text-center font-bold mt-4 mb-2 text-xl"><?= htmlspecialchars($error) ?></div>
<?php 
  endif; 
?>
<?php if (isset($error)): ?>
    <div class="text-xs text-center text-gray-500 bg-yellow-100 p-2 rounded-lg mt-2">
        <?php
        echo "Usuario introducido: " . htmlspecialchars($usuario) . "<br>";
        if (isset($empleado)) {
            echo "Hash en BD: " . htmlspecialchars($empleado['Clave']) . "<br>";
            echo "Password_verify: " . (password_verify($contrasena, $empleado['Clave']) ? "OK" : "FALLO");
        } else {
            echo "No se encontró empleado con ese correo.";
        }
        ?>
    </div>
<?php endif; ?>
