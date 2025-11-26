<?php
  if (session_status() === PHP_SESSION_NONE) session_start();

  require_once __DIR__ . '/config/MySqlDb.php';
  require_once __DIR__ . '/controllers/ProductController.php';

  // Guard de rol
  $user = $_SESSION['user'] ?? null;
  $cargo = $_SESSION['cargo'] ?? null;
  if (($user['type'] ?? null) !== 'operador' || !in_array($cargo, ['administrador','mantenedor','catalogo'], true)) {
    http_response_code(403);
    die('<div class="container my-5"><div class="alert alert-danger">No autorizado.</div></div>');
  }

  $flashOk = '';
  $flashErr = '';
  $editando = false;
  $catEdit  = null;

  // Para selects de padre
  $catsAll = PlantillaCategoriaController::obtenerCategoriasAdmin($conn);

  // Modo edición
  if (isset($_GET['editar']) && ctype_digit((string)$_GET['editar'])) {
    $idc = (int)$_GET['editar'];
    foreach ($catsAll as $c) {
      if ((int)$c['id_categoria'] === $idc) {
        $catEdit  = $c;
        $editando = true;
        break;
      }
    }
  }

  // POST actions
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
      $nombre = trim($_POST['nombre_categoria'] ?? '');
      $desc   = trim($_POST['descripcion_categoria'] ?? '');
      $padre  = $_POST['id_padre'] !== '' ? (int)$_POST['id_padre'] : null;
      $activa = isset($_POST['activa']);

      if ($nombre === '') {
        $flashErr = 'El nombre es obligatorio.';
      } else {
        PlantillaCategoriaController::crearCategoria($conn, $nombre, $desc ?: null, $padre, $activa);
        $flashOk = '✅ Categoría creada.';
      }
    }

    if ($action === 'update' && isset($_POST['id_categoria']) && ctype_digit((string)$_POST['id_categoria'])) {
      $idc    = (int)$_POST['id_categoria'];
      $nombre = trim($_POST['nombre_categoria'] ?? '');
      $desc   = trim($_POST['descripcion_categoria'] ?? '');
      $padre  = $_POST['id_padre'] !== '' ? (int)$_POST['id_padre'] : null;

      if ($nombre === '') {
        $flashErr = 'El nombre es obligatorio.';
      } else {
        // Evitar poner la categoría como hija de sí misma
        if ($padre === $idc) $padre = null;
        PlantillaCategoriaController::actualizarCategoria($conn, $idc, $nombre, $desc ?: null, $padre);
        $flashOk = '✅ Categoría actualizada.';
      }
    }

    if ($action === 'toggle' && isset($_POST['id_categoria'], $_POST['nuevo_activa'])) {
      $idc   = (int)$_POST['id_categoria'];
      $nuevo = ((int)$_POST['nuevo_activa'] === 1);
      PlantillaCategoriaController::setCategoriaActiva($conn, $idc, $nuevo);
      $flashOk = $nuevo ? '✅ Categoría activada.' : '⛔ Categoría desactivada.';
    }

    if ($action === 'delete' && isset($_POST['id_categoria'])) {
      $idc = (int)$_POST['id_categoria'];
      $res = PlantillaCategoriaController::eliminarCategoria($conn, $idc);
      if ($res === true) {
        $flashOk = '🗑️ Categoría eliminada.';
      } else {
        $flashErr = $res; // mensaje de restricción (hijos o productos)
      }
    }

    header('Location: admin_categorias.php?ok='.urlencode($flashOk).'&err='.urlencode($flashErr));
    exit;
  }

  // Mensajes
  if (isset($_GET['ok']) && $_GET['ok'] !== '')  $flashOk  = $_GET['ok'];
  if (isset($_GET['err']) && $_GET['err'] !== '') $flashErr = $_GET['err'];
  // Refrescar datos (por si hubo cambios)
  $catsAll = PlantillaCategoriaController::obtenerCategoriasAdmin($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Categorías | Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">

<?php include __DIR__ . '/partials/admin_sidebar_open.php'; ?>

<div class="container my-4">
  <h1 class="h4 mb-3">🗂 Administrar categorías</h1>

  <?php if ($flashOk): ?><div class="alert alert-success"><?= htmlspecialchars($flashOk) ?></div><?php endif; ?>
  <?php if ($flashErr): ?><div class="alert alert-warning"><?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

  <!-- Form crear/editar -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title"><?= $editando ? 'Editar categoría' : 'Nueva categoría' ?></h5>
      <form method="POST" class="row g-3">
        <input type="hidden" name="action" value="<?= $editando ? 'update' : 'create' ?>">
        <?php if ($editando): ?>
          <input type="hidden" name="id_categoria" value="<?= (int)$catEdit['id_categoria'] ?>">
        <?php endif; ?>

        <div class="col-12 col-md-6">
          <label class="form-label">Nombre</label>
          <input type="text" name="nombre_categoria" class="form-control"
                 value="<?= $editando ? htmlspecialchars($catEdit['nombre_categoria']) : '' ?>" required>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label">Categoría padre (opcional)</label>
          <select name="id_padre" class="form-select">
            <option value="">— Sin padre —</option>
            <?php foreach ($catsAll as $c): 
              $idc = (int)$c['id_categoria'];
              // Evitar listarse como propio padre en edición
              if ($editando && $idc === (int)$catEdit['id_categoria']) continue;
            ?>
              <option value="<?= $idc ?>"
                <?= $editando && $catEdit['id_padre'] && (int)$catEdit['id_padre'] === $idc ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nombre_categoria']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Descripción</label>
          <textarea name="descripcion_categoria" class="form-control" rows="2"><?= $editando ? htmlspecialchars($catEdit['descripcion_categoria'] ?? '') : '' ?></textarea>
        </div>

        <?php if (!$editando): ?>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="activa" id="activa" checked>
              <label class="form-check-label" for="activa">Activa</label>
            </div>
          </div>
        <?php endif; ?>

        <div class="col-12 text-end">
          <?php if ($editando): ?>
            <a class="btn btn-secondary me-2" href="admin_categorias.php">Cancelar</a>
          <?php endif; ?>
          <button class="btn btn-primary"><?= $editando ? 'Guardar cambios' : 'Crear categoría' ?></button>
        </div>
      </form>
    </div>
  </div>

  <!-- Listado -->
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Padre</th>
          <th>Productos</th>
          <th>Estado</th>
          <th class="text-end" style="width:260px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($catsAll as $c): 
          $idc = (int)$c['id_categoria'];
          $act = (int)$c['activa'] === 1;
          $num = (int)($c['num_productos'] ?? 0);
        ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($c['nombre_categoria']) ?></td>
            <td class="text-muted"><?= htmlspecialchars($c['nombre_padre'] ?? '—') ?></td>
            <td><?= $num ?></td>
            <td>
              <?php if ($act): ?>
                <span class="badge bg-success">Activa</span>
              <?php else: ?>
                <span class="badge bg-secondary">Inactiva</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <a href="admin_categorias.php?editar=<?= $idc ?>" class="btn btn-sm btn-warning">Editar</a>

              <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id_categoria" value="<?= $idc ?>">
                <input type="hidden" name="nuevo_activa" value="<?= $act ? 0 : 1 ?>">
                <button class="btn btn-sm <?= $act ? 'btn-outline-secondary' : 'btn-success' ?>">
                  <?= $act ? 'Desactivar' : 'Activar' ?>
                </button>
              </form>

              <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta categoría?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id_categoria" value="<?= $idc ?>">
                <button class="btn btn-sm btn-danger"
                        <?= ((int)$c['num_productos'] > 0) ? 'disabled title="Tiene productos asignados"' : '' ?>>
                  Eliminar
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($catsAll)): ?>
          <tr><td colspan="5" class="text-center text-muted">No hay categorías aún.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/partials/admin_sidebar_close.php'; ?>
</body>
</html>
