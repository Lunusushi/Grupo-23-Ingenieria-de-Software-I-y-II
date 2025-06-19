<?php
require_once 'controllers/FavoritosController.php';

$id_cliente = $_SESSION["usuario_id"];
$lista = FavoritosController::obtenerLista($conn, $id_cliente);
$id_lista = $lista["id_lista"];

// Agregar producto
$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_producto = $_POST["id_producto"];
    $mensaje = FavoritosController::agregarFavorito($conn, $id_lista, $id_producto);
}

$favoritos = FavoritosController::obtenerFavoritos($conn, $id_lista);
?>

<h2>⭐ Mis Favoritos</h2>
<?php if ($mensaje) echo "<p><strong>$mensaje</strong></p>"; ?>

<?php if (count($favoritos) == 0): ?>
    <p>No tienes productos en tu lista de favoritos.</p>
<?php else: ?>
    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
        <?php foreach ($favoritos as $f): ?>
            <div style="border: 1px solid #ccc; padding: 10px; width: 250px">
                <img src="<?= $f["url_imagen_principal"] ?>" width="100%" height="180">
                <h3><?= $f["nombre_producto"] ?></h3>
                <p>Agregado el: <?= substr($f["fecha_agregado"], 0, 10) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
