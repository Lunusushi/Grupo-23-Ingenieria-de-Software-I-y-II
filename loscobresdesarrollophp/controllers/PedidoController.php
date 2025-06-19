<?php
require_once __DIR__ . '/../config/db.php';

class PedidoController {
    public static function realizarPedido($conn, $id_cliente, $id_carrito, $metodo_pago, $lugar_retiro) {
        // Obtener operador de prueba
        $id_operador = 1;

        // Crear pedido
        $stmt = $conn->prepare("INSERT INTO PEDIDO (id_cliente, id_carrito, metodo_pago, lugar_retiro, estado, codigo_verificacion, id_operador, id_op_registro, id_op_entrega)
                                VALUES (?, ?, ?, ?, 'pendiente', ?, ?, ?, ?)");
        $codigo = substr(md5(uniqid()), 0, 6);
        $stmt->execute([$id_cliente, $id_carrito, $metodo_pago, $lugar_retiro, $codigo, $id_operador, $id_operador, $id_operador]);

        $id_pedido = $conn->lastInsertId();

        // Obtener productos del carrito
        $stmt = $conn->prepare("SELECT * FROM ITEM_CARRITO WHERE id_carrito = ?");
        $stmt->execute([$id_carrito]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            // Insertar detalle
            $subtotal = $item["cantidad"] * $item["precio_unitario_momento"];
            $stmt = $conn->prepare("INSERT INTO DETALLE_PEDIDO (id_pedido, id_producto, cantidad, precio_unitario, subtotal)
                                    VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_pedido, $item["id_producto"], $item["cantidad"], $item["precio_unitario_momento"], $subtotal]);

            // CU21: actualizar stock
            $stmt = $conn->prepare("UPDATE PRODUCTO SET stock_actual = stock_actual - ? WHERE id_producto = ?");
            $stmt->execute([$item["cantidad"], $item["id_producto"]]);
        }

        // Limpiar carrito
        $conn->prepare("DELETE FROM ITEM_CARRITO WHERE id_carrito = ?")->execute([$id_carrito]);

        return $codigo;
    }

    public static function buscarPedidoPorCodigo($conn, $codigo) {
        $stmt = $conn->prepare("SELECT * FROM PEDIDO WHERE codigo_verificacion = ?");
        $stmt->execute([$codigo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function detallesPedido($conn, $id_pedido) {
        $stmt = $conn->prepare("
            SELECT d.*, p.nombre_producto
            FROM DETALLE_PEDIDO d
            JOIN PRODUCTO p ON d.id_producto = p.id_producto
            WHERE d.id_pedido = ?");
        $stmt->execute([$id_pedido]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
