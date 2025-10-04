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

        // Check if id_cliente exists in CLIENTE table
        $checkCliente = $conn->prepare("SELECT id_cliente FROM CLIENTE WHERE id_cliente = ?");
        $checkCliente->execute([$id_cliente]);
        if (!$checkCliente->fetch()) {
            return null; // Cliente no existe
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

    public static function obtenerFavoritos($conn, $id_lista, ?string $q = null, ?string $sort = null, ?float $minPrice = null, ?float $maxPrice = null) {
        $sql = "
            SELECT i.*, p.nombre_producto, p.url_imagen_principal, p.precio_unitario, i.fecha_agregado
            FROM ITEM_FAVORITO i
            JOIN PRODUCTO p ON i.id_producto = p.id_producto
            WHERE i.id_lista = ?
        ";
        $params = [$id_lista];

        if ($q !== null && $q !== '') {
            $sql .= " AND p.nombre_producto LIKE ?";
            $params[] = '%' . $q . '%';
        }

        if ($minPrice !== null) {
            $sql .= " AND p.precio_unitario >= ?";
            $params[] = $minPrice;
        }

        if ($maxPrice !== null) {
            $sql .= " AND p.precio_unitario <= ?";
            $params[] = $maxPrice;
        }

        $orderBy = "i.fecha_agregado DESC"; // default: most recent
        if ($sort === 'nombre_asc') {
            $orderBy = "p.nombre_producto ASC";
        } elseif ($sort === 'nombre_desc') {
            $orderBy = "p.nombre_producto DESC";
        } elseif ($sort === 'precio_asc') {
            $orderBy = "p.precio_unitario ASC";
        } elseif ($sort === 'precio_desc') {
            $orderBy = "p.precio_unitario DESC";
        } elseif ($sort === 'recientes') {
            $orderBy = "i.fecha_agregado DESC";
        }

        $sql .= " ORDER BY $orderBy";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function eliminarFavorito($conn, $id_lista, $id_producto) {
        $stmt = $conn->prepare("DELETE FROM ITEM_FAVORITO WHERE id_lista = ? AND id_producto = ?");
        $stmt->execute([$id_lista, $id_producto]);
        return $stmt->rowCount() > 0;
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

    public static function actualizarEstadoPedido(PDO $conn, int $id_pedido, string $nuevo_estado): void {
        $stmt = $conn->prepare("UPDATE PEDIDO SET estado = ? WHERE id_pedido = ?");
        $stmt->execute([$nuevo_estado, $id_pedido]);
    }

    public static function obtenerPedidosPendientes(PDO $conn): array {
        $stmt = $conn->query("
            SELECT p.id_pedido, p.fecha_pedido, p.estado, p.codigo_verificacion, p.metodo_pago, p.lugar_retiro,
                   COALESCE(u.nombre, p.guest_nombre) AS cliente_nombre,
                   COALESCE(u.apellido, '') AS cliente_apellido,
                   COALESCE(SUM(dp.subtotal), 0) AS total
            FROM PEDIDO p
            LEFT JOIN CLIENTE c ON p.id_cliente = c.id_cliente
            LEFT JOIN USUARIO u ON c.id_usuario = u.id_usuario
            LEFT JOIN DETALLE_PEDIDO dp ON p.id_pedido = dp.id_pedido
            WHERE p.estado = 'pendiente'
            GROUP BY p.id_pedido
            ORDER BY p.fecha_pedido DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public static function fusionarCarritoInvitadoConCliente(PDO $conn, int $id_usuario): void {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $guestToken = $_SESSION['guest_token'] ?? null;
        if (!$guestToken) return;

        // 1) Resolver id_cliente desde id_usuario
        $stmt = $conn->prepare("SELECT id_cliente FROM CLIENTE WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { unset($_SESSION['guest_token']); return; }
        $id_cliente = (int)$row['id_cliente'];

        $conn->beginTransaction();
        try {
            // 2) Carrito invitado por token
            $stmt = $conn->prepare("SELECT * FROM CARRITO_COMPRA WHERE session_token = ? LIMIT 1");
            $stmt->execute([$guestToken]);
            $guestCart = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$guestCart) { $conn->commit(); unset($_SESSION['guest_token']); return; }

            $guestId = (int)$guestCart['id_carrito'];

            // 3) ¿Cliente ya tiene carrito?
            $stmt = $conn->prepare("SELECT * FROM CARRITO_COMPRA WHERE id_cliente = ? ORDER BY id_carrito DESC LIMIT 1");
            $stmt->execute([$id_cliente]);
            $clientCart = $stmt->fetch(PDO::FETCH_ASSOC);
            $clientId   = $clientCart ? (int)$clientCart['id_carrito'] : null;

            // 4) ¿El carrito invitado está referenciado por algún pedido?
            $stmt = $conn->prepare("SELECT COUNT(*) FROM PEDIDO WHERE id_carrito = ?");
            $stmt->execute([$guestId]);
            $hasPedido = (int)$stmt->fetchColumn() > 0;

            // Helper: mover items de un carrito a otro (sumando cantidades si ya existe el producto)
            $moveItems = function(int $fromId, int $toId) use ($conn) {
                // Traer items del origen
                $s = $conn->prepare("SELECT id_producto, cantidad, precio_unitario_momento FROM ITEM_CARRITO WHERE id_carrito = ?");
                $s->execute([$fromId]);
                $items = $s->fetchAll(PDO::FETCH_ASSOC);

                foreach ($items as $it) {
                    $idp   = (int)$it['id_producto'];
                    $cant  = (float)$it['cantidad'];
                    $ppu   = (float)$it['precio_unitario_momento']; // mantenemos precio capturado

                    // ¿Existe ya en destino?
                    $c = $conn->prepare("SELECT id_item_carrito, cantidad FROM ITEM_CARRITO WHERE id_carrito=? AND id_producto=?");
                    $c->execute([$toId, $idp]);
                    if ($row = $c->fetch(PDO::FETCH_ASSOC)) {
                        $nuevaCant = (float)$row['cantidad'] + $cant;
                        $u = $conn->prepare("UPDATE ITEM_CARRITO SET cantidad=? WHERE id_item_carrito=?");
                        $u->execute([$nuevaCant, (int)$row['id_item_carrito']]);
                    } else {
                        $ins = $conn->prepare("INSERT INTO ITEM_CARRITO (id_carrito, id_producto, cantidad, precio_unitario_momento) VALUES (?,?,?,?)");
                        $ins->execute([$toId, $idp, $cant, $ppu]);
                    }
                }
            };

            if ($hasPedido) {
                // Caso A: carrito invitado usado en pedidos -> NO borrar ni reasignar
                if ($clientId === null) {
                    $ins = $conn->prepare("INSERT INTO CARRITO_COMPRA (id_cliente) VALUES (?)");
                    $ins->execute([$id_cliente]);
                    $clientId = (int)$conn->lastInsertId();
                }

                // mover items (si hubiera) y limpiar el carrito invitado
                $moveItems($guestId, $clientId);

                // borrar items del carrito invitado (la fila del carrito se conserva por FK con PEDIDO)
                $delItems = $conn->prepare("DELETE FROM ITEM_CARRITO WHERE id_carrito = ?");
                $delItems->execute([$guestId]);

                // desvincular token del carrito invitado
                $upd = $conn->prepare("UPDATE CARRITO_COMPRA SET session_token = NULL WHERE id_carrito = ?");
                $upd->execute([$guestId]);

            } else {
                // Caso B: carrito invitado sin pedidos asociados -> podemos fusionar/borrar o reasignar
                if ($clientId !== null && $clientId !== $guestId) {
                    // fusionar items al carrito del cliente y borrar carrito invitado
                    $moveItems($guestId, $clientId);

                    $delItems = $conn->prepare("DELETE FROM ITEM_CARRITO WHERE id_carrito = ?");
                    $delItems->execute([$guestId]);

                    $delCart = $conn->prepare("DELETE FROM CARRITO_COMPRA WHERE id_carrito = ?");
                    $delCart->execute([$guestId]);

                } else {
                    // El cliente no tiene carrito: reasignar el carrito invitado al cliente
                    $upd = $conn->prepare("UPDATE CARRITO_COMPRA SET id_cliente = ?, session_token = NULL WHERE id_carrito = ?");
                    $upd->execute([$id_cliente, $guestId]);
                }
            }

            $conn->commit();
            unset($_SESSION['guest_token']);
        } catch (\Throwable $e) {
            $conn->rollBack();
            // Repropaga para que lo veas en logs si algo raro pasa
            throw $e;
        }
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
