<?php
  // realizar_pedido.php
  if (session_status() === PHP_SESSION_NONE) session_start();

  require_once __DIR__ . '/config/MySqlDb.php';
  require_once __DIR__ . '/controllers/ClientController.php';
  require_once __DIR__ . '/partials/navbar.php';

  $isCliente = (isset($_SESSION['user']['type']) && $_SESSION['user']['type'] === 'cliente');
  $id_usuario = $isCliente ? (int)$_SESSION['user']['id'] : null;

  // Obtener carrito universal (cliente o invitado)
  $carrito = ClientController::obtenerCarritoUniversal($conn, $id_usuario);
  if (!$carrito) {
      die("No hay carrito disponible para tu tipo de usuario.");
  }
  $id_carrito = (int)$carrito['id_carrito'];

  // Traer items para mostrar resumen
  $items = ClientController::obtenerItems($conn, $id_carrito);

  // Si no hay items, no hay qué pagar
  if (empty($items)) {
      header("Location: carrito.php");
      exit;
  }

  // 🔹 OPERADOR: obtener el primer operador disponible (y opcionalmente su nombre)
  $opStmt = $conn->query("
      SELECT o.id_operador, u.nombre, u.apellido
      FROM OPERADOR o
      LEFT JOIN USUARIO u ON u.id_usuario = o.id_usuario
      ORDER BY o.id_operador ASC
      LIMIT 1
  ");
  $operador = $opStmt->fetch(PDO::FETCH_ASSOC);

  $mensaje = "";
  $codigoPedido = null;

  // Calcular total
  $total = 0;
  foreach ($items as $it) {
      $total += (float)$it['cantidad'] * (float)$it['precio_unitario_momento'];
  }

  // Si no hay operador, no permitimos finalizar pedido
  $hayOperador = !empty($operador) && !empty($operador['id_operador']);

  // Procesar envío del formulario
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      if (!$hayOperador) {
          $mensaje = "❌ No hay operadores disponibles para asignar a tu pedido. Intenta más tarde.";
      } else {
          $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
          $lugar_retiro = $_POST['lugar_retiro'] ?? 'tienda';

          try {
              if ($isCliente) {
                  // Flujo actual para clientes registrados (la asignación de operador la hace el método)
                  $codigoPedido = ClientController::realizarPedido($conn, $id_usuario, $id_carrito, $metodo_pago, $lugar_retiro);
                  $mensaje = "✅ Pedido realizado. Tu código de verificación es: <strong>" . htmlspecialchars($codigoPedido) . "</strong>";
              } else {
                  // Flujo invitado (la asignación de operador la hace el método)
                  $guest_nombre   = trim($_POST['guest_nombre'] ?? '');
                  $guest_email    = trim($_POST['guest_email'] ?? '');
                  $guest_telefono = trim($_POST['guest_telefono'] ?? '');

                  if ($guest_nombre === '' || !filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
                      $mensaje = "❌ Por favor, ingresa un nombre y email válidos.";
                  } else {
                      $codigoPedido = ClientController::realizarPedidoInvitado(
                          $conn,
                          $id_carrito,
                          $guest_nombre,
                          $guest_email,
                          $guest_telefono,
                          $metodo_pago,
                          $lugar_retiro
                      );
                      $mensaje = "✅ Pedido realizado como invitado. Tu código de verificación es: <strong>" . htmlspecialchars($codigoPedido) . "</strong>";
                  }
              }
          } catch (Exception $e) {
              $mensaje = "❌ Error al procesar el pedido: " . htmlspecialchars($e->getMessage());
          }
      }
  }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Realizar Pedido | Los Cobres</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS (misma versión en todo el sitio) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<main class="flex-grow-1">

<div class="container my-4">
  <h1 class="h3 mb-4">Finalizar compra</h1>

  <?php if (!$hayOperador): ?>
    <div class="alert alert-warning">
      No hay operadores disponibles para asignar a tu pedido en este momento. Por favor, intenta más tarde.
    </div>
    <a href="carrito.php" class="btn btn-outline-secondary">Volver al carrito</a>
  <?php endif; ?>

  <?php if (!empty($mensaje)): ?>
    <div class="alert <?= $codigoPedido ? 'alert-success' : 'alert-danger' ?>">
      <?= $mensaje ?>
    </div>
    <?php if ($codigoPedido): ?>
      <a href="index.php" class="btn btn-primary">Volver al inicio</a>
      <a href="catalogo.php" class="btn btn-outline-secondary">Seguir comprando</a>
    <?php endif; ?>
  <?php endif; ?>

  <?php if (!$codigoPedido && $hayOperador): ?>
    <!-- Info de operador asignable (opcional mostrar) -->
    <div class="alert alert-info py-2">
      <small>
        Operador asignado al retiro:
        <strong>
          <?= htmlspecialchars(trim(($operador['nombre'] ?? '') . ' ' . ($operador['apellido'] ?? '')) ?: ('Operador #' . $operador['id_operador'])) ?>
        </strong>
      </small>
    </div>

    <!-- Resumen del carrito -->
    <div class="card mb-4">
      <div class="card-header">Resumen de tu compra</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table mb-0 align-middle">
            <thead>
              <tr>
                <th>Producto</th>
                <th class="text-end">Precio</th>
                <th class="text-end">Cant.</th>
                <th class="text-end">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it):
                  $precio = (float)$it['precio_unitario_momento'];
                  $cant   = (float)$it['cantidad'];
                  $subtotal = $precio * $cant;
              ?>
                <tr>
                  <td><?= htmlspecialchars($it['nombre_producto'] ?? 'Producto') ?></td>
                  <td class="text-end">$<?= number_format($precio, 0, ',', '.') ?></td>
                  <td class="text-end"><?= rtrim(rtrim(number_format($cant, 2, ',', '.'), '0'), ',') ?></td>
                  <td class="text-end">$<?= number_format($subtotal, 0, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="3" class="text-end">Total</th>
                <th class="text-end">$<?= number_format($total, 0, ',', '.') ?></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Formulario de pago -->
    <form method="POST" class="row g-3">
      <?php if (!$isCliente): ?>
        <div class="col-12">
          <h2 class="h6">Tus datos (compra como invitado)</h2>
        </div>
        <div class="col-md-6">
          <label class="form-label">Nombre y Apellido</label>
          <input name="guest_nombre" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input type="email" name="guest_email" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Teléfono (opcional)</label>
          <input name="guest_telefono" class="form-control">
        </div>
      <?php endif; ?>

      <div class="col-md-6">
        <label class="form-label">Método de pago</label>
        <select name="metodo_pago" class="form-select">
          <option value="efectivo">Efectivo</option>
          <option value="tarjeta">Tarjeta</option>
          <option value="transferencia">Transferencia</option>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Lugar de retiro</label>
        <select name="lugar_retiro" class="form-select">
          <option value="tienda">Tienda</option>
          <option value="sucursal">Sucursal</option>
        </select>
      </div>

      <div class="col-12">
        <button class="btn btn成功 btn-success">Confirmar pedido</button>
        <a href="carrito.php" class="btn btn-outline-secondary">Volver al carrito</a>
      </div>
    </form>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
