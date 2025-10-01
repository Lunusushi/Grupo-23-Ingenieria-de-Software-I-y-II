<?php
require_once __DIR__ . '/../config/MySqlDb.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function ensureSession() {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function getGuestToken(): string {
    ensureSession();
    if (empty($_SESSION['guest_token'])) {
        $_SESSION['guest_token'] = bin2hex(random_bytes(16)); // 32 chars
    }
    return $_SESSION['guest_token'];
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
            throw new Exception("Operadores no pueden realizar pedidos.");
        }

        // Obtener id_operador, que es la PK de la tabla OPERADOR
        $stmt = $conn->query("SELECT id_operador FROM OPERADOR LIMIT 1");
        $op = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$op) {
            throw new Exception("No hay operadores disponibles para asignar el pedido.");
        }
        $id_operador = $op["id_operador"];

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

    public static function obtenerCarritoUniversal(PDO $conn, ?int $id_usuario): ?array {
        ensureSession();

        if ($id_usuario !== null) {
            // Operador no usa carrito
            $checkOperador = $conn->prepare("SELECT cargo FROM OPERADOR WHERE id_usuario = ?");
            $checkOperador->execute([$id_usuario]);
            if ($checkOperador->fetch(PDO::FETCH_ASSOC)) {
                return null;
            }

            // Debe existir como CLIENTE (tu modelo actual mapea id_usuario == id_cliente)
            $checkCliente = $conn->prepare("SELECT id_cliente FROM CLIENTE WHERE id_cliente = ?");
            $checkCliente->execute([$id_usuario]);
            if (!$checkCliente->fetch()) {
                throw new Exception("Cliente con id_cliente $id_usuario no existe en CLIENTE.");
            }

            // Buscar/crear carrito por id_cliente
            $stmt = $conn->prepare("SELECT * FROM CARRITO_COMPRA WHERE id_cliente = ?");
            $stmt->execute([$id_usuario]);
            $carrito = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$carrito) {
                $conn->prepare("INSERT INTO CARRITO_COMPRA (id_cliente) VALUES (?)")->execute([$id_usuario]);
                $id = $conn->lastInsertId();
                $stmt = $conn->prepare("SELECT * FROM CARRITO_COMPRA WHERE id_carrito = ?");
                $stmt->execute([$id]);
                $carrito = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return $carrito;
        }

        // Invitado (guest) por token de sesión
        $token = getGuestToken();
        $stmt = $conn->prepare("SELECT * FROM CARRITO_COMPRA WHERE session_token = ?");
        $stmt->execute([$token]);
        $carrito = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$carrito) {
            $ins = $conn->prepare("INSERT INTO CARRITO_COMPRA (session_token) VALUES (?)");
            $ins->execute([$token]);
            $id = $conn->lastInsertId();
            $stmt = $conn->prepare("SELECT * FROM CARRITO_COMPRA WHERE id_carrito = ?");
            $stmt->execute([$id]);
            $carrito = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $carrito;
    }

    public static function fusionarCarritoInvitadoConCliente(PDO $conn, int $id_usuario_cliente): void {
        ensureSession();
        if (empty($_SESSION['guest_token'])) return;
        $token = $_SESSION['guest_token'];

        // Carrito del invitado
        $stmt = $conn->prepare("SELECT * FROM CARRITO_COMPRA WHERE session_token = ?");
        $stmt->execute([$token]);
        $carritoGuest = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$carritoGuest) return;

        // Carrito del cliente (crear si no existe)
        $carritoCliente = self::obtenerCarritoUniversal($conn, $id_usuario_cliente);

        // Items del invitado
        $items = $conn->prepare("SELECT * FROM ITEM_CARRITO WHERE id_carrito = ?");
        $items->execute([$carritoGuest['id_carrito']]);
        $items = $items->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $it) {
            // ¿ya existe ese producto en el carrito del cliente?
            $q = $conn->prepare("SELECT * FROM ITEM_CARRITO WHERE id_carrito = ? AND id_producto = ?");
            $q->execute([$carritoCliente['id_carrito'], $it['id_producto']]);
            $existente = $q->fetch(PDO::FETCH_ASSOC);

            if ($existente) {
                $nueva = $existente['cantidad'] + $it['cantidad'];
                $up = $conn->prepare("UPDATE ITEM_CARRITO SET cantidad = ? WHERE id_item_carrito = ?");
                $up->execute([$nueva, $existente['id_item_carrito']]);
            } else {
                $ins = $conn->prepare("INSERT INTO ITEM_CARRITO (id_carrito, id_producto, cantidad, precio_unitario_momento)
                                    VALUES (?, ?, ?, ?)");
                $ins->execute([$carritoCliente['id_carrito'], $it['id_producto'], $it['cantidad'], $it['precio_unitario_momento']]);
            }
        }

        // Limpiar carrito invitado
        $conn->prepare("DELETE FROM ITEM_CARRITO WHERE id_carrito = ?")->execute([$carritoGuest['id_carrito']]);
        $conn->prepare("DELETE FROM CARRITO_COMPRA WHERE id_carrito = ?")->execute([$carritoGuest['id_carrito']]);

        // borrar token
        unset($_SESSION['guest_token']);
    }

    public static function realizarPedidoInvitado(PDO $conn, int $id_carrito, string $nombre, string $email, ?string $telefono, string $metodo_pago, string $lugar_retiro): string {
        // Asignar un operador “de turno”
        $op = $conn->query("SELECT id_operador FROM OPERADOR LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$op) throw new Exception("No hay operadores disponibles.");

        $conn->beginTransaction();
        try {
            $codigo = substr(md5(uniqid('', true)), 0, 6);

            $stmt = $conn->prepare("
            INSERT INTO PEDIDO (id_cliente, id_carrito, estado, codigo_verificacion, lugar_retiro, metodo_pago,
                                id_operador, id_op_registro, id_op_entrega,
                                guest_nombre, guest_email, guest_telefono)
            VALUES (NULL, ?, 'pendiente', ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id_carrito, $codigo, $lugar_retiro, $metodo_pago,
                            $op['id_operador'], $op['id_operador'], $op['id_operador'],
                            $nombre, $email, $telefono]);

            $id_pedido = $conn->lastInsertId();

            // Items → detalle + descontar stock
            $it = $conn->prepare("SELECT * FROM ITEM_CARRITO WHERE id_carrito = ?");
            $it->execute([$id_carrito]);
            $items = $it->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                $subtotal = $item['cantidad'] * $item['precio_unitario_momento'];
                $conn->prepare("INSERT INTO DETALLE_PEDIDO (id_pedido, id_producto, cantidad, precio_unitario, subtotal)
                                VALUES (?, ?, ?, ?, ?)")
                    ->execute([$id_pedido, $item['id_producto'], $item['cantidad'],
                                $item['precio_unitario_momento'], $subtotal]);

                $conn->prepare("UPDATE PRODUCTO SET stock_actual = stock_actual - ? WHERE id_producto = ?")
                    ->execute([$item['cantidad'], $item['id_producto']]);
            }

            // Vaciar carrito
            $conn->prepare("DELETE FROM ITEM_CARRITO WHERE id_carrito = ?")->execute([$id_carrito]);

            $conn->commit();
            return $codigo;
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public static function eliminarItem(PDO $conn, int $id_carrito, int $id_item_carrito): bool {
        $stmt = $conn->prepare("DELETE FROM ITEM_CARRITO WHERE id_item_carrito = ? AND id_carrito = ?");
        return $stmt->execute([$id_item_carrito, $id_carrito]);
    }

    public static function actualizarCantidad(PDO $conn, int $id_carrito, int $id_item_carrito, float $cantidad): bool {
        // Si la cantidad es <= 0, mejor eliminamos el ítem
        if ($cantidad <= 0) {
            return self::eliminarItem($conn, $id_carrito, $id_item_carrito);
        }
        $stmt = $conn->prepare("UPDATE ITEM_CARRITO SET cantidad = ? WHERE id_item_carrito = ? AND id_carrito = ?");
        return $stmt->execute([$cantidad, $id_item_carrito, $id_carrito]);
    }

    public static function decrementarCantidad(PDO $conn, int $id_carrito, int $id_item_carrito, float $cantidad_a_quitar): bool {
        // Traer cantidad actual y verificar pertenencia al carrito
        $q = $conn->prepare("SELECT cantidad FROM ITEM_CARRITO WHERE id_item_carrito = ? AND id_carrito = ?");
        $q->execute([$id_item_carrito, $id_carrito]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        $actual = (float)$row['cantidad'];
        if ($cantidad_a_quitar <= 0) return true; // nada que hacer

        // Si pide quitar más o igual a lo que hay, se elimina el ítem
        if ($cantidad_a_quitar >= $actual) {
            return self::eliminarItem($conn, $id_carrito, $id_item_carrito);
        }

        $nueva = $actual - $cantidad_a_quitar;
        return self::actualizarCantidad($conn, $id_carrito, $id_item_carrito, $nueva);
    }
}
?>
