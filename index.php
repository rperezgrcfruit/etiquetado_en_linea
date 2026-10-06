<?php
require_once '../packing/config/csrf_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $username = $_POST['username'];
    $password = $_POST['password'];
    $empresa = $_POST['empresa'];
    require_once '../packing/config/db.php';
    require_once '../packing/config/password_helper.php';

    $user = verifyLogin($pdo, $username, $password);

    if ($user) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Regenerar ID de sesión para prevenir session fixation
        session_regenerate_id(true);
        $_SESSION['username'] = $user['user'];
        $_SESSION['name'] = $user['nombre'];
        $_SESSION['empresa'] = $empresa;
        header("Location: /packing/view/main.php");
    } else {
        echo "<script>alert('Credenciales incorrectas. Por favor, inténtalo de nuevo.');</script>";
    }
}

require_once '../packing/api/get_empresa.php';
session_start();

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso</title>
  <link rel="stylesheet" href="/packing/view/assets/css/styles.css?v=<?php echo filemtime('/var/www/grc/packing/view/assets/css/styles.css'); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>

  <div class="login-image">
    <img src="/packing/view/assets/images/icons/grc_sf.png" alt="" srcset="">
  </div>
  <div class="login-wrapper">
    <form class="login-form" method="post" id="form-login">
      <h1 class="h2-titulo-sesion">Acceso</h1>
      <div class="form-group">
        <label for="username">Nombre de usuarios:</label>
        <input type="text" id="username" name="username" require>
        <img src="/packing/view/assets/images/icons/icon-username.png" class="icons" alt="" srcset="">
      </div>
      <div class="form-group">
        <label for="password">Contraseña:</label>
        <input type="password" id="password" class="password" name="password" require>
        <img src="/packing/view/assets/images/icons/icon-password.png" class="icons" alt="" srcset="">
        <span class="toggle-password"><i class="fas fa-eye"></i></span>
      </div>
      <div class="form-group">
        <label for="empresa">Empresa</label>
        <select name="empresa" id="empresa">
          <option selected>Seleccionar Empresa</option>
          <?php
          foreach ($empresas ?? [] as $empresa) {
            echo '<option value="' . htmlspecialchars($empresa["R_SOCIAL"]) . '">' . htmlspecialchars($empresa["R_SOCIAL"]) . '</option>';
          }

          ?>
        </select>
      </div>

      <div id="overlay"></div>
      <div id="spinner"></div>

      <?php require_once '../packing/config/csrf_helper.php'; echo csrfField(); ?>
      <button type="submit" id="btn-login">Iniciar sesión</button>

    </form>

  </div>

  <script src="/packing/view/assets/js/scripts.js?v=<?php echo time(); ?>"></script>
</body>

</html>