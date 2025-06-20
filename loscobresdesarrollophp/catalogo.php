<?php
require_once 'config/MySqlDb.php';
require_once 'controllers/CatalogController.php';
require_once 'partials/navbar.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_cliente = $_SESSION["usuario_id"] ?? 1;

$categorias = CatalogController::obtenerCategorias($conn);
$id_categorias = $_GET["cat"] ?? [];
$productos = CatalogController::obtenerProductos($conn, $id_categorias);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Catálogo - Los Cobres</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="public/css/main.css" rel="stylesheet">
</head>
<body>

<div class="container">
  <h2 class="mb-4">Catálogo de Productos</h2>

  <!-- Filtros -->
  <form class="mb-4" method="GET">
    <div class="row">
      <?php foreach ($categorias as $cat): ?>
        <div class="col-md-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="cat[]" value="<?= $cat["id_categoria"] ?>"
              <?= (in_array($cat["id_categoria"], $id_categorias)) ? "checked" : "" ?>>
            <label class="form-check-label"><?= $cat["nombre_categoria"] ?></label>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-primary mt-2" type="submit">Aplicar Filtro</button>
  </form>

  <!-- Productos -->
  <div class="row">
    <?php foreach ($productos as $p): ?>
      <div class="col-md-4">
        <div class="card mb-4">
          <img src="<?= $p["url_imagen_principal"] ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
          <div class="card-body">
            <h5 class="card-title"><?= $p["nombre_producto"] ?></h5>
            <p class="card-text"><?= $p["descripcion"] ?></p>
            <p><strong>$<?= $p["precio_unitario"] ?></strong></p>
            <p>Stock: <?= $p["stock_actual"] ?></p>

            <form method="POST" action="carrito.php" class="mb-2">
              <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
              <div class="input-group">
                <input type="number" name="cantidad" class="form-control" value="1" min="1" required>
                <button class="btn btn-success" type="submit">🛒</button>
              </div>
            </form>

            <form method="POST" action="favoritos.php">
              <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
              <button class="btn btn-warning w-100">⭐ Agregar a favoritos</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

</body>
</html>
