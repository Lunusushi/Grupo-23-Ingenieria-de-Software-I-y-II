<?php
  if (session_status() === PHP_SESSION_NONE) session_start();
  require_once __DIR__ . '/config/MySqlDb.php';
  require_once __DIR__ . '/controllers/ProductController.php';

  // Guard extra según rol (opcional)
  $user = $_SESSION['user'] ?? null;
  $cargo = $_SESSION['cargo'] ?? null;
  if (($user['type'] ?? null) !== 'operador' || !in_array($cargo, ['administrador','catalogo'], true)) {
    http_response_code(403);
    die('<div class="container my-5"><div class="alert alert-danger">No autorizado.</div></div>');
  }

    $flash = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $id_producto = (int)($_POST['id_producto'] ?? 0);
            $titulo      = trim($_POST['titulo'] ?? '') ?: null;
            $subtitulo   = trim($_POST['subtitulo'] ?? '') ?: null;
            $imagen_url  = trim($_POST['imagen_url'] ?? '') ?: null;
            $orden       = (int)($_POST['orden'] ?? 0);
            $activo      = isset($_POST['activo']) ? 1 : 0;

            if ($id_producto > 0) {
                ProductController::crearPromoHome($conn, $id_producto, $titulo, $subtitulo, $imagen_url, $orden, (bool)$activo);
                $flash = '✅ Promo creada.';
            } else {
                $flash = '❌ Selecciona un producto válido.';
            }
        }

        if ($action === 'update') {
            $id_promo  = (int)($_POST['id_promo'] ?? 0);
            $orden     = (int)($_POST['orden'] ?? 0);
            $activo    = isset($_POST['activo']) ? 1 : 0;
            $titulo    = trim($_POST['titulo'] ?? '') ?: null;
            $subtitulo = trim($_POST['subtitulo'] ?? '') ?: null;
            $imagen_url= trim($_POST['imagen_url'] ?? '') ?: null;

            if ($id_promo > 0) {
                ProductController::actualizarPromoHome($conn, $id_promo, [
                    'orden' => $orden,
                    'activo'=> $activo,
                    'titulo'=> $titulo,
                    'subtitulo'=> $subtitulo,
                    'imagen_url'=> $imagen_url,
                ]);
                $flash = '✅ Promo actualizada.';
            }
        }

        if ($action === 'delete') {
            $id_promo = (int)($_POST['id_promo'] ?? 0);
            if ($id_promo > 0) {
                ProductController::eliminarPromoHome($conn, $id_promo);
                $flash = '🗑️ Promo eliminada.';
            }
        }

        header('Location: promos_admin.php?msg='.urlencode($flash));
        exit;
    }

    if (isset($_GET['msg'])) $flash = $_GET['msg'];

    $promos = ProductController::listarPromosHome($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Promos Home | Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>.suggest-box{position:absolute;z-index:1000;width:100%;display:none;}</style>
</head>
<body class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/partials/admin_sidebar_open.php';?>
<div class="container my-4">
  <h1 class="h4 mb-3">🎯 Promociones del Home (Carrusel)</h1>

  <?php if ($flash): ?>
    <div class="alert alert-info"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <!-- Crear nueva -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title">Nueva promo</h5>
      <form method="POST" class="row g-3 position-relative">
        <input type="hidden" name="action" value="create">
        <div class="col-12 col-md-6">
          <label class="form-label">Buscar producto</label>
            <input type="text" id="promo-buscar" class="form-control" placeholder="Escribe parte del nombre..." autocomplete="off">
            <div id="promo-suggestions" class="list-group suggest-box"></div>
        </div>
        <div class="col-12 col-md-2">
          <label class="form-label">ID producto</label>
          <input type="number" class="form-control" name="id_producto" id="id_producto" required>
        </div>
        <div class="col-12 col-md-2">
          <label class="form-label">Orden</label>
          <input type="number" class="form-control" name="orden" value="0">
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="activo" id="activo" checked>
            <label class="form-check-label" for="activo">Activo</label>
          </div>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label">Título (opcional)</label>
          <input type="text" class="form-control" name="titulo" id="titulo">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label">Subtítulo (opcional)</label>
          <input type="text" class="form-control" name="subtitulo" id="subtitulo">
        </div>
        <div class="col-12">
          <label class="form-label">Imagen personalizada (URL, opcional)</label>
          <input type="url" class="form-control" name="imagen_url" placeholder="Si vacío, usa imagen principal del producto">
        </div>
        <div class="col-12 text-end">
          <button class="btn btn-primary">Agregar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Listado -->
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Preview</th>
          <th>Producto</th>
          <th>Título/Subtítulo</th>
          <th>Orden</th>
          <th>Activo</th>
          <th style="width:160px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($promos as $ph): ?>
          <tr>
            <td>
              <img src="<?= htmlspecialchars($ph['imagen_url'] ?: $ph['url_imagen_principal']) ?>" style="width:100px;height:60px;object-fit:cover" class="rounded">
            </td>
            <td>
              <div class="fw-semibold"><?= htmlspecialchars($ph['nombre_producto']) ?></div>
              <div class="text-muted small">#<?= (int)$ph['id_producto'] ?></div>
            </td>
            <td class="small">
              <form method="POST" class="row g-2">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id_promo" value="<?= (int)$ph['id_promo'] ?>">
                <div class="col-12">
                  <input type="text" class="form-control form-control-sm" name="titulo" placeholder="Título..." value="<?= htmlspecialchars($ph['titulo'] ?? '') ?>">
                </div>
                <div class="col-12">
                  <input type="text" class="form-control form-control-sm" name="subtitulo" placeholder="Subtítulo..." value="<?= htmlspecialchars($ph['subtitulo'] ?? '') ?>">
                </div>
                <div class="col-12">
                  <input type="url" class="form-control form-control-sm" name="imagen_url" placeholder="URL imagen (opcional)" value="<?= htmlspecialchars($ph['imagen_url'] ?? '') ?>">
                </div>
            </td>
            <td style="max-width:120px;">
                <input type="number" class="form-control form-control-sm" name="orden" value="<?= (int)$ph['orden'] ?>">
            </td>
            <td>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="activo" <?= !empty($ph['activo']) ? 'checked' : '' ?>>
                </div>
            </td>
            <td class="text-end">
                <button class="btn btn-sm btn-success">Guardar</button>
              </form>
              <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar promo?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id_promo" value="<?= (int)$ph['id_promo'] ?>">
                <button class="btn btn-sm btn-danger">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($promos)): ?>
          <tr><td colspan="6" class="text-center text-muted">No hay promos aún. Crea la primera arriba.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/partials/admin_sidebar_close.php'; // cierra main + flex ?>

<script>
  // Autocomplete de producto usando tu endpoint existente
  const input = document.getElementById('promo-buscar');
  const box   = document.getElementById('promo-suggestions');
  const idInp = document.getElementById('id_producto');
  const titl  = document.getElementById('titulo');

  input.addEventListener('input', async () => {
    const q = input.value.trim();
    if (!q) { box.style.display='none'; box.innerHTML=''; return; }

    const res = await fetch(`controllers/productController.php?action=buscar&q=${encodeURIComponent(q)}`);
    const items = await res.json();

    box.innerHTML = '';
    items.forEach(p => {
      const a = document.createElement('a');
      a.href = '#';
      a.className = 'list-group-item list-group-item-action';
      a.textContent = `#${p.id_producto} — ${p.nombre_producto}`;
      a.onclick = (e) => {
        e.preventDefault();
        idInp.value = p.id_producto;
        titl.value  = p.nombre_producto; // opcional
        box.style.display='none';
        box.innerHTML='';
      };
      box.appendChild(a);
    });
    box.style.display = items.length ? 'block' : 'none';
  });

  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !box.contains(e.target)) {
      box.style.display='none';
    }
  });
</script>
</body>
</html>
