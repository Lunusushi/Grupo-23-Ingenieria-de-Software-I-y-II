<?php
session_start();
if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/MySqlDb.php';
require_once 'controllers/ClientController.php';

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/main.css" rel="stylesheet">
</head>
<body>

<div class="container">
  <h2>⭐ Mis Favoritos</h2>

  <?php if ($mensaje): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
  <?php endif; ?>

  <?php if (count($favoritos) == 0): ?>
      <div class="alert alert-warning">No tienes productos en tu lista de favoritos.</div>
  <?php else: ?>
      <div class="d-flex flex-wrap gap-3">
          <?php foreach ($favoritos as $f): ?>
              <div class="card" style="width: 18rem;">
                  <img src="<?= htmlspecialchars($f["url_imagen_principal"]) ?>" class="card-img-top" alt="Producto">
                  <div class="card-body">
                      <h5 class="card-title"><?= htmlspecialchars($f["nombre_producto"]) ?></h5>
                      <p class="card-text">Agregado el: <?= htmlspecialchars(substr($f["fecha_agregado"], 0, 10)) ?></p>
                  </div>
              </div>
          <?php endforeach; ?>
      </div>
  <?php endif; ?>
</div>

</body>
</html>
