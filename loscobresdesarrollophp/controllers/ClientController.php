<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class ClientController {
    // CarritoController methods
    public static function obtenerCarrito($conn, $id_cliente) {
        // Check if user is an operator
        $checkOperador = $conn->prepare("SELECT cargo FROM OPERADOR WHERE id_usuario = ?");
        $checkOperador->execute([$id_cliente]);
        $operador = $checkOperador->fetch(PDO::FETCH_ASSOC);

        if ($operador) {
            // For operator users, return empty cart or handle accordingly
            return null;
        }

        // Check if id_cliente exists in CLIENTE table
        $checkCliente = $conn->prepare("SELECT id_cliente FROM CLIENTE WHERE id_cliente = ?");
        $checkCliente->execute([$id_cliente]);
        if (!$checkCliente->fetch()) {
            throw new Exception("Cliente con id_cliente $id_cliente no existe en la tabla CLIENTE.");
        }

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
        $check = $conn->prepare("SELECT * FROM ITEM_CARRITO WHERE id_carrito = ? AND id_producto = ?");
        $check->execute([$id_carrito, $id_producto]);

        if ($row = $check->fetch(PDO::FETCH_ASSOC)) {
            $nueva = $row["cantidad"] + $cantidad;
            $update = $conn->prepare("UPDATE ITEM_CARRITO SET cantidad = ? WHERE id_item_carrito = ?");
            $update->execute([$nueva, $row["id_item_carrito"]]);
        } else {
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

    // FavoritosController methods
    public static function obtenerLista($conn, $id_cliente) {
        // Check if user is an operator
        $checkOperador = $conn->prepare("SELECT cargo FROM OPERADOR WHERE id_usuario = ?");
        $checkOperador->execute([$id_cliente]);
        $operador = $checkOperador->fetch(PDO::FETCH_ASSOC);

        if ($operador) {
            // For operator users, return empty favorites list or handle accordingly
            return null;
        }

        $stmt = $conn->prepare("SELECT * FROM LISTA_FAVORITOS WHERE id_cliente = ?");
        $stmt->execute([$id_cliente]);
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lista) {
            $stmt = $conn->prepare("INSERT INTO LISTA_FAVORITOS (id_cliente, nombre_lista) VALUES (?, 'Favoritos')");
            $stmt->execute([$id_cliente]);
            $id = $conn->lastInsertId();
            $stmt = $conn->prepare("SELECT * FROM LISTA_FAVORITOS WHERE id_lista = ?");
            $stmt->execute([$id]);
            $lista = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $lista;
    }

    public static function agregarFavorito($conn, $id_lista, $id_producto) {
        $check = $conn->prepare("SELECT COUNT(*) as total FROM ITEM_FAVORITO WHERE id_lista = ?");
        $check->execute([$id_lista]);
        $total = $check->fetchColumn();

        if ($total >= 30) {
            return "❌ Límite de 30 favoritos alcanzado.";
        }

        $check2 = $conn->prepare("SELECT * FROM ITEM_FAVORITO WHERE id_lista = ? AND id_producto = ?");
        $check2->execute([$id_lista, $id_producto]);
        if ($check2->fetch()) {
            return "⚠️ El producto ya está en favoritos.";
        }

        $stmt = $conn->prepare("INSERT INTO ITEM_FAVORITO (id_lista, id_producto, fecha_agregado) VALUES (?, ?, NOW())");
        $stmt->execute([$id_lista, $id_producto]);
        return "✅ Producto agregado a favoritos.";
    }

    public static function obtenerFavoritos($conn, $id_lista) {
        $stmt = $conn->prepare("
            SELECT i.*, p.nombre_producto, p.url_imagen_principal
            FROM ITEM_FAVORITO i
            JOIN PRODUCTO p ON i.id_producto = p.id_producto
            WHERE i.id_lista = ?
        ");
        $stmt->execute([$id_lista]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // PedidoController methods
    public static function realizarPedido($conn, $id_cliente, $id_carrito, $metodo_pago, $lugar_retiro) {
        // Check if user is an operator
        $checkOperador = $conn->prepare("SELECT cargo FROM OPERADOR WHERE id_usuario = ?");
        $checkOperador->execute([$id_cliente]);
        $operador = $checkOperador->fetch(PDO::FETCH_ASSOC);

        if ($operador) {
            // For operator users, disallow placing orders or handle accordingly
            throw new Exception("Operadores no pueden realizar pedidos.");
        }

        $id_operador = 1;

        $stmt = $conn->prepare("INSERT INTO PEDIDO (id_cliente, id_carrito, metodo_pago, lugar_retiro, estado, codigo_verificacion, id_operador, id_op_registro, id_op_entrega)
                                VALUES (?, ?, ?, ?, 'pendiente', ?, ?, ?, ?)");
        $codigo = substr(md5(uniqid()), 0, 6);
        $stmt->execute([$id_cliente, $id_carrito, $metodo_pago, $lugar_retiro, $codigo, $id_operador, $id_operador, $id_operador]);

        $id_pedido = $conn->lastInsertId();

        $stmt = $conn->prepare("SELECT * FROM ITEM_CARRITO WHERE id_carrito = ?");
        $stmt->execute([$id_carrito]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $subtotal = $item["cantidad"] * $item["precio_unitario_momento"];
            $stmt = $conn->prepare("INSERT INTO DETALLE_PEDIDO (id_pedido, id_producto, cantidad, precio_unitario, subtotal)
                                    VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_pedido, $item["id_producto"], $item["cantidad"], $item["precio_unitario_momento"], $subtotal]);

            $stmt = $conn->prepare("UPDATE PRODUCTO SET stock_actual = stock_actual - ? WHERE id_producto = ?");
            $stmt->execute([$item["cantidad"], $item["id_producto"]]);
        }

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
