<?php
session_start();
if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/MySqlDb.php';
require_once 'controllers/ClientController.php';
require_once 'partials/navbar.php';

$id_cliente = $_SESSION['user']['id'];

$carrito = ClientController::obtenerCarrito($conn, $id_cliente);
$id_carrito = $carrito["id_carrito"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_producto = $_POST["id_producto"];
    $cantidad = $_POST["cantidad"];
    ClientController::agregarProducto($conn, $id_carrito, $id_producto, $cantidad);
}

$items = ClientController::obtenerItems($conn, $id_carrito);
$total = 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Carrito de Compras</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container">
  <h2 class="mb-4">🛒 Mi Carrito de Compras</h2>

  <?php if (count($items) === 0): ?>
    <div class="alert alert-info">No hay productos en tu carrito.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered align-middle text-center">
        <thead class="table-light">
          <tr>
            <th>Imagen</th>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio unitario</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <?php
              $subtotal = $item["cantidad"] * $item["precio_unitario_momento"];
              $total += $subtotal;
            ?>
            <tr>
              <td><img src="<?= htmlspecialchars($item["url_imagen_principal"]) ?>" width="80"></td>
              <td><?= htmlspecialchars($item["nombre_producto"]) ?></td>
              <td><?= (int)$item["cantidad"] ?></td>
              <td>$<?= number_format($item["precio_unitario_momento"], 0, ',', '.') ?></td>
              <td><strong>$<?= number_format($subtotal, 0, ',', '.') ?></strong></td>
            </tr>
          <?php endforeach; ?>
          <tr class="table-secondary">
            <td colspan="4" class="text-end"><strong>Total:</strong></td>
            <td><strong>$<?= number_format($total, 0, ',', '.') ?></strong></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="text-end">
      <a href="realizar_pedido.php" class="btn btn-success">🧾 Finalizar Pedido</a>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
