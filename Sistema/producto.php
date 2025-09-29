<?php
// producto.php
include_once "config/MySqlDb.php";
require_once 'partials/navbar.php';

if (!isset($_GET['id'])) {
    die("Producto no especificado");
}

$id = intval($_GET['id']);

// Buscar el producto en la BD
$sql = "SELECT id_producto, nombre_producto, descripcion, precio_unitario, stock_actual, url_imagen_principal 
        FROM producto 
        WHERE id_producto = :id AND activo = 1";

$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    die("Producto no encontrado");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($producto['nombre_producto']) ?> - Detalles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

    <div class="container" style="max-width: 800px; margin: 20px auto;">
        <h1><?= htmlspecialchars($producto['nombre_producto']) ?></h1>
        <img src="<?= htmlspecialchars($producto['url_imagen_principal']) ?>" alt="Imagen del producto" style="max-width: 300px; display: block; margin-bottom: 20px;">
        <p><strong>Descripción:</strong> <?= htmlspecialchars($producto['descripcion']) ?></p>
        <p><strong>Precio:</strong> $<?= number_format($producto['precio_unitario'], 0, ',', '.') ?></p>
        <p><strong>Stock:</strong> <?= $producto['stock_actual'] > 0 ? $producto['stock_actual'] : 'Agotado' ?></p>
        
        <?php if ($producto['stock_actual'] > 0): ?>
            <form action="carrito.php" method="POST">
                <input type="hidden" name="id_producto" value="<?= $producto['id_producto'] ?>">
                <button type="submit">Agregar al carrito</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>