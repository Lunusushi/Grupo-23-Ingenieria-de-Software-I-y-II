<?php
  // direcciones.php — CRUD direcciones + favorita (solo clientes)
  if (session_status() === PHP_SESSION_NONE) session_start();

  // Guard: solo clientes
  $userType = $_SESSION['user']['type'] ?? ($_SESSION['user_type'] ?? null);
  if (empty($_SESSION['user']) || $userType !== 'cliente') {
    header('Location: index.php'); exit;
  }

  require_once __DIR__ . '/config/MySqlDb.php';
  require_once __DIR__ . '/partials/navbar.php';

  // Tu modelo: CLIENTE(id_cliente) referencia a USUARIO(id_usuario).
  // En tu app, usas el id_usuario en $_SESSION['user']['id'].
  // Para asociar direcciones, necesitamos el id_cliente correspondiente.
  $usuarioId = (int)$_SESSION['user']['id'];

  // Buscar id_cliente a partir de id_usuario
  $stmt = $conn->prepare("SELECT id_cliente FROM CLIENTE WHERE id_usuario = ?");
  $stmt->execute([$usuarioId]);
  $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$cliente) { http_response_code(403); die("No se encontró el cliente asociado a este usuario."); }
  $id_cliente = (int)$cliente['id_cliente'];

  $mensaje = "";
  $error   = "";

  // Sanitizador simple
  function f($k, $def='') { return trim($_POST[$k] ?? $def); }

  // ACCIONES
  try {
    // Crear
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
      $alias      = f('alias');
      $calle      = f('calle');
      $comuna     = f('comuna');
      $ciudad     = f('ciudad');
      $region     = f('region');
      $referencia = f('referencia');
      $tipo       = $_POST['tipo'] ?? 'otra';
      $fav        = isset($_POST['es_favorita']) ? 1 : 0;

      if ($calle === '') throw new RuntimeException("La calle es obligatoria.");

      // Si viene como favorita, desmarca el resto del cliente
      if ($fav) {
        $conn->prepare("UPDATE DIRECCION SET es_favorita=0 WHERE id_cliente=?")->execute([$id_cliente]);
      }

      $ins = $conn->prepare("
        INSERT INTO DIRECCION (id_cliente, alias, calle, comuna, ciudad, region, referencia, es_favorita, tipo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $ins->execute([$id_cliente, $alias, $calle, $comuna, $ciudad, $region, $referencia, $fav, $tipo]);
      $mensaje = "✅ Dirección creada.";
    }

    // Editar
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
      $id_direccion = (int)($_POST['id_direccion'] ?? 0);

      // Verificar que la dirección sea del cliente
      $own = $conn->prepare("SELECT id_direccion FROM DIRECCION WHERE id_direccion=? AND id_cliente=?");
      $own->execute([$id_direccion, $id_cliente]);
      if (!$own->fetch()) throw new RuntimeException("Dirección no encontrada.");

      $alias      = f('alias');
      $calle      = f('calle');
      $comuna     = f('comuna');
      $ciudad     = f('ciudad');
      $region     = f('region');
      $referencia = f('referencia');
      $tipo       = $_POST['tipo'] ?? 'otra';
      $fav        = isset($_POST['es_favorita']) ? 1 : 0;

      if ($calle === '') throw new RuntimeException("La calle es obligatoria.");

      if ($fav) {
        $conn->prepare("UPDATE DIRECCION SET es_favorita=0 WHERE id_cliente=?")->execute([$id_cliente]);
      }

      $up = $conn->prepare("
        UPDATE DIRECCION
        SET alias=?, calle=?, comuna=?, ciudad=?, region=?, referencia=?, es_favorita=?, tipo=?
        WHERE id_direccion=? AND id_cliente=?
      ");
      $up->execute([$alias, $calle, $comuna, $ciudad, $region, $referencia, $fav, $tipo, $id_direccion, $id_cliente]);
      $mensaje = "✅ Dirección actualizada.";
    }

    // Eliminar
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
      $id_direccion = (int)($_POST['id_direccion'] ?? 0);

      $own = $conn->prepare("SELECT id_direccion FROM DIRECCION WHERE id_direccion=? AND id_cliente=?");
      $own->execute([$id_direccion, $id_cliente]);
      if (!$own->fetch()) throw new RuntimeException("Dirección no encontrada.");

      $del = $conn->prepare("DELETE FROM DIRECCION WHERE id_direccion=? AND id_cliente=?");
      $del->execute([$id_direccion, $id_cliente]);
      $mensaje = "🗑️ Dirección eliminada.";
    }

    // Marcar favorita (enlace GET o botón POST)
    if (($_GET['fav'] ?? '') && ctype_digit($_GET['fav'])) {
      $id_direccion = (int)$_GET['fav'];

      $own = $conn->prepare("SELECT id_direccion FROM DIRECCION WHERE id_direccion=? AND id_cliente=?");
      $own->execute([$id_direccion, $id_cliente]);
      if ($own->fetch()) {
        $conn->prepare("UPDATE DIRECCION SET es_favorita=0 WHERE id_cliente=?")->execute([$id_cliente]);
        $conn->prepare("UPDATE DIRECCION SET es_favorita=1 WHERE id_direccion=? AND id_cliente=?")->execute([$id_direccion, $id_cliente]);
        $mensaje = "⭐ Dirección marcada como favorita.";
        header("Location: direcciones.php"); exit;
      }
    }

  } catch (Throwable $e) {
    $error = "❌ " . $e->getMessage();
  }

  // Listar direcciones del cliente
  $stmt = $conn->prepare("SELECT * FROM DIRECCION WHERE id_cliente=? ORDER BY es_favorita DESC, fecha_creacion DESC, id_direccion DESC");
  $stmt->execute([$id_cliente]);
  $dirs = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Helper UI
  function selected($a, $b) { return $a===$b ? 'selected' : ''; }
  function checked($v) { return $v ? 'checked' : ''; }
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mis Direcciones | Los Cobres</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container my-4">
  <h1 class="h4 mb-3">📍 Mis direcciones</h1>

  <?php if ($mensaje): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error)   ?></div><?php endif; ?>

  <!-- Crear nueva -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title mb-3">Agregar dirección</h5>
      <form method="POST" class="row g-2">
        <input type="hidden" name="action" value="create">
        <div class="col-12 col-md-4">
          <label class="form-label">Alias</label>
          <input name="alias" class="form-control" placeholder="Casa / Trabajo / ...">
        </div>
        <div class="col-12 col-md-8">
          <label class="form-label">Calle *</label>
          <input name="calle" class="form-control" required>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label">Comuna</label>
          <input name="comuna" class="form-control">
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label">Ciudad</label>
          <input name="ciudad" class="form-control">
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label">Región</label>
          <input name="region" class="form-control">
        </div>
        <div class="col-12">
          <label class="form-label">Referencia</label>
          <input name="referencia" class="form-control" placeholder="Piso, depto, entrecalles…">
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label">Tipo</label>
          <select name="tipo" class="form-select">
            <option value="casa">Casa</option>
            <option value="trabajo">Trabajo</option>
            <option value="otra" selected>Otra</option>
          </select>
        </div>
        <div class="col-12 col-md-4 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="es_favorita" id="fav-new">
            <label class="form-check-label" for="fav-new">Marcar como favorita</label>
          </div>
        </div>
        <div class="col-12 col-md-4 d-flex align-items-end justify-content-end">
          <button class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Listado -->
  <?php if (empty($dirs)): ?>
    <div class="alert alert-info">No tienes direcciones guardadas.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($dirs as $d): ?>
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                  <h5 class="card-title mb-1">
                    <?= htmlspecialchars($d['alias'] ?: 'Sin alias') ?>
                    <?php if ($d['es_favorita']): ?>
                      <span class="badge bg-warning text-dark">Favorita</span>
                    <?php endif; ?>
                    <span class="badge bg-secondary ms-1"><?= htmlspecialchars($d['tipo']) ?></span>
                  </h5>
                  <div class="text-muted">
                    <?= htmlspecialchars($d['calle']) ?>
                    <?php if ($d['comuna']): ?>, <?= htmlspecialchars($d['comuna']) ?><?php endif; ?>
                    <?php if ($d['ciudad']): ?>, <?= htmlspecialchars($d['ciudad']) ?><?php endif; ?>
                    <?php if ($d['region']): ?>, <?= htmlspecialchars($d['region']) ?><?php endif; ?>
                    <?php if ($d['referencia']): ?><br><small>Ref: <?= htmlspecialchars($d['referencia']) ?></small><?php endif; ?>
                  </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                  <?php if (!$d['es_favorita']): ?>
                    <a class="btn btn-outline-warning btn-sm" href="?fav=<?= (int)$d['id_direccion'] ?>">⭐ Favorita</a>
                  <?php endif; ?>

                  <!-- Botón editar: despliega un collapse con el form -->
                  <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse"
                          data-bs-target="#edit-<?= (int)$d['id_direccion'] ?>">
                    Editar
                  </button>

                  <form method="POST" onsubmit="return confirm('¿Eliminar esta dirección?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_direccion" value="<?= (int)$d['id_direccion'] ?>">
                    <button class="btn btn-danger btn-sm">Eliminar</button>
                  </form>
                </div>
              </div>

              <!-- Form editar -->
              <div class="collapse mt-3" id="edit-<?= (int)$d['id_direccion'] ?>">
                <form method="POST" class="row g-2">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="id_direccion" value="<?= (int)$d['id_direccion'] ?>">

                  <div class="col-12 col-md-4">
                    <label class="form-label">Alias</label>
                    <input name="alias" class="form-control" value="<?= htmlspecialchars($d['alias'] ?? '') ?>">
                  </div>
                  <div class="col-12 col-md-8">
                    <label class="form-label">Calle *</label>
                    <input name="calle" class="form-control" value="<?= htmlspecialchars($d['calle'] ?? '') ?>" required>
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">Comuna</label>
                    <input name="comuna" class="form-control" value="<?= htmlspecialchars($d['comuna'] ?? '') ?>">
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">Ciudad</label>
                    <input name="ciudad" class="form-control" value="<?= htmlspecialchars($d['ciudad'] ?? '') ?>">
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">Región</label>
                    <input name="region" class="form-control" value="<?= htmlspecialchars($d['region'] ?? '') ?>">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Referencia</label>
                    <input name="referencia" class="form-control" value="<?= htmlspecialchars($d['referencia'] ?? '') ?>">
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" class="form-select">
                      <option value="casa"    <?= selected($d['tipo'],'casa') ?>>Casa</option>
                      <option value="trabajo" <?= selected($d['tipo'],'trabajo') ?>>Trabajo</option>
                      <option value="otra"    <?= selected($d['tipo'],'otra') ?>>Otra</option>
                    </select>
                  </div>
                  <div class="col-12 col-md-4 d-flex align-items-end">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="es_favorita" id="fav-<?= (int)$d['id_direccion'] ?>" <?= checked((int)$d['es_favorita']) ?>>
                      <label class="form-check-label" for="fav-<?= (int)$d['id_direccion'] ?>">Marcar como favorita</label>
                    </div>
                  </div>
                  <div class="col-12 col-md-4 d-flex align-items-end justify-content-end">
                    <button class="btn btn-primary">Guardar cambios</button>
                  </div>
                </form>
              </div>

            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>
</body>
</html>