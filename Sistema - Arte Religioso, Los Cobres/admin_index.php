<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/MySqlDb.php';
require_once __DIR__ . '/controllers/ClientController.php';

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id_pedido = (int)($_POST['id_pedido'] ?? 0);
    if ($id_pedido > 0) {
        if ($_POST['action'] === 'confirmar_entrega') {
            ClientController::actualizarEstadoPedido($conn, $id_pedido, 'completado');
        } elseif ($_POST['action'] === 'rechazar_pedido') {
            ClientController::actualizarEstadoPedido($conn, $id_pedido, 'cancelado');
        }
        // Redirect to avoid resubmission
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Get pending orders
$pedidos_pendientes = ClientController::obtenerPedidosPendientes($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Inicio | Los Cobres</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

  <?php include __DIR__ . '/partials/admin_sidebar_open.php';?>

  <div class="container mt-5">
    <div class="text-center">
      <h1 class="display-4">Bienvenido a Los Cobres</h1>
      <p class="lead">Menu administrador</p>
      <hr class="my-4">
    </div>

    <!-- Pedidos Pendientes -->
    <div class="mt-5">
      <h2>Pedidos Pendientes</h2>
      <?php if (empty($pedidos_pendientes)): ?>
        <p class="text-muted">No hay pedidos pendientes.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>ID Pedido</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Método Pago</th>
                <th>Lugar Retiro</th>
                <th>Total</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pedidos_pendientes as $pedido): ?>
                <tr>
                  <td><?= htmlspecialchars($pedido['id_pedido']) ?></td>
                  <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($pedido['fecha_pedido']))) ?></td>
                  <td><?= htmlspecialchars($pedido['cliente_nombre'] . ' ' . $pedido['cliente_apellido']) ?></td>
                  <td><?= htmlspecialchars($pedido['metodo_pago']) ?></td>
                  <td><?= htmlspecialchars($pedido['lugar_retiro']) ?></td>
                  <td>$<?= number_format($pedido['total'], 0, ',', '.') ?></td>
                  <td>
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="action" value="confirmar_entrega">
                      <input type="hidden" name="id_pedido" value="<?= (int)$pedido['id_pedido'] ?>">
                      <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Confirmar entrega del pedido?')">Confirmar Entrega</button>
                    </form>
                    <a href="admin_editar_pedido.php?id=<?= urlencode($pedido['id_pedido']) ?>" class="btn btn-warning btn-sm ms-1">Editar</a>
                    <form method="POST" class="d-inline ms-1">
                      <input type="hidden" name="action" value="rechazar_pedido">
                      <input type="hidden" name="id_pedido" value="<?= (int)$pedido['id_pedido'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Rechazar el pedido?')">Rechazar</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php include __DIR__ . '/partials/admin_sidebar_close.php'; // cierra main + flex ?>
</body>
</html>