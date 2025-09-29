<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Inicio | Los Cobres</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

  <?php include __DIR__ . '/partials/navbar.php'; ?>

  <div class="container mt-5">
    <div class="text-center">
      <h1 class="display-4">Bienvenido a Los Cobres</h1>
      <p class="lead">Menu administrador</p>
      <hr class="my-4">
    </div>
  </div>
</body>
</html>