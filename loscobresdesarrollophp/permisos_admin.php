<?php
require_once 'config/MySqlDb.php';
require_once 'controllers/UserController.php';
require_once 'partials/navbar.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuarios = UserController::obtenerUsuarios($conn);
$operadores = UserController::obtenerOperadores($conn);
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_usuario = $_POST["id_usuario"];
    $cargo = $_POST["cargo"] ?? null;
    $action = $_POST["action"] ?? null;

    // Get current user cargo for permission check
    $currentUserCargo = null;
    if (isset($_SESSION["user_type"]) && $_SESSION["user_type"] === 'operador') {
        $currentUserCargo = $_SESSION["cargo"];
    }

    if ($action === "asignar" && $cargo) {
        // Permission check
        if ($currentUserCargo === 'administrador' || 
            ($currentUserCargo === 'mantenedor' && in_array($cargo, ['catalogo', 'caja']))) {
UserController::asignarOperador($conn, $id_usuario, $cargo);
            $mensaje = "✅ Permiso asignado correctamente.";
        } else {
            $mensaje = "❌ No tienes permiso para asignar este cargo.";
        }
    } elseif ($action === "revocar" && $cargo) {
        if ($currentUserCargo === 'administrador' || 
            ($currentUserCargo === 'mantenedor' && in_array($cargo, ['catalogo', 'caja']))) {
UserController::revocarCargo($conn, $id_usuario, $cargo);
            $mensaje = "🗑️ Permiso revocado correctamente.";
        } else {
            $mensaje = "❌ No tienes permiso para revocar este cargo.";
        }
    }
    $operadores = UserController::obtenerOperadores($conn);
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
          <th>Administrador</th>
          <th>Mantenedor</th>
          <th>Catálogo</th>
          <th>Caja</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= $u["nombre"] ?> <?= $u["apellido"] ?></td>
            <td><?= $u["email"] ?></td>
            <?php
              $userCargo = $operadores[$u["id_usuario"]] ?? null;
            ?>
            <td>
              <?php if ($userCargo === 'administrador'): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                  <input type="hidden" name="cargo" value="administrador">
                  <button name="revocar" value="true" class="btn btn-danger btn-sm">Revocar</button>
                </form>
              <?php elseif (isset($_SESSION["cargo"]) && $_SESSION["cargo"] === 'administrador'): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                  <input type="hidden" name="cargo" value="administrador">
                  <button name="action" value="asignar" class="btn btn-success btn-sm">Asignar</button>
                </form>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($userCargo === 'mantenedor'): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                  <input type="hidden" name="cargo" value="mantenedor">
                  <button name="revocar" value="true" class="btn btn-danger btn-sm">Revocar</button>
                </form>
              <?php elseif (isset($_SESSION["cargo"]) && in_array($_SESSION["cargo"], ['administrador', 'mantenedor'])): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                  <input type="hidden" name="cargo" value="mantenedor">
                  <button name="action" value="asignar" class="btn btn-success btn-sm">Asignar</button>
                </form>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($userCargo === 'catalogo'): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                  <input type="hidden" name="cargo" value="catalogo">
                  <button name="revocar" value="true" class="btn btn-danger btn-sm">Revocar</button>
                </form>
              <?php elseif (isset($_SESSION["cargo"]) && in_array($_SESSION["cargo"], ['administrador', 'mantenedor'])): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                  <input type="hidden" name="cargo" value="catalogo">
                  <button name="action" value="asignar" class="btn btn-success btn-sm">Asignar</button>
                </form>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($userCargo === 'caja'): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                  <input type="hidden" name="cargo" value="caja">
                  <button name="revocar" value="true" class="btn btn-danger btn-sm">Revocar</button>
                </form>
              <?php elseif (isset($_SESSION["cargo"]) && in_array($_SESSION["cargo"], ['administrador', 'mantenedor'])): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                  <input type="hidden" name="cargo" value="caja">
                  <button name="action" value="asignar" class="btn btn-success btn-sm">Asignar</button>
                </form>
              <?php endif; ?>
            </td>
            <td>
              <!-- No additional action buttons needed here -->
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
