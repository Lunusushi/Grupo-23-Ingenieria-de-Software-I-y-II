<?php
session_start();
if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/MySqlDb.php';
require_once 'controllers/ClientController.php';

$id_usuario = $_SESSION['user']['id'] ?? null;
if (!$id_usuario) {
    header("Location: login.php");
    exit();
}
// Map id_usuario to id_cliente
$stmt = $conn->prepare("SELECT id_cliente FROM CLIENTE WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo "<div class='alert alert-danger'>No tienes acceso a esta página o no eres un cliente registrado.</div>";
    include __DIR__ . '/partials/footer.php';
    exit();
}
$id_cliente = $row['id_cliente'];

$lista = ClientController::obtenerLista($conn, $id_cliente);
if (!$lista) {
    echo "<div class='alert alert-danger'>No tienes acceso a esta página o no eres un cliente registrado.</div>";
    include __DIR__ . '/partials/footer.php';
    exit();
}
$id_lista = $lista["id_lista"];

$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $id_producto = $_POST["id_producto"];
        $mensaje = ClientController::agregarFavorito($conn, $id_lista, $id_producto);
    } elseif ($action === 'remove') {
        $id_producto = $_POST["id_producto"];
        if (ClientController::eliminarFavorito($conn, $id_lista, $id_producto)) {
            $mensaje = "✅ Producto eliminado de favoritos.";
        } else {
            $mensaje = "⚠️ Error al eliminar el producto.";
        }
    }
}

// Filtros
$q = $_GET['q'] ?? '';
$sort = $_GET['sort'] ?? 'recientes';
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;

$favoritos = ClientController::obtenerFavoritos($conn, $id_lista, $q, $sort, $minPrice, $maxPrice);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Favoritos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/partials/navbar.php'; ?>
<div class="container my-4 flex-grow-1">
  <h2 class="mb-4">⭐ Mis Favoritos</h2>

  <?php if ($mensaje): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
  <?php endif; ?>

  <!-- Filtros -->
  <form class="card p-3 mb-4" method="GET">
    <div class="row g-3">
      <div class="col-12 col-md-4">
        <label class="form-label">Buscar por nombre</label>
        <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($q) ?>">
      </div>
      <div class="col-12 col-md-2">
        <label class="form-label">Precio mínimo</label>
        <input type="number" name="min_price" class="form-control" placeholder="0" min="0" step="0.01" value="<?= htmlspecialchars($minPrice ?? '') ?>">
      </div>
      <div class="col-12 col-md-2">
        <label class="form-label">Precio máximo</label>
        <input type="number" name="max_price" class="form-control" placeholder="0" min="0" step="0.01" value="<?= htmlspecialchars($maxPrice ?? '') ?>">
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Ordenar por</label>
        <select name="sort" class="form-select">
          <option value="recientes" <?= $sort === 'recientes' ? 'selected' : '' ?>>Más recientes</option>
          <option value="nombre_asc" <?= $sort === 'nombre_asc' ? 'selected' : '' ?>>Nombre A-Z</option>
          <option value="nombre_desc" <?= $sort === 'nombre_desc' ? 'selected' : '' ?>>Nombre Z-A</option>
          <option value="precio_asc" <?= $sort === 'precio_asc' ? 'selected' : '' ?>>Precio menor a mayor</option>
          <option value="precio_desc" <?= $sort === 'precio_desc' ? 'selected' : '' ?>>Precio mayor a menor</option>
        </select>
      </div>
      <div class="col-12 col-md-1 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
      </div>
    </div>
  </form>

  <?php if (count($favoritos) == 0): ?>
      <div class="alert alert-warning">No tienes productos en tu lista de favoritos.</div>
  <?php else: ?>
      <div class="row">
          <?php foreach ($favoritos as $f): ?>
              <div class="col-md-4">
                  <div class="card mb-4 h-100">
                      <img src="<?= htmlspecialchars($f["url_imagen_principal"]) ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Producto">
                      <div class="card-body d-flex flex-column">
                          <h5 class="card-title"><?= htmlspecialchars($f["nombre_producto"]) ?></h5>
                          <p class="mb-1"><strong>$<?= number_format($f["precio_unitario"], 0, ',', '.') ?></strong></p>
                          <p class="text-muted mb-3">Agregado el: <?= htmlspecialchars(substr($f["fecha_agregado"], 0, 10)) ?></p>
                          <form method="POST" class="mt-auto">
                              <input type="hidden" name="action" value="remove">
                              <input type="hidden" name="id_producto" value="<?= (int)$f["id_producto"] ?>">
                              <button type="submit" class="btn btn-danger">Eliminar</button>
                          </form>
                      </div>
                  </div>
              </div>
          <?php endforeach; ?>
      </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
