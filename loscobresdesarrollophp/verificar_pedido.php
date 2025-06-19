<?php
require_once 'controllers/PedidoController.php';

$pedido = null;
$detalles = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $codigo = $_POST["codigo"];
    $pedido = PedidoController::buscarPedidoPorCodigo($conn, $codigo);

    if ($pedido) {
        $detalles = PedidoController::detallesPedido($conn, $pedido["id_pedido"]);
    } else {
        echo "<p>❌ Pedido no encontrado.</p>";
    }
}
?>

<h2>🔎 Verificar Pedido por Código</h2>

<form method="POST">
    <input name="codigo" placeholder="Código de retiro" required>
    <button type="submit">Buscar</button>
</form>

<?php if ($pedido): ?>
    <h3>Detalles del pedido #<?= $pedido["id_pedido"] ?></h3>
    <p>Cliente ID: <?= $pedido["id_cliente"] ?></p>
    <p>Método: <?= $pedido["metodo_pago"] ?></p>
    <p>Lugar: <?= $pedido["lugar_retiro"] ?></p>
    <p>Estado: <?= $pedido["estado"] ?></p>

    <h4>Productos:</h4>
    <ul>
        <?php foreach ($detalles as $d): ?>
            <li><?= $d["nombre_producto"] ?> — <?= $d["cantidad"] ?> unidad(es) — $<?= $d["subtotal"] ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
