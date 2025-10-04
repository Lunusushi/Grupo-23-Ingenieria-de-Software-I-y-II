<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$user = $_SESSION['user'] ?? null;
if (($user['type'] ?? null) !== 'operador') { header('Location: login.php'); exit; }
$cargo = $_SESSION['cargo'] ?? null;
$puedeEditar = in_array($cargo, ['administrador','mantenedor','catalogo'], true);

require_once __DIR__ . '/config/MySqlDb.php';
require_once __DIR__ . '/controllers/ProductController.php';

$flash = '';
$editando = false;
$tpl = null;

/* Modo edición */
if (isset($_GET['editar']) && ctype_digit((string)$_GET['editar'])) {
  $tpl = PlantillaController::obtener($conn, (int)$_GET['editar']);
  if ($tpl) $editando = true;
}

/* Acciones (POST) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $puedeEditar) {
  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $nombre = trim($_POST['nombre'] ?? '');
    $activa = isset($_POST['activa']);
    if ($nombre === '') {
      $flash = '❌ El nombre es obligatorio.';
    } else {
      try {
        PlantillaController::crear($conn, $nombre, $activa);
        $flash = '✅ Plantilla creada.';
      } catch (Throwable $ex) {
        // Manejo de duplicados (1062)
        $msg = '❌ Error al crear la plantilla.';
        if ($ex instanceof PDOException && isset($ex->errorInfo[1]) && (int)$ex->errorInfo[1] === 1062) {
          $msg = '⚠️ Ya existe una plantilla con ese nombre.';
        } elseif ($ex->getMessage()) {
          $msg = $ex->getMessage();
        }
        $flash = $msg;
      }
    }
    header('Location: admin_plantillas_productos.php?msg='.urlencode($flash));
    exit;
  }

  if ($action === 'rename') {
    $id = (int)($_POST['id_plantilla'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    if ($id && $nombre !== '') {
      try {
        PlantillaController::renombrar($conn, $id, $nombre);
        $flash = '✏️ Nombre actualizado.';
      } catch (Throwable $ex) {
        $msg = '❌ Error al renombrar.';
        if ($ex instanceof PDOException && isset($ex->errorInfo[1]) && (int)$ex->errorInfo[1] === 1062) {
          $msg = '⚠️ Ya existe una plantilla con ese nombre.';
        } elseif ($ex->getMessage()) {
          $msg = $ex->getMessage();
        }
        $flash = $msg;
      }
    } else {
      $flash = '❌ Falta el nuevo nombre.';
    }
    header('Location: admin_plantillas_productos.php?editar='.$id.'&msg='.urlencode($flash));
    exit;
  }

  if ($action === 'toggle') {
    $id = (int)($_POST['id_plantilla'] ?? 0);
    $val = (int)($_POST['nuevo_activa'] ?? 1);
    try {
      PlantillaController::setActiva($conn, $id, (bool)$val);
      $flash = $val ? '✅ Plantilla activada.' : '⛔ Plantilla desactivada.';
    } catch (Throwable $ex) {
      $flash = '❌ No se pudo cambiar el estado.';
    }
    header('Location: admin_plantillas_productos.php?editar='.$id.'&msg='.urlencode($flash));
    exit;
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id_plantilla'] ?? 0);
    try {
      PlantillaController::eliminar($conn, $id);
      $flash = '🗑️ Plantilla eliminada.';
    } catch (Throwable $ex) {
      $flash = '❌ No se pudo eliminar la plantilla.';
    }
    header('Location: admin_plantillas_productos.php?msg='.urlencode($flash));
    exit;
  }

  /* Ítems */
  if ($action === 'add_item') {
    $id    = (int)($_POST['id_plantilla'] ?? 0);
    $prod  = (int)($_POST['id_producto'] ?? 0);
    $orden = (int)($_POST['orden'] ?? 0);
    if ($id && $prod) {
      try {
        PlantillaController::agregarProducto($conn, $id, $prod, $orden); // hace upsert por PK compuesto
        $flash = '✅ Producto añadido.';
      } catch (Throwable $ex) {
        $flash = '❌ No se pudo añadir el producto.';
      }
    }
    header('Location: admin_plantillas_productos.php?editar='.$id.'&msg='.urlencode($flash));
    exit;
  }

  if ($action === 'rm_item') {
    $id   = (int)($_POST['id_plantilla'] ?? 0);
    $prod = (int)($_POST['id_producto'] ?? 0);
    try {
      PlantillaController::quitarProducto($conn, $id, $prod);
      $flash = '🗑️ Producto quitado.';
    } catch (Throwable $ex) {
      $flash = '❌ No se pudo quitar el producto.';
    }
    header('Location: admin_plantillas_productos.php?editar='.$id.'&msg='.urlencode($flash));
    exit;
  }

  if ($action === 'set_order') {
    $id    = (int)($_POST['id_plantilla'] ?? 0);
    $prod  = (int)($_POST['id_producto'] ?? 0);
    $orden = (int)($_POST['orden'] ?? 0);
    try {
      PlantillaController::setOrden($conn, $id, $prod, $orden);
      $flash = '🔢 Orden actualizado.';
    } catch (Throwable $ex) {
      $flash = '❌ No se pudo actualizar el orden.';
    }
    header('Location: admin_plantillas_productos.php?editar='.$id.'&msg='.urlencode($flash));
    exit;
  }

  if ($action === 'bulk_activate' || $action === 'bulk_deactivate') {
    $id      = (int)($_POST['id_plantilla'] ?? 0);
    $activar = ($action === 'bulk_activate');
    try {
      $count = PlantillaController::activarPorPlantilla($conn, $id, $activar);
      $flash = ($activar ? '✅ Activados ' : '⛔ Desactivados ').(int)$count.' producto(s).';
    } catch (Throwable $ex) {
      $flash = '❌ No se pudo completar la acción masiva.';
    }
    header('Location: admin_plantillas_productos.php?editar='.$id.'&msg='.urlencode($flash));
    exit;
  }
}

if (isset($_GET['msg'])) $flash = $_GET['msg'];
$plantillas = PlantillaController::listar($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Plantillas (colecciones de productos) | Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include __DIR__ . '/partials/admin_sidebar_open.php'; ?>

<div class="container my-4">
  <h1 class="h4 mb-3">🧩 Plantillas (colecciones de productos)</h1>

  <?php if ($flash): ?>
    <div class="alert alert-info"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <?php if ($puedeEditar): ?>
  <!-- Crear nueva plantilla -->
  <form method="POST" class="card p-3 mb-4">
    <input type="hidden" name="action" value="create">
    <div class="row g-2 align-items-end">
      <div class="col-md-6">
        <label class="form-label">Nombre *</label>
        <input type="text" name="nombre" class="form-control" required>
      </div>
      <div class="col-md-3">
        <div class="form-check mt-4">
          <input class="form-check-input" type="checkbox" name="activa" id="chkAct" checked>
          <label class="form-check-label" for="chkAct">Activa</label>
        </div>
      </div>
      <div class="col-md-3 text-end">
        <button class="btn btn-primary w-100">Crear</button>
      </div>
    </div>
  </form>
  <?php endif; ?>

  <div class="row">
    <div class="<?= $editando ? 'col-lg-6' : 'col-12' ?>">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Listado</h5>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Activa</th>
                  <th>Creada</th>
                  <th style="width:220px;"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($plantillas as $p): ?>
                <tr>
                  <td class="fw-semibold"><?= htmlspecialchars($p['nombre']) ?></td>
                  <td>
                    <span class="badge <?= !empty($p['activa']) ? 'bg-success' : 'bg-secondary' ?>">
                      <?= !empty($p['activa']) ? 'Activa' : 'Inactiva' ?>
                    </span>
                  </td>
                  <td class="text-muted small"><?= htmlspecialchars($p['fecha_creacion']) ?></td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="admin_plantillas_productos.php?editar=<?= (int)$p['id_plantilla'] ?>">Abrir</a>
                    <?php if ($puedeEditar): ?>
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id_plantilla" value="<?= (int)$p['id_plantilla'] ?>">
                        <input type="hidden" name="nuevo_activa" value="<?= !empty($p['activa']) ? 0 : 1 ?>">
                        <button class="btn btn-sm <?= !empty($p['activa']) ? 'btn-outline-secondary' : 'btn-success' ?>">
                          <?= !empty($p['activa']) ? 'Desactivar' : 'Activar' ?>
                        </button>
                      </form>
                      <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar plantilla?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id_plantilla" value="<?= (int)$p['id_plantilla'] ?>">
                        <button class="btn btn-sm btn-danger">Eliminar</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($plantillas)): ?>
                  <tr><td colspan="4" class="text-center text-muted">No hay plantillas aún.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <?php if ($editando): ?>
    <div class="col-lg-6 mt-4 mt-lg-0">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">Editar: <?= htmlspecialchars($tpl['nombre']) ?></h5>
            <?php if ($puedeEditar): ?>
            <form method="POST" class="d-flex gap-2">
              <input type="hidden" name="action" value="rename">
              <input type="hidden" name="id_plantilla" value="<?= (int)$tpl['id_plantilla'] ?>">
              <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nuevo nombre">
              <button class="btn btn-sm btn-outline-primary">Renombrar</button>
            </form>
            <?php endif; ?>
          </div>

          <hr>

          <?php if ($puedeEditar): ?>
          <!-- Añadir producto por búsqueda -->
          <form method="POST" class="row g-2 position-relative mb-3">
            <input type="hidden" name="action" value="add_item">
            <input type="hidden" name="id_plantilla" value="<?= (int)$tpl['id_plantilla'] ?>">
            <div class="col-12">
              <label class="form-label">Agregar producto</label>
              <input type="text" id="tpl-buscar" class="form-control" placeholder="Escribe parte del nombre…" autocomplete="off">
              <div id="tpl-suggestions" class="list-group position-absolute w-100" style="z-index:1000;display:none;"></div>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label">ID producto</label>
              <input type="number" name="id_producto" id="tpl-id-producto" class="form-control" required>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label">Orden</label>
              <input type="number" name="orden" class="form-control" value="0">
            </div>
            <div class="col-12 col-md-4 d-flex align-items-end">
              <button class="btn btn-primary w-100">Agregar</button>
            </div>
          </form>

          <!-- Acciones masivas -->
          <div class="d-flex gap-2 mb-3">
            <form method="POST" class="d-inline">
              <input type="hidden" name="action" value="bulk_activate">
              <input type="hidden" name="id_plantilla" value="<?= (int)$tpl['id_plantilla'] ?>">
              <button class="btn btn-success">Activar todos</button>
            </form>
            <form method="POST" class="d-inline">
              <input type="hidden" name="action" value="bulk_deactivate">
              <input type="hidden" name="id_plantilla" value="<?= (int)$tpl['id_plantilla'] ?>">
              <button class="btn btn-outline-secondary">Desactivar todos</button>
            </form>
          </div>
          <?php endif; ?>

          <?php $items = PlantillaController::listarProductos($conn, (int)$tpl['id_plantilla']); ?>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th style="width:80px;">Img</th>
                  <th>Producto</th>
                  <th>Precio / Stock</th>
                  <th>Estado</th>
                  <th style="width:160px;">Orden</th>
                  <th style="width:120px;"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $it): ?>
                  <tr>
                    <td><img src="<?= htmlspecialchars($it['url_imagen_principal']) ?>" style="width:72px;height:48px;object-fit:cover" class="rounded"></td>
                    <td class="fw-semibold">#<?= (int)$it['id_producto'] ?> — <?= htmlspecialchars($it['nombre_producto']) ?></td>
                    <td>$<?= number_format((float)$it['precio_unitario'],0,',','.') ?> · Stock: <?= (int)$it['stock_actual'] ?></td>
                    <td>
                      <span class="badge <?= (int)$it['activo']===1 ? 'bg-success':'bg-secondary' ?>">
                        <?= (int)$it['activo']===1 ? 'Activo':'Inactivo' ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($puedeEditar): ?>
                      <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="action" value="set_order">
                        <input type="hidden" name="id_plantilla" value="<?= (int)$tpl['id_plantilla'] ?>">
                        <input type="hidden" name="id_producto" value="<?= (int)$it['id_producto'] ?>">
                        <input type="number" name="orden" class="form-control form-control-sm" value="<?= (int)$it['orden'] ?>">
                        <button class="btn btn-sm btn-outline-primary">Guardar</button>
                      </form>
                      <?php else: ?>
                        <?= (int)$it['orden'] ?>
                      <?php endif; ?>
                    </td>
                    <td class="text-end">
                      <?php if ($puedeEditar): ?>
                      <form method="POST" class="d-inline" onsubmit="return confirm('¿Quitar este producto de la plantilla?');">
                        <input type="hidden" name="action" value="rm_item">
                        <input type="hidden" name="id_plantilla" value="<?= (int)$tpl['id_plantilla'] ?>">
                        <input type="hidden" name="id_producto" value="<?= (int)$it['id_producto'] ?>">
                        <button class="btn btn-sm btn-danger">Quitar</button>
                      </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                  <tr><td colspan="6" class="text-center text-muted">Sin productos en esta plantilla.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <?php if ($puedeEditar): ?>
          <small class="text-muted">Tip: usa “Activar/Desactivar todos” para cambiar el estado de todos los productos incluidos.</small>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/partials/admin_sidebar_close.php'; ?>

<script>
/* Autocomplete (ADMIN): busca TODOS (activos e inactivos) */
(function(){
  const input = document.getElementById('tpl-buscar');
  const box   = document.getElementById('tpl-suggestions');
  const idInp = document.getElementById('tpl-id-producto');
  if (!input || !box || !idInp) return;

  input.addEventListener('input', async () => {
    const q = input.value.trim();
    if (!q) { box.style.display='none'; box.innerHTML=''; return; }
    try {
      const res = await fetch(`controllers/productController.php?action=buscar_admin&q=${encodeURIComponent(q)}`);
      const items = await res.json();
      box.innerHTML = '';
      items.forEach(p => {
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action';
        a.textContent = `#${p.id_producto} — ${p.nombre_producto} ${+p.activo===1?'(activo)':'(inactivo)'}`;
        a.onclick = (e) => {
          e.preventDefault();
          idInp.value = p.id_producto;
          box.style.display='none'; box.innerHTML='';
        };
        box.appendChild(a);
      });
      box.style.display = items.length ? 'block' : 'none';
    } catch(e) {
      box.style.display='none'; box.innerHTML='';
    }
  });

  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !box.contains(e.target)) {
      box.style.display='none'; box.innerHTML='';
    }
  });
})();
</script>
</body>
</html>
