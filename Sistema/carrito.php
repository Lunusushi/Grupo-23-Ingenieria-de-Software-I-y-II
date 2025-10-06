<?php
// carrito.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/config/MySqlDb.php';
require_once __DIR__ . '/controllers/ClientController.php';
require_once __DIR__ . '/partials/navbar.php';

// ¿Cliente logueado o invitado?
$isCliente  = (isset($_SESSION['user']['type']) && $_SESSION['user']['type'] === 'cliente');
$id_usuario = $isCliente ? (int)$_SESSION['user']['id'] : null;
$id_cliente = null;
$error = '';

if ($isCliente) {
    $stmt = $conn->prepare("SELECT id_cliente FROM CLIENTE WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $id_cliente = (int)$row['id_cliente'];
    } else {
        $error = "No encontramos información de cliente asociada a tu cuenta.";
    }
}

// Obtener/crear carrito universal (cliente o invitado)
$carrito = ClientController::obtenerCarritoUniversal($conn, $id_cliente ?? $id_usuario);
if (!$carrito && !$error) {
    $error = "No tienes acceso a esta página o no eres un cliente registrado.";
}

$id_carrito = $carrito ? (int)$carrito['id_carrito'] : null;

if (!$error && $id_carrito !== null && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_producto']) && !isset($_POST['action'])) {

    $id_producto = (int)($_POST['id_producto'] ?? 0);
    $cantidad    = isset($_POST['cantidad']) && is_numeric($_POST['cantidad']) ? (float)$_POST['cantidad'] : 1;
    if ($cantidad < 1) $cantidad = 1;

    ClientController::agregarProducto($conn, $id_carrito, $id_producto, $cantidad);
    header("Location: carrito.php");
    exit;
}

// Quitar cierta cantidad de un ítem concreto
if (!$error && $id_carrito !== null && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_part') {
    $id_item_carrito   = (int)($_POST['id_item_carrito'] ?? 0);
    $cantidad_remover  = isset($_POST['cantidad_remover']) && is_numeric($_POST['cantidad_remover'])
        ? (float)$_POST['cantidad_remover'] : 1;
    if ($cantidad_remover < 1) $cantidad_remover = 1;

    ClientController::decrementarCantidad($conn, $id_carrito, $id_item_carrito, $cantidad_remover);
    header("Location: carrito.php");
    exit;
}

// Eliminar todo el ítem (atajo)
if (!$error && $id_carrito !== null && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_all') {
    $id_item_carrito = (int)($_POST['id_item_carrito'] ?? 0);
    ClientController::eliminarItem($conn, $id_carrito, $id_item_carrito);
    header("Location: carrito.php");
    exit;
}

// Traer items del carrito
$items = (!$error && $id_carrito !== null) ? ClientController::obtenerItems($conn, $id_carrito) : [];

// Calcular total
$total = 0;
foreach ($items as $it) {
    $subtotal = (float)$it['cantidad'] * (float)$it['precio_unitario_momento'];
    $total += $subtotal;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Carrito de Compras | Los Cobres</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<main class="flex-grow-1">
<div class="container my-4">
  <h1 class="h3 mb-4">Tu carrito</h1>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php elseif (empty($items)): ?>
    <div class="alert alert-info">Tu carrito está vacío.</div>
    <a href="catalogo.php" class="btn btn-primary">← Ir al catálogo</a>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width:64px;">Imagen</th>
            <th>Producto</th>
            <th class="text-end">Precio</th>
            <th class="text-end">Cantidad</th>
            <th class="text-end">Subtotal</th>
            <th class="text-end" style="width:260px;">Quitar</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it):
            $precio   = (float)$it['precio_unitario_momento'];
            $cant     = (float)$it['cantidad'];
            $subtotal = $precio * $cant;
            $id_item  = (int)$it['id_item_carrito'];
            $cantMax  = max(1, (int)$cant);
        ?>
          <tr>
            <td>
              <?php if (!empty($it['url_imagen_principal'])): ?>
                <img src="<?= htmlspecialchars($it['url_imagen_principal']) ?>" alt="" style="width:64px;height:64px;object-fit:cover" class="rounded">
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($it['nombre_producto'] ?? 'Producto') ?></td>
            <td class="text-end">$<?= number_format($precio, 0, ',', '.') ?></td>
            <td class="text-end"><?= (int)$cant ?></td>
            <td class="text-end">$<?= number_format($subtotal, 0, ',', '.') ?></td>
            <td class="text-end">
              <!-- Quitar N unidades (sin exceder lo que hay) -->
              <form method="POST" class="d-inline-flex align-items-center gap-2">
                <input type="hidden" name="action" value="remove_part">
                <input type="hidden" name="id_item_carrito" value="<?= $id_item ?>">
                <input
                  type="number"
                  name="cantidad_remover"
                  min="1"
                  max="<?= $cantMax ?>"
                  step="1"
                  value="1"
                  class="form-control text-end"
                  style="max-width: 90px;"
                  aria-label="Cantidad a quitar"
                  required
                >
                <button class="btn btn-outline-danger btn-sm" type="submit" title="Quitar del carrito">
                  Quitar
                </button>
              </form>

              <!-- Eliminar todo el ítem (atajo) -->
              <form method="POST" onsubmit="return confirm('¿Eliminar este producto del carrito?');" class="d-inline">
                <input type="hidden" name="action" value="remove_all">
                <input type="hidden" name="id_item_carrito" value="<?= $id_item ?>">
                <button class="btn btn-danger btn-sm" type="submit">Quitar todo</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="4" class="text-end">Total</th>
            <th class="text-end">$<?= number_format($total, 0, ',', '.') ?></th>
            <th></th>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="d-flex gap-2">
      <a href="catalogo.php" class="btn btn-outline-secondary">← Seguir comprando</a>
      <a href="realizar_pedido.php" class="btn btn-success">Proceder al pago</a>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>