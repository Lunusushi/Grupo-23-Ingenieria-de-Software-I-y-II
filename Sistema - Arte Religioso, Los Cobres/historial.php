<?php
    // historial.php — Historial de pedidos + recompra (solo clientes)
    if (session_status() === PHP_SESSION_NONE) session_start();

    // Guard: solo clientes
    $userType = $_SESSION['user']['type'] ?? ($_SESSION['user_type'] ?? null);
    if (empty($_SESSION['user']) || $userType !== 'cliente') {
    header('Location: index.php'); exit;
    }

    require_once __DIR__ . '/config/MySqlDb.php';
    require_once __DIR__ . '/controllers/ClientController.php';
    require_once __DIR__ . '/partials/navbar.php';

    $usuarioId = (int)$_SESSION['user']['id'];

    // Resolver id_cliente a partir de id_usuario
    $stmt = $conn->prepare("SELECT id_cliente FROM CLIENTE WHERE id_usuario = ?");
    $stmt->execute([$usuarioId]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cliente) { http_response_code(403); die("No se encontró el cliente asociado a este usuario."); }
    $id_cliente = (int)$cliente['id_cliente'];

    $mensaje = "";
    $error   = "";

    // Recomprar: agrega todos los ítems del pedido al carrito actual
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'recomprar') {
    $id_pedido = (int)($_POST['id_pedido'] ?? 0);

    try {
        // Verifica que el pedido sea del cliente
        $chk = $conn->prepare("SELECT id_pedido FROM PEDIDO WHERE id_pedido=? AND id_cliente=?");
        $chk->execute([$id_pedido, $id_cliente]);
        if (!$chk->fetch()) {
        throw new RuntimeException("Pedido no encontrado.");
        }

        // Traer items del pedido + estado actual del producto
        $q = $conn->prepare("
        SELECT dp.id_producto, dp.cantidad, p.activo, p.stock_actual, p.nombre_producto
        FROM DETALLE_PEDIDO dp
        JOIN PRODUCTO p ON p.id_producto = dp.id_producto
        WHERE dp.id_pedido = ?
        ");
        $q->execute([$id_pedido]);
        $itemsPedido = $q->fetchAll(PDO::FETCH_ASSOC);

        // Obtener/crear carrito del cliente (usa universal para compatibilidad)
        // Si no tienes obtenerCarritoUniversal, cambia a: ClientController::obtenerCarrito($conn, $id_cliente)
        $carrito = ClientController::obtenerCarritoUniversal($conn, $usuarioId);
        if (!$carrito) throw new RuntimeException("No se pudo obtener el carrito.");
        $id_carrito = (int)$carrito['id_carrito'];

        $agregados = 0; $omitidos = [];
        foreach ($itemsPedido as $row) {
        $idProd  = (int)$row['id_producto'];
        $cantPed = (float)$row['cantidad'];
        $activo  = (int)$row['activo'] === 1;
        $stock   = (float)$row['stock_actual'];
        $nombre  = $row['nombre_producto'];

        if (!$activo || $stock <= 0) {
            $omitidos[] = "$nombre (sin stock o inactivo)";
            continue;
        }
        // Cantidad a agregar = min(cantidad pedida, stock actual) — entera
        $cantAdd = max(0, min((int)$cantPed, (int)$stock));
        if ($cantAdd <= 0) {
            $omitidos[] = "$nombre (stock insuficiente)";
            continue;
        }

        ClientController::agregarProducto($conn, $id_carrito, $idProd, $cantAdd);
        $agregados++;
        }

        $mensaje = "✅ Se agregaron {$agregados} producto(s) al carrito.";
        if (!empty($omitidos)) {
        $mensaje .= " Omitidos: " . htmlspecialchars(implode(', ', $omitidos));
        }

    } catch (Throwable $e) {
        $error = "❌ " . $e->getMessage();
    }
    }

    // Listar pedidos del cliente (con total e ítems)
    $sql = "
        SELECT pe.id_pedido, pe.fecha_pedido, pe.estado, pe.codigo_verificacion,
                COALESCE(SUM(dp.subtotal),0) AS total,
                COUNT(dp.id_detalle_pedido)   AS items
        FROM PEDIDO pe
        LEFT JOIN DETALLE_PEDIDO dp ON dp.id_pedido = pe.id_pedido
        WHERE pe.id_cliente = ?
        GROUP BY pe.id_pedido
        ORDER BY pe.fecha_pedido DESC, pe.id_pedido DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_cliente]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Función para traer detalles de un pedido
    function detallesPedido(PDO $conn, int $id_pedido): array {
    $s = $conn->prepare("
        SELECT dp.id_detalle_pedido, dp.id_producto, dp.cantidad, dp.precio_unitario, dp.subtotal,
            p.nombre_producto, p.url_imagen_principal
        FROM DETALLE_PEDIDO dp
        JOIN PRODUCTO p ON p.id_producto = dp.id_producto
        WHERE dp.id_pedido = ?
    ");
    $s->execute([$id_pedido]);
    return $s->fetchAll(PDO::FETCH_ASSOC);
    }
?>

<!DOCTYPE html>
<html lang="es">
    <head>
    <meta charset="UTF-8">
    <title>Historial de pedidos | Los Cobres</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .code-chip {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        background: #f5f5f5;
        border: 1px solid #e0e0e0;
        padding: .25rem .5rem;
        border-radius: .375rem;
        display: inline-block;
        }
    </style>
    </head>
    <body class="d-flex flex-column min-vh-100">
    <main class="flex-grow-1">

    <div class="container my-4">
    <h1 class="h4 mb-3">🧾 Historial de pedidos</h1>

    <?php if ($mensaje): ?><div class="alert alert-success"><?= $mensaje ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <?php if (empty($pedidos)): ?>
        <div class="alert alert-info">Aún no tienes pedidos.</div>
        <a href="catalogo.php" class="btn btn-primary">Ir al catálogo</a>
    <?php else: ?>

        <div class="list-group">
        <?php foreach ($pedidos as $pe):
            $idp   = (int)$pe['id_pedido'];
            $fecha = htmlspecialchars($pe['fecha_pedido']);
            $estado= htmlspecialchars($pe['estado'] ?? 'pendiente');
            $total = number_format((float)$pe['total'], 0, ',', '.');
            $count = (int)$pe['items'];
            $codigo  = $pe['codigo_verificacion'] ? htmlspecialchars($pe['codigo_verificacion']) : '—';
        ?>
            <div class="list-group-item">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                <div class="fw-semibold">Pedido #<?= $idp ?></div>
                <div class="text-muted small"><?= $fecha ?> · <?= $count ?> ítem<?= $count===1?'':'es' ?></div>
                <div class="mt-1"><span class="badge bg-secondary"><?= $estado ?></span></div>
                </div>
                <div class="mt-2">
                    <span class="text-muted small d-block mb-1">Código de retiro</span>
                    <span class="code-chip me-2" id="code-<?= $idp ?>"><?= $codigo ?></span>
                    <?php if ($pe['codigo_verificacion']): ?>
                    <button class="btn btn-outline-dark btn-sm copy-btn" data-target="code-<?= $idp ?>">Copiar</button>
                    <?php endif; ?>
                </div>
                <div class="text-end">
                <div class="fs-5 fw-semibold">$<?= $total ?></div>
                <div class="mt-2 d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-primary btn-sm"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#detalle-<?= $idp ?>">
                    Ver detalle
                    </button>
                    <form method="POST">
                    <input type="hidden" name="action" value="recomprar">
                    <input type="hidden" name="id_pedido" value="<?= $idp ?>">
                    <button class="btn btn-success btn-sm" type="submit">Recomprar todo</button>
                    </form>
                </div>
                </div>
            </div>

            <!-- Detalle -->
            <div class="collapse mt-3" id="detalle-<?= $idp ?>">
                <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th style="width:64px;">Imagen</th>
                        <th>Producto</th>
                        <th class="text-end">Precio</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $dets = detallesPedido($conn, $idp);
                    foreach ($dets as $d):
                        $precio   = number_format((float)$d['precio_unitario'], 0, ',', '.');
                        $cantidad = (int)$d['cantidad'];
                        $sub      = number_format((float)$d['subtotal'], 0, ',', '.');
                    ?>
                    <tr>
                        <td>
                        <?php if (!empty($d['url_imagen_principal'])): ?>
                            <img src="<?= htmlspecialchars($d['url_imagen_principal']) ?>"
                                alt="" style="width:64px;height:64px;object-fit:cover" class="rounded">
                        <?php endif; ?>
                        </td>
                        <td>
                        <a class="text-decoration-none"
                            href="producto.php?id=<?= (int)$d['id_producto'] ?>">
                            <?= htmlspecialchars($d['nombre_producto']) ?>
                        </a>
                        </td>
                        <td class="text-end">$<?= $precio ?></td>
                        <td class="text-end"><?= $cantidad ?></td>
                        <td class="text-end">$<?= $sub ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <a href="carrito.php" class="btn btn-outline-secondary btn-sm">Ir al carrito</a>
            </div>
            </div>
        <?php endforeach; ?>
        </div>

    <?php endif; ?>
    </div>
    <script>
    // Copiar código de retiro al portapapeles
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-target');
        const el = document.getElementById(targetId);
        if (!el) return;
        const text = el.textContent.trim();
        navigator.clipboard.writeText(text).then(() => {
            // feedback simple
            const original = btn.textContent;
            btn.textContent = '¡Copiado!';
            setTimeout(() => btn.textContent = original, 1200);
        });
        });
    });
    </script>
    </main>
    <?php include __DIR__ . '/partials/footer.php'; ?>
    </body>
</html>