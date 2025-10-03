<?php
  session_start();
  if (!isset($_SESSION['user']['id'])) {
      header("Location: login.php");
      exit();
  }

  require_once 'config/MySqlDb.php';
  require_once 'controllers/ClientController.php';
  require_once 'partials/navbar.php'; // Incluyo el navbar común

  $pedido = null;
  $detalles = [];
  $error = "";

  if ($_SERVER["REQUEST_METHOD"] === "POST") {
      $codigo = trim($_POST["codigo"]);
      $pedido = ClientController::buscarPedidoPorCodigo($conn, $codigo);

      if ($pedido) {
          $detalles = ClientController::detallesPedido($conn, $pedido["id_pedido"]);
      } else {
          $error = "❌ Pedido no encontrado.";
      }
  }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificar Pedido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container mt-4">
  <h2 class="mb-4">🔎 Verificar Pedido por Código</h2>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" class="mb-4 d-flex gap-2 flex-wrap">
      <input name="codigo" class="form-control me-2" placeholder="Código de retiro" required>
      <button type="submit" class="btn btn-primary">Buscar</button>
  </form>

  <?php if ($pedido): ?>
    <div class="card shadow-sm">
      <div class="card-body">
        <h3 class="card-title mb-3">Detalles del pedido #<?= htmlspecialchars($pedido["id_pedido"]) ?></h3>
        <p><strong>Cliente ID:</strong> <?= htmlspecialchars($pedido["id_cliente"]) ?></p>
        <p><strong>Método de pago:</strong> <?= htmlspecialchars($pedido["metodo_pago"]) ?></p>
        <p><strong>Lugar de retiro:</strong> <?= htmlspecialchars($pedido["lugar_retiro"]) ?></p>
        <p><strong>Estado:</strong> <?= htmlspecialchars($pedido["estado"]) ?></p>

        <h4 class="mt-4">Productos:</h4>
        <ul class="list-group">
          <?php foreach ($detalles as $d): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($d["nombre_producto"]) ?> — <?= htmlspecialchars($d["cantidad"]) ?> unidad(es)
                <span>$<?= number_format($d["subtotal"], 0, ',', '.') ?></span>
              </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
