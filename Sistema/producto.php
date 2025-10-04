<?php
  // producto.php
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }

  require_once __DIR__ . '/config/MySqlDb.php';      // $conn (PDO)
  require_once __DIR__ . '/partials/navbar.php';     // Navbar Bootstrap

  // 1) Validar parámetro
  if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
      http_response_code(400);
      $error = "Producto no especificado o ID inválido.";
  } else {
      $id = (int) $_GET['id'];

      // 2) Buscar producto principal
      $stmt = $conn->prepare("
        SELECT p.id_producto, p.nombre_producto, p.descripcion, p.precio_unitario,
              p.stock_actual, p.url_imagen_principal, p.activo
        FROM PRODUCTO p
        INNER JOIN CATEGORIA c ON c.id_categoria = p.id_categoria AND c.activa = 1
        WHERE p.id_producto = :id AND p.activo = 1
        LIMIT 1
      ");
      $stmt->execute([':id' => $id]);
      $producto = $stmt->fetch(PDO::FETCH_ASSOC);
      
      if (!$producto || (int)$producto['activo'] !== 1) {
          http_response_code(404);
          $error = "Producto no encontrado o inactivo.";
      } else {
          // 3) (Opcional) Galería extra
          $gal = $conn->prepare("SELECT url_imagen FROM IMAGEN_PRODUCTO WHERE id_producto = ? ORDER BY COALESCE(orden, 9999), id_imagen");
          $gal->execute([$producto['id_producto']]);
          $imagenesExtra = $gal->fetchAll(PDO::FETCH_ASSOC);
      }
  }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= isset($producto) ? htmlspecialchars($producto['nombre_producto']) . ' | Los Cobres' : 'Producto | Los Cobres' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="d-flex flex-column min-vh-100">
  <main class="flex-grow-1">
  <div class="container my-4">

    <?php if (!empty($error)): ?>
      <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
      <a href="catalogo.php" class="btn btn-secondary">← Volver al catálogo</a>
    <?php else: ?>
      <div class="row g-4">
        <!-- Imagen principal -->
        <div class="col-12 col-md-5 col-lg-4">
          <div class="card">
            <img
              src="<?= htmlspecialchars($producto['url_imagen_principal']) ?>"
              class="card-img-top"
              alt="<?= htmlspecialchars($producto['nombre_producto']) ?>"
              style="object-fit: cover; height: 320px;">
          </div>

          <?php if (!empty($imagenesExtra)): ?>
            <div class="mt-3 d-flex flex-wrap gap-2">
              <?php foreach ($imagenesExtra as $im): ?>
                <img src="<?= htmlspecialchars($im['url_imagen']) ?>"
                    alt="Imagen adicional"
                    class="rounded border"
                    style="width:78px; height:78px; object-fit:cover;">
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Detalles -->
        <div class="col-12 col-md-7 col-lg-8">
          <h1 class="h3 mb-3"><?= htmlspecialchars($producto['nombre_producto']) ?></h1>
          <?php
            $precio = number_format((float)$producto['precio_unitario'], 0, ',', '.');
            $stock  = (float)$producto['stock_actual'];
            $stockEntero = (int)$stock; // si manejas unidades enteras
          ?>
          <p class="lead mb-1"><strong>Precio:</strong> $<?= $precio ?></p>
          <p class="text-muted">Stock: <?= $stock > 0 ? $stock : 'Agotado' ?></p>

          <?php if (!empty($producto['descripcion'])): ?>
            <div class="mb-3">
              <h2 class="h6">Descripción</h2>
              <p class="mb-0"><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></p>
            </div>
          <?php endif; ?>

          <!-- Form para agregar al carrito (igual que en el catálogo) -->
          <?php if ($stock > 0): ?>
            <form method="POST" action="carrito.php" class="mb-3">
              <input type="hidden" name="id_producto" value="<?= htmlspecialchars($producto['id_producto']) ?>">

              <div class="input-group" style="max-width: 280px;">
                <input
                  type="number"
                  name="cantidad"
                  class="form-control"
                  value="1"
                  min="1"
                  step="1"
                  <?php if ($stockEntero > 0): ?>max="<?= (int)$stockEntero ?>"<?php endif; ?>
                  required
                >
                <button class="btn btn-success" type="submit">🛒 Agregar</button>
              </div>
              <small class="text-muted">Stock disponible: <?= $stockEntero ?></small>
            </form>
          <?php else: ?>
            <button class="btn btn-secondary" disabled>Agotado</button>
          <?php endif; ?>

          <div class="mt-3">
            <a href="catalogo.php" class="btn btn-outline-secondary">← Volver al catálogo</a>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
  </main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>