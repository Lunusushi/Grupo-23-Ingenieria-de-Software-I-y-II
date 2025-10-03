<?php    
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
  if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'operador') { header('Location: login.php'); exit; }
  require_once 'config/MySqlDb.php';
  require_once 'controllers/ProductController.php';

  $categorias = ProductController::obtenerCategorias($conn);

  $mensaje_error = "";
  $mensaje_exito = "";

  $editando = false;
  $producto_editar = null;

  // (Opcional) restringe por cargo quién puede modificar visibilidad
  $cargo = $_SESSION['cargo'] ?? null;
  $puedeModificar = in_array($cargo, ['administrador','mantenedor','catalogo'], true);

  // Modo edición
  if (isset($_GET["editar"])) {
      $producto_editar = ProductController::obtenerProductoById($conn, $_GET["editar"]);
      if ($producto_editar) {
          $editando = true;
      }
  }

  // Agregar o editar producto
  if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["nombre_producto"])) {
      if (isset($_POST["id_producto"]) && !empty($_POST["id_producto"])) {
          // Editar
          ProductController::editarProducto(
              $conn,
              $_POST["id_producto"],
              $_POST["nombre_producto"],
              $_POST["descripcion"],
              $_POST["precio_unitario"],
              $_POST["stock_actual"],
              $_POST["url_imagen_principal"],
              $_POST["id_categoria"]
          );
          $mensaje_exito = "✅ Producto actualizado correctamente.";
      } else {
          // Agregar
          ProductController::agregarProducto(
              $conn,
              $_POST["nombre_producto"],
              $_POST["descripcion"],
              $_POST["precio_unitario"],
              $_POST["stock_actual"],
              $_POST["url_imagen_principal"],
              $_POST["id_categoria"]
          );
          $mensaje_exito = "✅ Producto agregado correctamente.";
      }
  }

  // Eliminar producto
  if (isset($_GET["eliminar"])) {
      $resultado = ProductController::eliminarProducto($conn, $_GET["eliminar"]);

      if (is_string($resultado)) {
          $mensaje_error = $resultado;
      } else {
          $mensaje_exito = "✅ Producto eliminado exitosamente.";
      }
  }

  // Toggle visibilidad (activo 1/0)
  if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "toggle_activo" && $puedeModificar) {
      $id_producto = (int)($_POST["id_producto"] ?? 0);
      $nuevo       = isset($_POST["nuevo_activo"]) ? (int)$_POST["nuevo_activo"] : null;
      if ($id_producto > 0 && ($nuevo === 0 || $nuevo === 1)) {
          ProductController::setActivo($conn, $id_producto, (bool)$nuevo);
          $mensaje_exito = $nuevo ? "✅ Producto activado." : "⛔ Producto desactivado.";
      }
  }

  $productos = ProductController::obtenerProductosAdmin($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Administrador de Productos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include __DIR__ . '/partials/admin_sidebar_open.php';?>
<div class="container mt-4">
  <h2 class="mb-4">🛠️ Administrar Productos</h2>

  <?php if ($mensaje_error): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($mensaje_error) ?></div>
  <?php endif; ?>

  <?php if ($mensaje_exito): ?>
    <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
  <?php endif; ?>

  <!-- Form alta/edición -->
  <form method="POST" class="card p-4 mb-4">
    <h5 class="mb-3"><?= $editando ? 'Editar producto' : 'Añadir nuevo producto' ?></h5>
    <input type="hidden" name="id_producto" value="<?= $producto_editar ? (int)$producto_editar['id_producto'] : '' ?>">
    <div class="row g-2">
      <div class="col-md-6"><input name="nombre_producto" class="form-control" placeholder="Nombre" value="<?= $producto_editar ? htmlspecialchars($producto_editar['nombre_producto']) : '' ?>" required></div>
      <div class="col-md-6"><input name="precio_unitario" type="number" class="form-control" placeholder="Precio" value="<?= $producto_editar ? htmlspecialchars($producto_editar['precio_unitario']) : '' ?>" required></div>
      <div class="col-md-6"><input name="stock_actual" type="number" class="form-control" placeholder="Stock" value="<?= $producto_editar ? htmlspecialchars($producto_editar['stock_actual']) : '' ?>" required></div>
      <div class="col-md-6"><input name="url_imagen_principal" class="form-control" placeholder="URL imagen" value="<?= $producto_editar ? htmlspecialchars($producto_editar['url_imagen_principal']) : '' ?>" required></div>
      <div class="col-12">
        <select name="id_categoria" class="form-select" required>
          <option value="">Seleccionar categoría</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?= (int)$c["id_categoria"] ?>" <?= $producto_editar && (int)$producto_editar['id_categoria'] === (int)$c["id_categoria"] ? 'selected' : '' ?>><?= htmlspecialchars($c["nombre_categoria"]) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12"><textarea name="descripcion" class="form-control" placeholder="Descripción"><?= $producto_editar ? htmlspecialchars($producto_editar['descripcion']) : '' ?></textarea></div>
      <div class="col-12 text-end">
        <?php if ($editando): ?>
          <a href="admin_productos.php" class="btn btn-secondary me-2">Cancelar</a>
        <?php endif; ?>
        <button class="btn btn-primary"><?= $editando ? 'Actualizar' : 'Agregar' ?></button>
      </div>
    </div>
  </form>

  <h5 class="mb-3">📦 Productos</h5>
  <div class="row">
    <?php foreach ($productos as $p): ?>
      <div class="col-md-4">
        <div class="card mb-4 h-100">
          <img src="<?= htmlspecialchars($p["url_imagen_principal"]) ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
          <div class="card-body d-flex flex-column">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h5 class="card-title mb-0"><?= htmlspecialchars($p["nombre_producto"]) ?></h5>
              <?php if ((int)$p['activo'] === 1): ?>
                <span class="badge bg-success">Activo</span>
              <?php else: ?>
                <span class="badge bg-secondary">Inactivo</span>
              <?php endif; ?>
            </div>

            <p class="mb-1">$<?= number_format((float)$p["precio_unitario"], 0, ',', '.') ?></p>
            <p class="text-muted mb-3">Stock: <?= htmlspecialchars((string)$p["stock_actual"]) ?></p>

            <div class="mt-auto d-flex gap-2">
              <a href="?editar=<?= (int)$p["id_producto"] ?>" class="btn btn-warning flex-fill">Editar</a>

              <?php if ($puedeModificar): ?>
                <form method="POST" class="flex-fill">
                  <input type="hidden" name="action" value="toggle_activo">
                  <input type="hidden" name="id_producto" value="<?= (int)$p['id_producto'] ?>">
                  <input type="hidden" name="nuevo_activo" value="<?= (int)$p['activo'] === 1 ? 0 : 1 ?>">
                  <button class="btn <?= (int)$p['activo'] === 1 ? 'btn-outline-secondary' : 'btn-success' ?> w-100">
                    <?= (int)$p['activo'] === 1 ? 'Desactivar' : 'Activar' ?>
                  </button>
                </form>
              <?php endif; ?>

              <a href="?eliminar=<?= (int)$p["id_producto"] ?>" onclick="return confirm('¿Eliminar este producto?')" class="btn btn-danger flex-fill">Eliminar</a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if (empty($productos)): ?>
      <div class="col-12">
        <div class="alert alert-info">No hay productos para mostrar.</div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/partials/admin_sidebar_close.php'; // cierra main + flex ?>
</body>
</html>