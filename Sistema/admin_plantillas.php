<?php
    if (session_status() === PHP_SESSION_NONE) session_start();

    $user = $_SESSION['user'] ?? null;
    if (($user['type'] ?? null) !== 'operador') { header('Location: login.php'); exit; }

    require_once __DIR__ . '/config/MySqlDb.php';
    require_once __DIR__ . '/controllers/PlantillaController.php';
    require_once __DIR__ . '/controllers/ProductController.php';

    // Permisos por cargo (quién puede crear/editar/eliminar)
    $cargo = $_SESSION['cargo'] ?? null;
    $puedeEditar = in_array($cargo, ['administrador','mantenedor','catalogo'], true);

    // Categorías para el select (intenta admin con 'activa'; si no existe, usa las activas)
    if (method_exists('ProductController', 'obtenerCategoriasAdmin')) {
    $categorias = ProductController::obtenerCategoriasAdmin($conn); // incluye 'activa'
    } else {
    $categorias = ProductController::obtenerCategorias($conn);
    }

    $flash = '';
    $editando = false;
    $tpl_editar = null;

    // Modo edición
    if (isset($_GET['editar'])) {
    $tpl_editar = PlantillaController::obtenerPlantillaById($conn, (int)$_GET['editar']);
    if ($tpl_editar) $editando = true;
    }

    // Acciones POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $puedeEditar) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id_tpl      = isset($_POST['id_plantilla']) && $_POST['id_plantilla'] !== '' ? (int)$_POST['id_plantilla'] : null;
        $np          = trim($_POST['nombre_plantilla'] ?? '');
        $idc         = isset($_POST['id_categoria']) && $_POST['id_categoria'] !== '' ? (int)$_POST['id_categoria'] : null;
        $ns          = trim($_POST['nombre_sugerido'] ?? '') ?: null;
        $ds          = trim($_POST['descripcion_sugerida'] ?? '') ?: null;
        $ppd         = ($_POST['precio_por_defecto'] === '' ? null : (float)$_POST['precio_por_defecto']);
        $img         = trim($_POST['url_imagen_por_defecto'] ?? '') ?: null;
        $n           = isset($_POST['es_nuevo_def']);
        $o           = isset($_POST['es_oferta_def']);
        $pop         = isset($_POST['es_popular_def']);
        $act         = isset($_POST['activa']);

        if ($np === '') {
        $flash = '❌ El nombre de la plantilla es obligatorio.';
        } else {
        if ($action === 'create') {
            PlantillaController::crearPlantilla($conn, $np, $idc, $ns, $ds, $ppd, $img, $n, $o, $pop, $act);
            $flash = '✅ Plantilla creada.';
        } else {
            PlantillaController::editarPlantilla($conn, $id_tpl, $np, $idc, $ns, $ds, $ppd, $img, $n, $o, $pop, $act);
            $flash = '✅ Plantilla actualizada.';
        }
        }
        header('Location: admin_plantillas.php?msg='.urlencode($flash));
        exit;
    }

    if ($action === 'toggle') {
        $id  = (int)($_POST['id_plantilla'] ?? 0);
        $val = (int)($_POST['nuevo_activa'] ?? 1);
        PlantillaController::setActiva($conn, $id, (bool)$val);
        $flash = $val ? '✅ Plantilla activada.' : '⛔ Plantilla desactivada.';
        header('Location: admin_plantillas.php?msg='.urlencode($flash));
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id_plantilla'] ?? 0);
        PlantillaController::eliminarPlantilla($conn, $id);
        $flash = '🗑️ Plantilla eliminada.';
        header('Location: admin_plantillas.php?msg='.urlencode($flash));
        exit;
    }
    }

    if (isset($_GET['msg'])) $flash = $_GET['msg'];
    $plantillas = PlantillaController::obtenerPlantillas($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Plantillas de Producto | Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include __DIR__ . '/partials/admin_sidebar_open.php'; ?>

<div class="container my-4">
  <h1 class="h4 mb-3">🧩 Plantillas de producto</h1>

  <?php if ($flash): ?>
    <div class="alert alert-info"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <?php if ($puedeEditar): ?>
  <form method="POST" class="card p-4 mb-4">
    <h5 class="mb-3"><?= $editando ? 'Editar plantilla' : 'Nueva plantilla' ?></h5>
    <input type="hidden" name="action" value="<?= $editando ? 'update' : 'create' ?>">
    <?php if ($editando): ?>
      <input type="hidden" name="id_plantilla" value="<?= (int)$tpl_editar['id_plantilla'] ?>">
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Nombre de la plantilla *</label>
        <input type="text" class="form-control" name="nombre_plantilla" required
               value="<?= $editando ? htmlspecialchars($tpl_editar['nombre_plantilla']) : '' ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Categoría por defecto</label>
        <select name="id_categoria" class="form-select">
          <option value="">(sin categoría)</option>
          <?php foreach ($categorias as $c):
            $idc = (int)$c['id_categoria'];
            $activa = isset($c['activa']) ? (int)$c['activa'] : 1;
            $sel = ($editando && (int)($tpl_editar['id_categoria'] ?? 0) === $idc) ? 'selected' : '';
            $label = htmlspecialchars($c['nombre_categoria']).($activa ? '' : ' (inactiva)');
          ?>
            <option value="<?= $idc ?>" <?= $sel ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Nombre sugerido</label>
        <input type="text" class="form-control" name="nombre_sugerido"
               value="<?= $editando ? htmlspecialchars($tpl_editar['nombre_sugerido'] ?? '') : '' ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Precio por defecto</label>
        <input type="number" step="0.01" min="0" class="form-control" name="precio_por_defecto"
               value="<?= $editando ? htmlspecialchars($tpl_editar['precio_por_defecto'] ?? '') : '' ?>">
      </div>

      <div class="col-12">
        <label class="form-label">URL imagen por defecto</label>
        <input type="url" class="form-control" name="url_imagen_por_defecto"
               value="<?= $editando ? htmlspecialchars($tpl_editar['url_imagen_por_defecto'] ?? '') : '' ?>">
      </div>

      <div class="col-12">
        <label class="form-label">Descripción sugerida</label>
        <textarea class="form-control" rows="3" name="descripcion_sugerida"><?= $editando ? htmlspecialchars($tpl_editar['descripcion_sugerida'] ?? '') : '' ?></textarea>
      </div>

      <div class="col-12">
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="es_nuevo_def" id="chkNuevo"
                 <?= $editando && !empty($tpl_editar['es_nuevo_def']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="chkNuevo">Marcar “Nuevo”</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="es_oferta_def" id="chkOferta"
                 <?= $editando && !empty($tpl_editar['es_oferta_def']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="chkOferta">Marcar “Oferta”</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="es_popular_def" id="chkPopular"
                 <?= $editando && !empty($tpl_editar['es_popular_def']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="chkPopular">Marcar “Popular”</label>
        </div>
      </div>

      <div class="col-12">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="activa" id="chkActiva"
                 <?= $editando ? (!empty($tpl_editar['activa']) ? 'checked' : '') : 'checked' ?>>
          <label class="form-check-label" for="chkActiva">Plantilla activa</label>
        </div>
      </div>

      <div class="col-12 text-end">
        <?php if ($editando): ?>
          <a href="admin_plantillas.php" class="btn btn-secondary me-2">Cancelar</a>
        <?php endif; ?>
        <button class="btn btn-primary"><?= $editando ? 'Actualizar' : 'Crear' ?></button>
      </div>
    </div>
  </form>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Categoría</th>
          <th>Valores por defecto</th>
          <th>Etiquetas</th>
          <th>Activa</th>
          <th style="width:220px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($plantillas as $pl): ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($pl['nombre_plantilla']) ?></td>
            <td>
              <?php if (!empty($pl['nombre_categoria'])): ?>
                <?= htmlspecialchars($pl['nombre_categoria']) ?>
                <?php if (isset($pl['categoria_activa']) && (int)$pl['categoria_activa'] === 0): ?>
                  <span class="badge bg-dark ms-1">Cat. inactiva</span>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="small">
              <?php if (!empty($pl['nombre_sugerido'])): ?>
                <div><strong>Nombre:</strong> <?= htmlspecialchars($pl['nombre_sugerido']) ?></div>
              <?php endif; ?>
              <?php if ($pl['precio_por_defecto'] !== null && $pl['precio_por_defecto'] !== ''): ?>
                <div><strong>Precio:</strong> $<?= number_format((float)$pl['precio_por_defecto'],0,',','.') ?></div>
              <?php endif; ?>
              <?php if (!empty($pl['url_imagen_por_defecto'])): ?>
                <div><strong>Imagen:</strong> <span class="text-truncate d-inline-block" style="max-width:240px;vertical-align:bottom;"><?= htmlspecialchars($pl['url_imagen_por_defecto']) ?></span></div>
              <?php endif; ?>
              <?php if (!empty($pl['descripcion_sugerida'])): ?>
                <div class="text-truncate" style="max-width:260px;"><strong>Desc:</strong> <?= htmlspecialchars($pl['descripcion_sugerida']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($pl['es_nuevo_def'])):  ?><span class="badge bg-primary me-1">Nuevo</span><?php endif; ?>
              <?php if (!empty($pl['es_oferta_def'])): ?><span class="badge bg-danger  me-1">Oferta</span><?php endif; ?>
              <?php if (!empty($pl['es_popular_def'])):?><span class="badge bg-success me-1">Popular</span><?php endif; ?>
            </td>
            <td>
              <span class="badge <?= !empty($pl['activa']) ? 'bg-success' : 'bg-secondary' ?>">
                <?= !empty($pl['activa']) ? 'Activa' : 'Inactiva' ?>
              </span>
            </td>
            <td class="text-end">
              <!-- Usar para crear producto (pre-rellenar) -->
              <a class="btn btn-sm btn-outline-primary" href="admin_productos.php?tpl=<?= (int)$pl['id_plantilla'] ?>">Usar</a>

              <?php if ($puedeEditar): ?>
                <a class="btn btn-sm btn-warning" href="admin_plantillas.php?editar=<?= (int)$pl['id_plantilla'] ?>">Editar</a>

                <form method="POST" class="d-inline">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id_plantilla" value="<?= (int)$pl['id_plantilla'] ?>">
                  <input type="hidden" name="nuevo_activa" value="<?= !empty($pl['activa']) ? 0 : 1 ?>">
                  <button class="btn btn-sm <?= !empty($pl['activa']) ? 'btn-outline-secondary' : 'btn-success' ?>">
                    <?= !empty($pl['activa']) ? 'Desactivar' : 'Activar' ?>
                  </button>
                </form>

                <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar plantilla?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id_plantilla" value="<?= (int)$pl['id_plantilla'] ?>">
                  <button class="btn btn-sm btn-danger">Eliminar</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($plantillas)): ?>
          <tr><td colspan="6" class="text-center text-muted">No hay plantillas aún.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/partials/admin_sidebar_close.php'; ?>
</body>
</html>
