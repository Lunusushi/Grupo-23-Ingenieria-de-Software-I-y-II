<?php
require_once 'config/MySqlDb.php';
require_once 'controllers/ProductController.php';
require_once 'partials/navbar.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$id_cliente = $_SESSION["usuario_id"] ?? 1;

$categoriasAll = ProductController::obtenerCategorias($conn);

// cat puede venir como int o array
$id_categorias = isset($_GET['cat']) ? (array)$_GET['cat'] : [];
$id_categorias = array_filter($id_categorias, fn($v) => ctype_digit((string)$v)); // sanea a dígitos

$min = isset($_GET['min']) && is_numeric($_GET['min']) ? (float)$_GET['min'] : null;
$max = isset($_GET['max']) && is_numeric($_GET['max']) ? (float)$_GET['max'] : null;
$q = trim($_GET['q'] ?? '');
$solo_disponibles = isset($_GET['disp']) ? 1 : 0;

$sort = $_GET['sort'] ?? 'recientes';
$validSort = ['recientes','precio_asc','precio_desc','nombre'];
if (!in_array($sort, $validSort, true)) $sort = 'recientes';

$page = max(1, (int)($_GET['page'] ?? 1));

// Filtros para el controlador
$filtros = [
  'cat' => $id_categorias,
  'min' => $min,
  'max' => $max,
  'q'   => $q,
  'solo_disponibles' => $solo_disponibles,
];

// Ejecutar listador paginado
$per = 12; // items por página (puedes cambiarlo o ponerlo en GET si quieres)
$res = ProductController::listarPaginado($conn, $filtros, $page, $per, $sort);

$productos = $res['items'] ?? [];
$total     = (int)($res['total'] ?? 0);
$pages     = max(1, (int)ceil($total / $per));

// Helper para construir query preservando filtros/orden
function buildQuery(array $extra = []) {
    $base = $_GET;
    unset($base['page']); // la manejamos aparte
    $query = array_merge($base, $extra);
    return http_build_query($query);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Catálogo - Los Cobres</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container my-4">
  <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <h2 class="mb-2">Catálogo de Productos</h2>
    <!-- Orden -->
    <form method="GET" class="d-flex gap-2">
      <?php if ($q !== ''): ?>
        <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
      <?php endif; ?>
      <?php
      // Reinyectar filtros como inputs hidden para no perderlos al cambiar orden
      if (!empty($id_categorias)) {
        foreach ($id_categorias as $cid) {
          echo '<input type="hidden" name="cat[]" value="'.htmlspecialchars($cid).'">';
        }
      }
      if ($min !== null) echo '<input type="hidden" name="min" value="'.htmlspecialchars($min).'">';
      if ($max !== null) echo '<input type="hidden" name="max" value="'.htmlspecialchars($max).'">';
      if ($solo_disponibles) echo '<input type="hidden" name="disp" value="1">';
      ?>
      <select name="sort" class="form-select">
        <option value="recientes"   <?= $sort==='recientes'?'selected':'' ?>>Más recientes</option>
        <option value="precio_asc"  <?= $sort==='precio_asc'?'selected':'' ?>>Precio: menor a mayor</option>
        <option value="precio_desc" <?= $sort==='precio_desc'?'selected':'' ?>>Precio: mayor a menor</option>
        <option value="nombre"      <?= $sort==='nombre'?'selected':'' ?>>Nombre</option>
      </select>
      <button class="btn btn-outline-primary">Ordenar</button>
    </form>
  </div>

  <!-- Filtros -->
  <form class="card p-3 mb-4" method="GET">
    <?php if ($q !== ''): ?>
      <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
    <?php endif; ?>
    <div class="row g-3">
      <div class="col-12">
        <div class="fw-semibold mb-1">Categorías</div>
        <div class="row">
          <?php foreach ($categoriasAll as $cat): ?>
            <div class="col-12 col-sm-6 col-md-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="cat[]"
                       value="<?= (int)$cat['id_categoria'] ?>"
                       <?= in_array((string)$cat['id_categoria'], array_map('strval',$id_categorias), true) ? 'checked' : '' ?>>
                <label class="form-check-label"><?= htmlspecialchars($cat['nombre_categoria']) ?></label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Precio mínimo</label>
        <input type="number" step="1" min="0" class="form-control" name="min"
               value="<?= $min !== null ? htmlspecialchars($min) : '' ?>">
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Precio máximo</label>
        <input type="number" step="1" min="0" class="form-control" name="max"
               value="<?= $max !== null ? htmlspecialchars($max) : '' ?>">
      </div>
      <div class="col-12 col-md-3 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="disp" name="disp" <?= $solo_disponibles ? 'checked':'' ?>>
          <label class="form-check-label" for="disp">Sólo disponibles</label>
        </div>
      </div>

      <!-- Mantener sort al aplicar filtros -->
      <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

      <div class="col-12 col-md-3 d-flex align-items-end justify-content-end">
        <button class="btn btn-primary w-100">Aplicar filtros</button>
      </div>
    </div>
  </form>

  <!-- Resumen -->
  <div class="mb-3 text-muted">
    <?= number_format($total, 0, ',', '.') ?> resultado<?= $total===1?'':'s' ?> · Página <?= $page ?> de <?= $pages ?>
  </div>

  <!-- Productos -->
  <div class="row">
    <?php foreach ($productos as $p): ?>
      <div class="col-md-4">
        <div class="card mb-4 h-100">
          <img src="<?= htmlspecialchars($p['url_imagen_principal']) ?>"
               class="card-img-top"
               style="height: 200px; object-fit: cover;" alt="Producto">
          <div class="card-body d-flex flex-column">
            <!-- Badges -->
            <div class="mb-2">
              <?php if (!empty($p['es_nuevo'])):   ?><span class="badge bg-primary me-1">Nuevo</span><?php endif; ?>
              <?php if (!empty($p['es_oferta'])):  ?><span class="badge bg-danger me-1">Oferta</span><?php endif; ?>
              <?php if (!empty($p['es_popular'])): ?><span class="badge bg-success me-1">Popular</span><?php endif; ?>
            </div>

            <h5 class="card-title mb-1">
              <a href="producto.php?id=<?= urlencode($p['id_producto']) ?>"
                 class="text-decoration-none text-dark">
                <?= htmlspecialchars($p['nombre_producto']) ?>
              </a>
            </h5>
            <p class="mb-1"><strong>$<?= number_format((float)$p['precio_unitario'], 0, ',', '.') ?></strong></p>
            <p class="text-muted mb-3">Stock: <?= htmlspecialchars((string)$p['stock_actual']) ?></p>

            <form method="POST" action="carrito.php" class="mt-auto">
              <input type="hidden" name="id_producto" value="<?= (int)$p['id_producto'] ?>">
              <div class="input-group">
                <input type="number" name="cantidad" class="form-control" value="1" min="1" step="1" required>
                <button class="btn btn-success" type="submit">🛒</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if (empty($productos)): ?>
      <div class="col-12">
        <div class="alert alert-warning">No se encontraron productos con los filtros aplicados.</div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Paginación -->
  <?php if ($pages > 1): ?>
    <nav aria-label="Paginación catálogo">
      <ul class="pagination justify-content-center">
        <li class="page-item <?= $page<=1?'disabled':'' ?>">
          <a class="page-link" href="?<?= buildQuery(['page'=>1]) ?>">«</a>
        </li>
        <li class="page-item <?= $page<=1?'disabled':'' ?>">
          <a class="page-link" href="?<?= buildQuery(['page'=>$page-1]) ?>">‹</a>
        </li>

        <?php
        // Ventana de páginas (ej. 2 antes y 2 después)
        $start = max(1, $page-2);
        $end   = min($pages, $page+2);
        for ($i=$start; $i <= $end; $i++): ?>
          <li class="page-item <?= $i===$page?'active':'' ?>">
            <a class="page-link" href="?<?= buildQuery(['page'=>$i]) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>

        <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
          <a class="page-link" href="?<?= buildQuery(['page'=>$page+1]) ?>">›</a>
        </li>
        <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
          <a class="page-link" href="?<?= buildQuery(['page'=>$pages]) ?>">»</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>

</div>
</body>
</html>