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

$mensaje = "";
$carrito = ClientController::obtenerCarrito($conn, $id_cliente);
$id_carrito = $carrito["id_carrito"];

$codigo = null;
$operadorAsignado = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $metodo = $_POST["metodo_pago"];
    $lugar = $_POST["lugar_retiro"];

    try {
        // Obtener el primer operador antes de hacer el pedido
        $stmt = $conn->query("SELECT id_usuario FROM OPERADOR LIMIT 1");
        $op = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$op) {
            $mensaje = "⚠️ No hay operadores disponibles para asignar el pedido.";
        } else {
            $operadorAsignado = $op["id_usuario"];
            $codigo = ClientController::realizarPedido($conn, $id_cliente, $id_carrito, $metodo, $lugar);
            $mensaje = "✅ Pedido registrado. Código de retiro: <strong>" . htmlspecialchars($codigo) . "</strong><br>🧑‍💼 Operador asignado: <strong>ID $operadorAsignado</strong>";
        }
    } catch (Exception $e) {
        $mensaje = "❌ Error al registrar el pedido: " . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Finalizar Pedido</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container">
  <h2 class="mb-4">📦 Finalizar Pedido</h2>

  <?php if ($mensaje): ?>
    <div class="alert alert-info"><?= $mensaje ?></div>
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
