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

$currentUserId = $_SESSION['user']['id'] ?? null;
$currentUserCargo = $_SESSION['cargo'] ?? null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $id_usuario = $_POST["id_usuario"];
  $cargo = $_POST["cargo"] ?? null;
  $action = $_POST["action"] ?? null;

  $currentUserId = $_SESSION["user"]["id"] ?? null;
  $currentUserCargo = $_SESSION["cargo"] ?? null;
  $targetUserCargo = $operadores[$id_usuario] ?? null;

  if ($action === "asignar" && $cargo) {
    if (UserController::puedeAsignarCargo($currentUserCargo, $cargo, $currentUserId, $id_usuario, $targetUserCargo)) {
        UserController::asignarOperador($conn, $id_usuario, $cargo);
        $mensaje = "✅ Permiso asignado correctamente.";
    } else {
        $mensaje = "❌ No tienes permiso para asignar este cargo.";
    }
  } elseif ($action === "revocar" && $cargo) {
    if (UserController::puedeRevocarCargo($currentUserCargo, $cargo, $currentUserId, $id_usuario, $targetUserCargo)) {
        UserController::revocarCargo($conn, $id_usuario, $cargo);
        $mensaje = "🗑️ Permiso revocado correctamente.";
    } else {
        $mensaje = "❌ No tienes permiso para revocar este cargo.";
    }
  }
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
    <div class="alert alert-info"><?= $mensaje ?></div>
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
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <?php $userCargo = $operadores[$u["id_usuario"]] ?? null; ?>
          <tr>
            <td><?= $u["nombre"] ?> <?= $u["apellido"] ?></td>
            <td><?= $u["email"] ?></td>

            <!-- Administrador -->
            <td>
              <?php if ($userCargo === 'administrador'): ?>
                <?php if ($currentUserCargo === 'administrador' && $u["id_usuario"] != $currentUserId): ?>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                    <input type="hidden" name="cargo" value="administrador">
                    <button name="action" value="revocar" class="btn btn-danger btn-sm">Revocar</button>
                  </form>
                <?php else: ?>
                  ✅
                <?php endif; ?>
              <?php elseif ($currentUserCargo === 'administrador'): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                  <input type="hidden" name="cargo" value="administrador">
                  <button name="action" value="asignar" class="btn btn-success btn-sm">Asignar</button>
                </form>
              <?php endif; ?>
            </td>

            <!-- Mantenedor -->
            <td>
              <?php if ($userCargo === 'mantenedor'): ?>
                <?php if ($currentUserCargo === 'administrador'): ?>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                    <input type="hidden" name="cargo" value="mantenedor">
                    <button name="action" value="revocar" class="btn btn-danger btn-sm">Revocar</button>
                  </form>
                <?php else: ?>
                  ✅
                <?php endif; ?>
              <?php elseif ($currentUserCargo === 'administrador'): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                  <input type="hidden" name="cargo" value="mantenedor">
                  <button name="action" value="asignar" class="btn btn-success btn-sm">Asignar</button>
                </form>
              <?php endif; ?>
            </td>

            <!-- Catálogo -->
            <td>
              <?php if ($userCargo === 'catalogo'): ?>
                <?php if (in_array($currentUserCargo, ['administrador', 'mantenedor'])): ?>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                    <input type="hidden" name="cargo" value="catalogo">
                    <button name="action" value="revocar" class="btn btn-danger btn-sm">Revocar</button>
                  </form>
                <?php else: ?>
                  ✅
                <?php endif; ?>
              <?php elseif (in_array($currentUserCargo, ['administrador', 'mantenedor'])): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                  <input type="hidden" name="cargo" value="catalogo">
                  <button name="action" value="asignar" class="btn btn-success btn-sm">Asignar</button>
                </form>
              <?php endif; ?>
            </td>

            <!-- Caja -->
            <td>
              <?php if ($userCargo === 'caja'): ?>
                <?php if (in_array($currentUserCargo, ['administrador', 'mantenedor'])): ?>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                    <input type="hidden" name="cargo" value="caja">
                    <button name="action" value="revocar" class="btn btn-danger btn-sm">Revocar</button>
                  </form>
                <?php else: ?>
                  ✅
                <?php endif; ?>
              <?php elseif (in_array($currentUserCargo, ['administrador', 'mantenedor'])): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="id_usuario" value="<?= $u["id_usuario"] ?>">
                  <input type="hidden" name="cargo" value="caja">
                  <button name="action" value="asignar" class="btn btn-success btn-sm">Asignar</button>
                </form>
              <?php endif; ?>
            </td>

          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
