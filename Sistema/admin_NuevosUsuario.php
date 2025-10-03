<?php
// admin_NuevosUsuario.php
// Page to manage users: create, update (nombre, apellido, email, username, password), delete

session_start();
require_once 'config/MySqlDb.php'; // This file sets up $conn as PDO instance

// Helper function to sanitize input
function sanitize($data) {
    return htmlspecialchars(trim($data));
}

// Handle form submissions
$action = $_POST['action'] ?? '';
$message = '';

if ($action === 'create') {
    $nombre = sanitize($_POST['nombre'] ?? '');
    $apellido = sanitize($_POST['apellido'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nombre && $apellido && $email && $username && $password) {
        // Check if email or username already exists
        $stmt = $conn->prepare("SELECT id_usuario FROM USUARIO WHERE email = ? OR nombre = ?");
        $stmt->execute([$email, $username]);
        $exists = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($exists && count($exists) > 0) {
            $message = "Email or usuario ya existe.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO USUARIO (nombre, apellido, email, password_hash, fecha_registro, activo) VALUES (?, ?, ?, ?, NOW(), 1)");
            $stmt->execute([$username, $apellido, $email, $password_hash]);
            $message = "Usuario creado correctamente.";
        }
    } else {
        $message = "Please fill all fields.";
    }
} elseif ($action === 'update') {
    $id_usuario = intval($_POST['id_usuario'] ?? 0);
    $nombre = sanitize($_POST['nombre'] ?? '');
    $apellido = sanitize($_POST['apellido'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($id_usuario && $nombre && $apellido && $email && $username) {
        // Update user info
        if ($password) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE USUARIO SET nombre = ?, apellido = ?, email = ?, password_hash = ? WHERE id_usuario = ?");
            $stmt->execute([$username, $apellido, $email, $password_hash, $id_usuario]);
        } else {
            $stmt = $conn->prepare("UPDATE USUARIO SET nombre = ?, apellido = ?, email = ? WHERE id_usuario = ?");
            $stmt->execute([$username, $apellido, $email, $id_usuario]);
        }
        $message = "Usuario actualizado correctamente.";
    } else {
        $message = "Llena todos los campos.";
    }
} elseif ($action === 'delete') {
    $id_usuario = intval($_POST['id_usuario'] ?? 0);
    if ($id_usuario) {
        $stmt = $conn->prepare("DELETE FROM USUARIO WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        $message = "Usuario eliminado correctamente.";
    }
}

// Fetch all users
$stmt = $conn->prepare("SELECT id_usuario, nombre, apellido, email FROM USUARIO ORDER BY id_usuario DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Admin - Nuevos Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <?php include 'partials/admin_sidebar_open.php'; ?>
    <div class="container mt-4">
        <h1 class="mb-4">Gestión de Usuarios</h1>

        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <h2>Crear Nuevo Usuario</h2>
        <form method="post" action="admin_NuevosUsuario.php" class="mb-4">
            <input type="hidden" name="action" value="create" />
            <div class="mb-3">
                <label for="username" class="form-label">Nombre de Usuario</label>
                <input type="text" class="form-control" id="username" name="username" required />
            </div>
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required />
            </div>
            <div class="mb-3">
                <label for="apellido" class="form-label">Apellido</label>
                <input type="text" class="form-control" id="apellido" name="apellido" required />
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required />
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required />
            </div>
            <button type="submit" class="btn btn-primary">Crear Usuario</button>
        </form>

        <h2>Usuarios Existentes</h2>
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nombre de Usuario</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Email</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <form method="post" action="admin_NuevosUsuario.php" class="d-flex align-items-center justify-content-center gap-2">
                            <td class="align-middle"><?php echo $user['id_usuario']; ?>
                                <input type="hidden" name="id_usuario" value="<?php echo $user['id_usuario']; ?>" />
                            </td>
                            <td class="align-middle"><input type="text" class="form-control form-control-sm" name="username" value="<?php echo htmlspecialchars($user['nombre']); ?>" required /></td>
                            <td class="align-middle"><input type="text" class="form-control form-control-sm" name="nombre" value="<?php echo htmlspecialchars($user['nombre']); ?>" required /></td>
                            <td class="align-middle"><input type="text" class="form-control form-control-sm" name="apellido" value="<?php echo htmlspecialchars($user['apellido']); ?>" required /></td>
                            <td class="align-middle"><input type="email" class="form-control form-control-sm" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required /></td>
                            <td class="align-middle">
                                <input type="password" class="form-control form-control-sm mb-1" name="password" placeholder="Nueva contraseña" />
                                <button type="submit" name="action" value="update" class="btn btn-sm btn-primary me-1">Actualizar</button>
                                <?php if (isset($_SESSION['user']['id']) && $_SESSION['user']['id'] != $user['id_usuario']): ?>
                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de eliminar este usuario?');">Eliminar</button>
                                <?php endif; ?>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include 'partials/admin_sidebar_close.php'; ?>
</body>
</html>
