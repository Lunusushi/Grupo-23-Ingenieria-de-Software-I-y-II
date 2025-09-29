<?php
require_once 'controllers/AuthenticationController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $tipo_usuario = $_POST["tipo_usuario"]; // 'cliente' o 'operador'

    // Prevenir re-login
    if (isset($_SESSION["user"])) {
        header("Location: catalogo.php");
        exit;
    }

    $loginSuccess = AuthenticationController::login($email, $password, $tipo_usuario);

    if ($loginSuccess) {
        $user = AuthenticationController::getCurrentUser();

        if ($user['type'] === 'operador') {
            $_SESSION["user_type"] = 'operador';
            $_SESSION["cargo"] = $user['cargo'];
            $_SESSION["id_operador"] = $user['id'];

            switch ($user['cargo']) {
                case 'administrador':
                    header("Location: admin_index.php");
                    break;
                case 'mantenedor':
                    header("Location: permisos_admin.php");
                    break;
                case 'catalogo':
                    header("Location: productos_admin.php");
                    break;
                case 'caja':
                    header("Location: verificar_pedido.php");
                    break;
                default:
                    header("Location: admin_index.php");
                    break;
            }
            exit;
        } elseif ($user['type'] === 'cliente') {
            $_SESSION["user_type"] = 'cliente';
            header("Location: catalogo.php");
            exit;
        } else {
            $mensaje = "❌ Tipo de usuario no válido.";
        }
    } else {
        $mensaje = "❌ Credenciales incorrectas.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Los Cobres</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 w-100" style="max-width: 400px;">
        <h3 class="text-center mb-4">Iniciar sesión</h3>

        <?php if ($mensaje): ?>
            <div class="alert alert-danger"><?= $mensaje ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" name="email" class="form-control" required autofocus>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="tipo_usuario" class="form-label">Tipo de usuario</label>
                <select name="tipo_usuario" class="form-select" required>
                    <option value="cliente">Cliente</option>
                    <option value="operador">Operador</option>
                </select>
            </div>

            <button class="btn btn-primary w-100">Entrar</button>
        </form>

        <div class="mt-3 text-center">
            <a href="register.php">¿No tienes cuenta? Regístrate</a>
        </div>
        <div class="mt-2 text-center">
            <a href="index.php" class="text-secondary">← Volver al inicio</a>
        </div>
    </div>
</div>

</body>
</html>
