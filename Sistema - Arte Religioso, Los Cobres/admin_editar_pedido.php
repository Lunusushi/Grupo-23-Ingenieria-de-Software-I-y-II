<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/MySqlDb.php';
require_once __DIR__ . '/controllers/ClientController.php';

$id_pedido = (int)($_GET['id'] ?? 0);
if ($id_pedido <= 0) {
    die("ID de pedido inválido.");
}

// Get order details
$stmt = $conn->prepare("SELECT * FROM PEDIDO WHERE id_pedido = ?");
$stmt->execute([$id_pedido]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pedido) {
    die("Pedido no encontrado.");
}

// Get order items
$detalles = ClientController::detallesPedido($conn, $id_pedido);

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_pedido') {
    $lugar_retiro = trim($_POST['lugar_retiro'] ?? '');
    $metodo_pago = trim($_POST['metodo_pago'] ?? '');

    $stmt = $conn->prepare("UPDATE PEDIDO SET lugar_retiro = ?, metodo_pago = ? WHERE id_pedido = ?");
    $stmt->execute([$lugar_retiro, $metodo_pago, $id_pedido]);

    header("Location: admin_index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Pedido #<?= htmlspecialchars($id_pedido) ?> | Los Cobres</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

  <?php include __DIR__ . '/partials/admin_sidebar_open.php';?>

  <div class="container mt-5">
    <h1>Editar Pedido #<?= htmlspecialchars($id_pedido) ?></h1>
    <hr>

    <form method="POST">
      <input type="hidden" name="action" value="update_pedido">

      <div class="mb-3">
        <label for="lugar_retiro" class="form-label">Lugar de Retiro</label>
        <input type="text" class="form-control" id="lugar_retiro" name="lugar_retiro" value="<?= htmlspecialchars($pedido['lugar_retiro']) ?>" required>
      </div>

      <div class="mb-3">
        <label for="metodo_pago" class="form-label">Método de Pago</label>
        <select class="form-select" id="metodo_pago" name="metodo_pago" required>
          <option value="efectivo" <?= $pedido['metodo_pago'] === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
          <option value="tarjeta" <?= $pedido['metodo_pago'] === 'tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
          <option value="transferencia" <?= $pedido['metodo_pago'] === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
        </select>
      </div>

      <h3>Detalles del Pedido</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio Unitario</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($detalles as $det): ?>
            <tr>
              <td><?= htmlspecialchars($det['nombre_producto']) ?></td>
              <td><?= htmlspecialchars($det['cantidad']) ?></td>
              <td>$<?= number_format($det['precio_unitario'], 0, ',', '.') ?></td>
              <td>$<?= number_format($det['subtotal'], 0, ',', '.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <button type="submit" class="btn btn-primary">Guardar Cambios</button>
      <a href="admin_index.php" class="btn btn-secondary">Volver</a>
    </form>
  </div>

<?php include __DIR__ . '/partials/admin_sidebar_close.php'; ?>
</body>
</html>
