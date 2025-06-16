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
          // DEBUG: Mostrar datos en consola JS y en la interfaz
            echo "<script>console.log('Usuario introducido: ", addslashes($usuario), "');</script>";
            echo "<script>console.log('Contraseña introducida: ", addslashes($contrasena), "');</script>";
            echo "<script>console.log('Contraseña en BD: ", addslashes($empleado['Clave']), "');</script>";
            // También mostramos en la interfaz
            echo '<div style="background:#ffe0e0;color:#a00;padding:10px;margin:10px 0;border-radius:8px;max-width:500px;text-align:left;font-size:1em;">';
            echo '<b>DEBUG LOGIN</b><br>';
            echo 'Usuario introducido: ' . htmlspecialchars($usuario) . '<br>';
            echo 'Contraseña introducida: ' . htmlspecialchars($contrasena) . '<br>';
            echo 'Contraseña en BD: ' . htmlspecialchars($empleado['Clave']) . '<br>';
            echo '</div>';
            if ($contrasena === $empleado["Clave"]) {
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
                      break;
              }
              exit();
          } else {
            // DEBUG: Contraseña incorrecta
            echo "<script>console.log('Comparación: FALLO');</script>";
            echo '<div style="background:#ffe0e0;color:#a00;padding:10px;margin:10px 0;border-radius:8px;max-width:500px;text-align:left;font-size:1em;">Comparación: <b>FALLO</b></div>';
            $error = "Contraseña incorrecta"; //si el usuario se equivoca en la contraseña lo avisamos con un mensaje
        }
      } else {
        // DEBUG: Usuario no encontrado
        echo "<script>console.log('Usuario no encontrado en la BD');</script>";
        echo '<div style="background:#ffe0e0;color:#a00;padding:10px;margin:10px 0;border-radius:8px;max-width:500px;text-align:left;font-size:1em;">Usuario no encontrado en la base de datos</div>';
        $error = "Usuario no encontrado"; //si el usuario no se encuentra nos saldrá este error
    }
  }
  if (isset($error)): 
?>
    <div class="text-red-600 text-center font-bold mt-4 mb-2 text-xl"><?= htmlspecialchars($error) ?></div>
<?php 
  endif; 
?>
<div style="background: #FFF3CD; color: #856404; border: 1px solid #FFEEBA; padding: 18px; border-radius: 12px; margin: 20px auto; max-width: 500px; text-align: center; font-size: 1.1em;">
    <b>DEBUG LOGIN</b><br>
    <?php
    // Prueba conexión y empleados
    try {
        $testConn = conectarBD();
        $testRes = $testConn->query("SELECT COUNT(*) as total FROM Empleados");
        $row = $testRes->fetch_assoc();
        echo "Conexión a la base de datos: <span style='color:green'>OK</span><br>";
        echo "Empleados en la BD: <b>" . $row['total'] . "</b><br>";
    } catch (Exception $e) {
        echo "Conexión a la base de datos: <span style='color:red'>FALLO</span><br>";
        echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
    ?>
    <hr style="margin:10px 0;">
    <?php if (isset($error)): ?>
        <span style="color:red;"><b><?= htmlspecialchars($error) ?></b></span><br>
        Usuario introducido: <?= htmlspecialchars($usuario) ?><br>
        <?php if (isset($empleado)): ?>
            Hash en BD: <?= htmlspecialchars($empleado['Clave']) ?><br>
            Password_verify: <?= password_verify($contrasena, $empleado['Clave']) ? "OK" : "FALLO" ?>
        <?php else: ?>
            No se encontró empleado con ese correo.
        <?php endif; ?>
    <?php else: ?>
        <span style="color:green;">Sin errores de login detectados.</span>
    <?php endif; ?>
</div>
