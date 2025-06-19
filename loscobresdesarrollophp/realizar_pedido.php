<?php
require_once 'config/db.php';
require_once 'controllers/PedidoController.php';
require_once 'controllers/CarritoController.php';
require_once 'partials/navbar.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_cliente = $_SESSION["usuario_id"] ?? 1;

$carrito = CarritoController::obtenerCarrito($conn, $id_cliente);
$id_carrito = $carrito["id_carrito"];

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $metodo = $_POST["metodo_pago"];
    $lugar = $_POST["lugar_retiro"];
    $codigo = PedidoController::realizarPedido($conn, $id_cliente, $id_carrito, $metodo, $lugar);
    $mensaje = "✅ Pedido registrado. Código de retiro: <strong>$codigo</strong>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Finalizar Pedido</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="public/css/main.css" rel="stylesheet">
</head>
<body>



<div class="container">
  <h2 class="mb-4">📦 Finalizar Pedido</h2>

  <?php if ($mensaje): ?>
    <div class="alert alert-success"><?= $mensaje ?></div>
  <?php else: ?>
    <form method="POST" class="card p-4">
      <div class="mb-3">
        <label class="form-label">Método de pago</label>
        <select name="metodo_pago" class="form-select" required>
          <option value="efectivo">Efectivo</option>
          <option value="transferencia">Transferencia</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Lugar de retiro</label>
        <input name="lugar_retiro" class="form-control" required>
      </div>

      <button class="btn btn-success w-100">Confirmar Pedido</button>
    </form>
  <?php endif; ?>
</div>

</body>
</html>
