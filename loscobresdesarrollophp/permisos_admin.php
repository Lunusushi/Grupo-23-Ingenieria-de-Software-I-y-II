<?php
require_once 'config/db.php';
require_once 'controllers/PermisoController.php';
require_once 'partials/navbar.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuarios = PermisoController::obtenerUsuarios($conn);
$operadores = PermisoController::obtenerOperadores($conn);
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_usuario = $_POST["id_usuario"];
    if (isset($_POST["asignar"])) {
        PermisoController::asignarOperador($conn, $id_usuario);
        $mensaje = "✅ Permiso asignado correctamente.";
    } elseif (isset($_POST["revocar"])) {
        PermisoController::revocarOperador($conn, $id_usuario);
        $mensaje = "🗑️ Permiso revocado correctamente.";
    }
    $operadores = PermisoController::obtenerOperadores($conn);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Permisos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="public/css/main.css" rel="stylesheet">
</head>
<body>


<div class="container">
  <h2 class="mb-4">⚙️ Gestión de Permisos de Usuario</h2>

  <?php if ($mensaje): ?>
    <div class="alert alert-success"><?= $mensaje ?></div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table table-bordered align-middle text-center">
      <thead class="table-light">
        <tr>
          <th>Nombre</th>
          <th>Email</th>
          <th>¿Operador?</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= $u["nombre"] ?> <?= $u["apellido"] ?></td>
            <td><?= $u["email"] ?></td>
            <td><?= in_array($u["id_usuario"], $operadores) ? "✅" : "❌" ?></td>
            <td>
              <form method="POST" class="d-inline">
                <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                <?php if (in_array($u["id_usuario"], $operadores)): ?>
                  <button name="revocar" class="btn btn-danger btn-sm">Revocar</button>
                <?php else: ?>
                  <button name="asignar" class="btn btn-success btn-sm">Asignar</button>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
