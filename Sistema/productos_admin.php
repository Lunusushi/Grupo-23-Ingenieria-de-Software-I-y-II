<?php
require_once 'config/MySqlDb.php';
require_once 'controllers/ProductController.php';
require_once 'partials/navbar.php';

$categorias = ProductController::obtenerCategorias($conn);

$mensaje_error = "";
$mensaje_exito = "";

// Agregar producto
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["nombre_producto"])) {
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="public/css/main.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
  <h2 class="mb-4">🛠️ Administrar Productos</h2>

  <?php if ($mensaje_error): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($mensaje_error) ?></div>
  <?php endif; ?>

  <?php if ($mensaje_exito): ?>
    <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
  <?php endif; ?>

  <form method="POST" class="card p-4 mb-4">
    <h5 class="mb-3">Añadir nuevo producto</h5>
    <div class="row g-2">
      <div class="col-md-6"><input name="nombre_producto" class="form-control" placeholder="Nombre" required></div>
      <div class="col-md-6"><input name="precio_unitario" type="number" class="form-control" placeholder="Precio" required></div>
      <div class="col-md-6"><input name="stock_actual" type="number" class="form-control" placeholder="Stock" required></div>
      <div class="col-md-6"><input name="url_imagen_principal" class="form-control" placeholder="URL imagen" required></div>
      <div class="col-12">
        <select name="id_categoria" class="form-select" required>
          <option value="">Seleccionar categoría</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?= $c["id_categoria"] ?>"><?= $c["nombre_categoria"] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12"><textarea name="descripcion" class="form-control" placeholder="Descripción"></textarea></div>
      <div class="col-12 text-end"><button class="btn btn-primary">Agregar</button></div>
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
            <a href="?eliminar=<?= $p["id_producto"] ?>" onclick="return confirm('¿Eliminar este producto?')" class="btn btn-danger w-100">Eliminar</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

</body>
</html>
