<?php

//header("Location: catalogo.php");
//exit;
// Esto no tiene que ir aqui.

require_once 'config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM USUARIO WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificacion para que no se vuelan a logear
    if (isset($_SESSION["usuario_id"])) {
    header("Location: catalogo.php");
    exit;
}
    if ($usuario && password_verify($password, $usuario["password_hash"])) {
        $_SESSION["usuario_id"] = $usuario["id_usuario"];
        $_SESSION["usuario_nombre"] = $usuario["nombre"];
        header("Location: index.php");
        exit;
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
