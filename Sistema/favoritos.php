<?php
session_start();
if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/MySqlDb.php';
require_once 'controllers/ClientController.php';
require_once 'partials/navbar.php'; // Incluyo el navbar común

$id_cliente = $_SESSION['user']['id'];
$lista = ClientController::obtenerLista($conn, $id_cliente);
$id_lista = $lista["id_lista"];

$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_producto = $_POST["id_producto"];
    $mensaje = ClientController::agregarFavorito($conn, $id_lista, $id_producto);
}

$favoritos = ClientController::obtenerFavoritos($conn, $id_lista);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Favoritos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container mt-4">
  <h2 class="mb-4">⭐ Mis Favoritos</h2>

  <?php if ($mensaje): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
  <?php endif; ?>

  <?php if (count($favoritos) == 0): ?>
      <div class="alert alert-warning">No tienes productos en tu lista de favoritos.</div>
  <?php else: ?>
      <div class="row g-3">
          <?php foreach ($favoritos as $f): ?>
              <div class="col-md-4 col-sm-6">
                  <div class="card h-100 shadow-sm">
                      <img src="<?= htmlspecialchars($f["url_imagen_principal"]) ?>" class="card-img-top" alt="Producto">
                      <div class="card-body d-flex flex-column">
                          <h5 class="card-title"><?= htmlspecialchars($f["nombre_producto"]) ?></h5>
                          <p class="card-text mt-auto"><small class="text-muted">Agregado el: <?= htmlspecialchars(substr($f["fecha_agregado"], 0, 10)) ?></small></p>
                      </div>
                  </div>
              </div>
          <?php endforeach; ?>
      </div>
  <?php endif; ?>
</div>

</body>
</html>
