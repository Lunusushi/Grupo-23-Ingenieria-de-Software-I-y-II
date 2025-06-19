<?php
require_once 'config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Declarar variables primero
    $nombre = $_POST["nombre"];
    $apellido = $_POST["apellido"];
    $email = $_POST["email"];
    $rut = $_POST["rut"];
    $telefono = $_POST["telefono"];

    // Validar contraseñas
    if ($_POST["password"] !== $_POST["password2"]) {
        $mensaje = "❌ Las contraseñas no coinciden.";
    } else {
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare("INSERT INTO USUARIO (nombre, apellido, email, password_hash, rut, telefono, fecha_registro)
                                    VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$nombre, $apellido, $email, $password, $rut, $telefono]);
            $id_usuario = $conn->lastInsertId();

            $stmt2 = $conn->prepare("INSERT INTO CLIENTE (id_usuario) VALUES (?)");
            $stmt2->execute([$id_usuario]);

            $mensaje = "✅ Cuenta registrada correctamente.";
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $mensaje = "❌ Ya existe una cuenta registrada con ese correo.";
            } else {
                $mensaje = "❌ Error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - Los Cobres</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/main.css" rel="stylesheet">
</head>
<body>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 w-100" style="max-width: 500px;">
        <h3 class="text-center mb-4">Crear cuenta</h3>

        <?php if ($mensaje): ?>
            <div class="alert <?= str_starts_with($mensaje, '✅') ? 'alert-success' : 'alert-danger' ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nombre</label>
                    <input name="nombre" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Apellido</label>
                    <input name="apellido" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Correo electrónico</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirmar Contraseña</label>
                <input type="password" name="password2" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">RUT</label>
                <input name="rut" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Teléfono</label>
                <input name="telefono" class="form-control" required>
            </div>

            <button class="btn btn-primary w-100">Registrarme</button>
        </form>

        <div class="mt-3 text-center">
            <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
        </div>
    </div>
</div>

</body>
</html>
