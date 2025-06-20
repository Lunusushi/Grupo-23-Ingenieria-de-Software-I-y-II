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

// Validación de acción
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_usuario = $_POST["id_usuario"];
    $cargo = $_POST["cargo"] ?? null;
    $action = $_POST["action"] ?? null;

    $targetUserCargo = $operadores[$id_usuario] ?? null;

    if ($action === "asignar" && $cargo) {
        if (UserController::puedeAsignarCargo($currentUserCargo, $cargo, $currentUserId, $id_usuario, $targetUserCargo)) {
            try {
                UserController::asignarOperador($conn, $id_usuario, $cargo);
                header("Location: permisos_admin.php?mensaje=asignado");
                exit;
            } catch (Exception $e) {
                $mensaje = $e->getMessage();
            }
        } else {
            $mensaje = "❌ No tienes permiso para asignar este cargo.";
        }
    } elseif ($action === "revocar" && $cargo) {
        if (UserController::puedeRevocarCargo($currentUserCargo, $cargo, $currentUserId, $id_usuario, $targetUserCargo)) {
            UserController::revocarCargo($conn, $id_usuario, $cargo);
            header("Location: permisos_admin.php?mensaje=revocado");
            exit;
        } else {
            $mensaje = "❌ No tienes permiso para revocar este cargo.";
        }
    }
}

// Mensajes tras redirección
if (isset($_GET['mensaje'])) {
    if ($_GET['mensaje'] === 'asignado') {
        $mensaje = "✅ Permiso asignado correctamente.";
    } elseif ($_GET['mensaje'] === 'revocado') {
        $mensaje = "🗑️ Permiso revocado correctamente.";
    }
}

// Función de renderizado
function renderBotonCargo($u, $cargo, $currentUserId, $currentUserCargo, $userCargo) {
    $id_usuario = $u["id_usuario"];

    // Mantenedor no puede verse a sí mismo revocar/sobrescribir
    if ($currentUserCargo === 'mantenedor' && $currentUserId === $id_usuario) {
        return ($userCargo === $cargo) ? '✅' : '';
    }

    // Admin no puede editar a otro admin desde frontend
    if ($currentUserCargo === 'administrador' && $userCargo === 'administrador' && $currentUserId !== $id_usuario) {
        return ($cargo === 'administrador') ? '✅' : '';
    }

    // Nadie se puede modificar a sí mismo
    if ($currentUserId === $id_usuario) {
        return ($userCargo === $cargo) ? '✅' : '';
    }

    $puedeAsignar = UserController::puedeAsignarCargo($currentUserCargo, $cargo, $currentUserId, $id_usuario, $userCargo);
    $puedeRevocar = UserController::puedeRevocarCargo($currentUserCargo, $cargo, $currentUserId, $id_usuario, $userCargo);

    if ($userCargo === $cargo) {
        if ($puedeRevocar) {
            return '
            <form method="POST" class="d-inline">
                <input type="hidden" name="id_usuario" value="' . htmlspecialchars($id_usuario) . '">
                <input type="hidden" name="cargo" value="' . htmlspecialchars($cargo) . '">
                <button name="action" value="revocar" class="btn btn-danger btn-sm">Revocar</button>
            </form>';
        } else {
            return '✅';
        }
    }

    if ($puedeAsignar) {
        return '
        <form method="POST" class="d-inline">
            <input type="hidden" name="id_usuario" value="' . htmlspecialchars($id_usuario) . '">
            <input type="hidden" name="cargo" value="' . htmlspecialchars($cargo) . '">
            <button name="action" value="asignar" class="btn btn-success btn-sm">Asignar</button>
        </form>';
    }

    return '';
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

<div class="container mt-4">
    <h2 class="mb-4">⚙️ Gestión de Permisos de Usuario</h2>

    <?php if ($mensaje): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
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
                    <?php
                    $userCargo = $operadores[$u["id_usuario"]] ?? null;

                    // 👻 Ocultar fila de administradores si el logeado es mantenedor
                    if ($currentUserCargo === 'mantenedor' && $userCargo === 'administrador') {
                        continue;
                    }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($u["nombre"]) ?> <?= htmlspecialchars($u["apellido"]) ?></td>
                        <td><?= htmlspecialchars($u["email"]) ?></td>
                        <td><?= renderBotonCargo($u, 'administrador', $currentUserId, $currentUserCargo, $userCargo) ?></td>
                        <td><?= renderBotonCargo($u, 'mantenedor', $currentUserId, $currentUserCargo, $userCargo) ?></td>
                        <td><?= renderBotonCargo($u, 'catalogo', $currentUserId, $currentUserCargo, $userCargo) ?></td>
                        <td><?= renderBotonCargo($u, 'caja', $currentUserId, $currentUserCargo, $userCargo) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
