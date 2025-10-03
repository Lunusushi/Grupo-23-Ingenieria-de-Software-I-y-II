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

  $productos = ProductController::obtenerProductos($conn);
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

  <form method="POST" class="card p-4 mb-4">
    <h5 class="mb-3"><?= $editando ? 'Editar producto' : 'Añadir nuevo producto' ?></h5>
    <input type="hidden" name="id_producto" value="<?= $producto_editar ? $producto_editar['id_producto'] : '' ?>">
    <div class="row g-2">
      <div class="col-md-6"><input name="nombre_producto" class="form-control" placeholder="Nombre" value="<?= $producto_editar ? htmlspecialchars($producto_editar['nombre_producto']) : '' ?>" required></div>
      <div class="col-md-6"><input name="precio_unitario" type="number" class="form-control" placeholder="Precio" value="<?= $producto_editar ? $producto_editar['precio_unitario'] : '' ?>" required></div>
      <div class="col-md-6"><input name="stock_actual" type="number" class="form-control" placeholder="Stock" value="<?= $producto_editar ? $producto_editar['stock_actual'] : '' ?>" required></div>
      <div class="col-md-6"><input name="url_imagen_principal" class="form-control" placeholder="URL imagen" value="<?= $producto_editar ? htmlspecialchars($producto_editar['url_imagen_principal']) : '' ?>" required></div>
      <div class="col-12">
        <select name="id_categoria" class="form-select" required>
          <option value="">Seleccionar categoría</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?= $c["id_categoria"] ?>" <?= $producto_editar && $producto_editar['id_categoria'] == $c["id_categoria"] ? 'selected' : '' ?>><?= $c["nombre_categoria"] ?></option>
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

  <h5 class="mb-3">📦 Productos activos</h5>
  <div class="row">
    <?php foreach ($productos as $p): ?>
      <div class="col-md-4">
        <div class="card mb-4">
          <img src="<?= $p["url_imagen_principal"] ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
          <div class="card-body">
            <h5 class="card-title"><?= $p["nombre_producto"] ?></h5>
            <p>$<?= $p["precio_unitario"] ?> — Stock: <?= $p["stock_actual"] ?></p>
            <div class="d-flex gap-2">
              <a href="?editar=<?= $p["id_producto"] ?>" class="btn btn-warning flex-fill">Editar</a>
              <a href="?eliminar=<?= $p["id_producto"] ?>" onclick="return confirm('¿Eliminar este producto?')" class="btn btn-danger flex-fill">Eliminar</a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php include __DIR__ . '/partials/admin_sidebar_close.php'; // cierra main + flex ?>
</body>
</html>
