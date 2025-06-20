<?php

require_once 'config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $user_type = $_POST["user_type"] ?? 'cliente';

    // Prevent re-login if already logged in
    if (isset($_SESSION["usuario_id"])) {
        header("Location: catalogo.php");
        exit;
    }

$stmt = $conn->prepare("SELECT * FROM USUARIO WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario && password_verify($password, $usuario["password_hash"])) {
    if ($user_type === 'operador') {
        $stmt2 = $conn->prepare("SELECT * FROM OPERADOR WHERE id_usuario = ?");
        $stmt2->execute([$usuario["id_usuario"]]);
        $operador = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($operador) {
            $_SESSION["usuario_id"] = $usuario["id_usuario"];
            $_SESSION["usuario_nombre"] = $usuario["nombre"];
            $_SESSION["user_type"] = 'operador';
            $_SESSION["cargo"] = $operador["cargo"];

            // Redirect based on cargo
            switch ($operador["cargo"]) {
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
                    header("Location: verificar_pedidos.php");
                    break;
                default:
                    header("Location: admin_index.php");
                    break;
            }
            exit;
        } else {
            $mensaje = "❌ No tienes permisos de operador.";
        }
    } elseif ($user_type === 'cliente') {
        $_SESSION["usuario_id"] = $usuario["id_usuario"];
        $_SESSION["usuario_nombre"] = $usuario["nombre"];
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/main.css" rel="stylesheet">
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
                <label class="form-label">Tipo de usuario</label>
                <select name="user_type" class="form-select" required>
                    <option value="cliente" selected>Cliente</option>
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
