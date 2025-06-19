<?php
require_once __DIR__ . '/../config/db.php';
session_start();

// Simulación de login temporal
if (!isset($_SESSION["usuario_id"])) {
    $_SESSION["usuario_id"] = 1; // ← Asegúrate de que ese id exista en CLIENTE
}

class CarritoController {
    public static function obtenerCarrito($conn, $id_cliente) {
        $stmt = $conn->prepare("SELECT * FROM CARRITO_COMPRA WHERE id_cliente = ?");
        $stmt->execute([$id_cliente]);
        $carrito = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$carrito) {
            $stmt = $conn->prepare("INSERT INTO CARRITO_COMPRA (id_cliente) VALUES (?)");
            $stmt->execute([$id_cliente]);
            $id = $conn->lastInsertId();
            $stmt = $conn->prepare("SELECT * FROM CARRITO_COMPRA WHERE id_carrito = ?");
            $stmt->execute([$id]);
            $carrito = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $carrito;
    }

    public static function agregarProducto($conn, $id_carrito, $id_producto, $cantidad) {
        // Verificar si ya existe
        $check = $conn->prepare("SELECT * FROM ITEM_CARRITO WHERE id_carrito = ? AND id_producto = ?");
        $check->execute([$id_carrito, $id_producto]);

        if ($row = $check->fetch(PDO::FETCH_ASSOC)) {
            // Actualizar cantidad
            $nueva = $row["cantidad"] + $cantidad;
            $update = $conn->prepare("UPDATE ITEM_CARRITO SET cantidad = ? WHERE id_item_carrito = ?");
            $update->execute([$nueva, $row["id_item_carrito"]]);
        } else {
            // Obtener precio del producto
            $precio_stmt = $conn->prepare("SELECT precio_unitario FROM PRODUCTO WHERE id_producto = ?");
            $precio_stmt->execute([$id_producto]);
            $producto = $precio_stmt->fetch(PDO::FETCH_ASSOC);

            $insert = $conn->prepare("INSERT INTO ITEM_CARRITO (id_carrito, id_producto, cantidad, precio_unitario_momento) 
                                      VALUES (?, ?, ?, ?)");
            $insert->execute([$id_carrito, $id_producto, $cantidad, $producto["precio_unitario"]]);
        }
    }

    public static function obtenerItems($conn, $id_carrito) {
        $stmt = $conn->prepare("
            SELECT i.*, p.nombre_producto, p.url_imagen_principal
            FROM ITEM_CARRITO i
            JOIN PRODUCTO p ON i.id_producto = p.id_producto
            WHERE i.id_carrito = ?
        ");
        $stmt->execute([$id_carrito]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
