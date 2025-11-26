<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$user = $_SESSION['user'] ?? null;
if (($user['type'] ?? null) !== 'operador') { header('Location: login.php'); exit; }

require_once __DIR__ . '/config/MySqlDb.php';
require_once __DIR__ . '/controllers/ProductController.php';

$cargo        = $_SESSION['cargo'] ?? null;
$puedeEditar  = in_array($cargo, ['administrador','mantenedor','catalogo'], true);

$flash = '';
$editando = false;
$col = null;

// Modo edición
if (isset($_GET['editar']) && ctype_digit((string)$_GET['editar'])) {
  $col = PlantillaCategoriaController::obtenerColeccion($conn, (int)$_GET['editar']);
  if ($col) $editando = true;
}

// Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $puedeEditar) {
  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $nombre = trim($_POST['nombre'] ?? '');
    $activa = isset($_POST['activa']);
    if ($nombre === '') {
      $flash = '❌ El nombre es obligatorio.';
    } else {
      PlantillaCategoriaController::crearColeccion($conn, $nombre, $activa);
      $flash = '✅ Colección creada.';
    }
    header('Location: admin_plantillas_cat.php?msg='.urlencode($flash));
    exit;
  }

  if ($action === 'update') {
    $id     = (int)($_POST['id_plantilla_cat'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $activa = isset($_POST['activa']);
    if ($id > 0 && $nombre !== '') {
      PlantillaCategoriaController::actualizarColeccion($conn, $id, $nombre, $activa);
      $flash = '✅ Colección actualizada.';
    } else {
      $flash = '❌ Datos incompletos.';
    }
    header('Location: admin_plantillas_cat.php?editar='.$id.'&msg='.urlencode($flash));
    exit;
  }

  if ($action === 'toggle') {
    $id  = (int)($_POST['id_plantilla_cat'] ?? 0);
    $val = (int)($_POST['nuevo_activa'] ?? 1);
    if ($id > 0) {
      PlantillaCategoriaController::setActiva($conn, $id, (bool)$val);
      $flash = $val ? '✅ Colección activada.' : '⛔ Colección desactivada.';
    }
    header('Location: admin_plantillas_cat.php?msg='.urlencode($flash));
    exit;
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id_plantilla_cat'] ?? 0);
    if ($id > 0) {
      PlantillaCategoriaController::eliminarColeccion($conn, $id);
      $flash = '🗑️ Colección eliminada.';
    }
    header('Location: admin_plantillas_cat.php?msg='.urlencode($flash));
    exit;
  }

  if ($action === 'save_members') {
    $id = (int)($_POST['id_plantilla_cat'] ?? 0);
    $catIds = $_POST['cat_ids'] ?? [];
    $orden  = $_POST['orden']   ?? [];
    if ($id > 0) {
      PlantillaCategoriaController::guardarCategoriasDeColeccion($conn, $id, (array)$catIds, (array)$orden);
      $flash = '💾 Categorías guardadas.';
    }
    header('Location: admin_plantillas_cat.php?editar='.$id.'&msg='.urlencode($flash));
    exit;
  }
}

if (isset($_GET['msg'])) $flash = $_GET['msg'];

$colecciones = PlantillaCategoriaController::listarColecciones($conn);
$categorias  = PlantillaCategoriaController::obtenerCategoriasAdmin($conn);

$miembros = [];
if ($editando) {
  $miembros = PlantillaCategoriaController::obtenerCategoriasDeColeccion($conn, (int)$col['id_plantilla_cat']); // [id_cat => orden]
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Colecciones de categorías | Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>.text-truncate-1{display:inline-block;max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}</style>
</head>
<body>
<?php include __DIR__ . '/partials/admin_sidebar_open.php'; ?>

<div class="container my-4">
  <h1 class="h4 mb-3">🧩 Colecciones de categorías</h1>

  <?php if ($flash): ?>
    <div class="alert alert-info"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <?php if ($puedeEditar): ?>
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title"><?= $editando ? 'Editar colección' : 'Nueva colección' ?></h5>
      <form method="POST" class="row g-3">
        <input type="hidden" name="action" value="<?= $editando ? 'update' : 'create' ?>">
        <?php if ($editando): ?>
          <input type="hidden" name="id_plantilla_cat" value="<?= (int)$col['id_plantilla_cat'] ?>">
        <?php endif; ?>

        <div class="col-md-6">
          <label class="form-label">Nombre *</label>
          <input type="text" name="nombre" class="form-control" required value="<?= $editando ? htmlspecialchars($col['nombre']) : '' ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="activa" id="chkActiva" <?= !$editando || !empty($col['activa']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="chkActiva">Activa</label>
          </div>
        </div>
        <div class="col-md-3 d-flex align-items-end justify-content-end">
          <?php if ($editando): ?>
            <a href="admin_plantillas_cat.php" class="btn btn-secondary me-2">Cancelar</a>
          <?php endif; ?>
          <button class="btn btn-primary"><?= $editando ? 'Guardar cambios' : 'Crear' ?></button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($editando): ?>
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title">Categorías en “<?= htmlspecialchars($col['nombre']) ?>”</h5>
      <form method="POST">
        <input type="hidden" name="action" value="save_members">
        <input type="hidden" name="id_plantilla_cat" value="<?= (int)$col['id_plantilla_cat'] ?>">

        <div class="table-responsive" style="max-height:520px;">
          <table class="table table-sm align-middle">
            <thead><tr>
              <th style="width:60px;">Usar</th>
              <th>Categoría</th>
              <th>Padre</th>
              <th>Estado</th>
              <th style="width:120px;">Orden</th>
            </tr></thead>
            <tbody>
            <?php foreach ($categorias as $c):
              $cid = (int)$c['id_categoria'];
              $checked = array_key_exists($cid, $miembros);
              $ordenV  = $checked ? (int)$miembros[$cid] : 0;
            ?>
              <tr>
                <td><input type="checkbox" class="form-check-input" name="cat_ids[]" value="<?= $cid ?>" <?= $checked ? 'checked' : '' ?>></td>
                <td class="text-truncate-1"><?= htmlspecialchars($c['nombre_categoria']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($c['nombre_padre'] ?? '—') ?></td>
                <td>
                  <span class="badge <?= !empty($c['activa']) ? 'bg-success' : 'bg-secondary' ?>">
                    <?= !empty($c['activa']) ? 'Activa' : 'Inactiva' ?>
                  </span>
                </td>
                <td><input type="number" class="form-control form-control-sm" name="orden[<?= $cid ?>]" value="<?= $ordenV ?>"></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($categorias)): ?>
              <tr><td colspan="5" class="text-center text-muted">No hay categorías.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="text-end mt-3">
          <button class="btn btn-primary">Guardar categorías</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Listado de colecciones</h5>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr>
            <th>Nombre</th>
            <th>Categorías</th>
            <th>Estado</th>
            <th>Creada</th>
            <th style="width:240px;"></th>
          </tr></thead>
          <tbody>
          <?php foreach ($colecciones as $p): ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars($p['nombre']) ?></td>
              <td><span class="badge bg-info text-dark"><?= (int)$p['num_categorias'] ?></span></td>
              <td>
                <span class="badge <?= !empty($p['activa']) ? 'bg-success' : 'bg-secondary' ?>">
                  <?= !empty($p['activa']) ? 'Activa' : 'Inactiva' ?>
                </span>
              </td>
              <td class="text-muted small"><?= htmlspecialchars($p['fecha_creacion']) ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="catalogo.php?catset=<?= (int)$p['id_plantilla_cat'] ?>" target="_blank">Ver en catálogo</a>
                <?php if ($puedeEditar): ?>
                  <a class="btn btn-sm btn-warning" href="admin_plantillas_cat.php?editar=<?= (int)$p['id_plantilla_cat'] ?>">Editar</a>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id_plantilla_cat" value="<?= (int)$p['id_plantilla_cat'] ?>">
                    <input type="hidden" name="nuevo_activa" value="<?= !empty($p['activa']) ? 0 : 1 ?>">
                    <button class="btn btn-sm <?= !empty($p['activa']) ? 'btn-outline-secondary' : 'btn-success' ?>">
                      <?= !empty($p['activa']) ? 'Desactivar' : 'Activar' ?>
                    </button>
                  </form>
                  <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar colección?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_plantilla_cat" value="<?= (int)$p['id_plantilla_cat'] ?>">
                    <button class="btn btn-sm btn-danger">Eliminar</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($colecciones)): ?>
            <tr><td colspan="5" class="text-center text-muted">Crea tu primera colección arriba.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/partials/admin_sidebar_close.php'; ?>
</body>
</html>
