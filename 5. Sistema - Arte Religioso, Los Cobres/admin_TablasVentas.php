<?php
    session_start();
    require_once 'config/MySqlDb.php'; // This file sets up $conn as PDO instance

    // Guard de acceso: sólo operadores con cargo
    $user     = $_SESSION['user'] ?? null;
    $userType = $user['type']   ?? ($_SESSION['user_type'] ?? null);
    $cargo    = $user['cargo']  ?? ($_SESSION['cargo'] ?? null);

    if ($userType !== 'operador' || !$cargo) {
    header('Location: login.php');
    exit;
    }

    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';

    // Validate and prepare date filters
    $dateFilter = '';
    $params = [];

    if ($start_date && $end_date) {
        $dateFilter = "AND p.fecha_pedido BETWEEN ? AND ?";
        $params[] = $start_date . " 00:00:00";
        $params[] = $end_date . " 23:59:59";
    } elseif ($start_date) {
        $dateFilter = "AND p.fecha_pedido >= ?";
        $params[] = $start_date . " 00:00:00";
    } elseif ($end_date) {
        $dateFilter = "AND p.fecha_pedido <= ?";
        $params[] = $end_date . " 23:59:59";
    }

    try {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) AS total_sales,
                COALESCE(SUM(dp.subtotal), 0) AS total_revenue
            FROM PEDIDO p
            LEFT JOIN DETALLE_PEDIDO dp ON p.id_pedido = dp.id_pedido
            WHERE p.estado = 'completado' $dateFilter
        ");
        $stmt->execute($params);
        $salesMetrics = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $salesMetrics = ['total_sales' => 0, 'total_revenue' => 0];
    }

    // Fetch top products by quantity sold (top 5)
    try {
        $stmt = $conn->prepare("
            SELECT p.id_producto, p.nombre_producto, COALESCE(SUM(dp.cantidad), 0) AS total_sold
            FROM DETALLE_PEDIDO dp
            JOIN PRODUCTO p ON dp.id_producto = p.id_producto
            GROUP BY p.id_producto, p.nombre_producto
            ORDER BY total_sold DESC
            LIMIT 5
        ");
        $stmt->execute();
        $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $topProducts = [];
    }

    // Fetch products with low stock (stock_actual < 10)
    try {
        $stmt = $conn->prepare("
            SELECT id_producto, nombre_producto, stock_actual
            FROM PRODUCTO
            WHERE stock_actual < 10
            ORDER BY stock_actual ASC
        ");
        $stmt->execute();
        $lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $lowStockProducts = [];
    }

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Auditoría de Ventas - Tablero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <?php include 'partials/admin_sidebar_open.php'; ?>
    <div class="container mt-4">
        <h1 class="mb-4">📈 Auditoría de Ventas - Tablero de Métricas</h1>

        <form method="GET" class="row g-3 mb-4">
            <div class="col-auto">
                <label for="start_date" class="col-form-label">Fecha inicio:</label>
            </div>
            <div class="col-auto">
                <input type="date" id="start_date" name="start_date" class="form-control"
                    value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>">
            </div>
            <div class="col-auto">
                <label for="end_date" class="col-form-label">Fecha fin:</label>
            </div>
            <div class="col-auto">
                <input type="date" id="end_date" name="end_date" class="form-control"
                    value="<?php echo htmlspecialchars($_GET['end_date'] ?? ''); ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>

        <div class="row mb-4">
            <div class="col-md-6 col-lg-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-header">Total de Ventas</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo number_format($salesMetrics['total_sales'] ?? 0); ?></h5>
                        <p class="card-text">Número de ventas completadas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header">Ingresos Totales</div>
                    <div class="card-body">
                        <h5 class="card-title">$<?php echo number_format($salesMetrics['total_revenue'] ?? 0, 2); ?></h5>
                        <p class="card-text">Ingresos generados por ventas</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-4">
                <h2>🏆 Top 5 Productos Más Vendidos</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>ID Producto</th>
                                <th>Nombre Producto</th>
                                <th>Cantidad Vendida</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['id_producto']); ?></td>
                                <td><?php echo htmlspecialchars($product['nombre_producto']); ?></td>
                                <td><?php echo htmlspecialchars($product['total_sold']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($topProducts)): ?>
                            <tr><td colspan="3" class="text-center">No hay datos disponibles</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <h2>⚠️ Productos con Stock Bajo (menos de 10)</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>ID Producto</th>
                                <th>Nombre Producto</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockProducts as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['id_producto']); ?></td>
                                <td><?php echo htmlspecialchars($product['nombre_producto']); ?></td>
                                <td><?php echo htmlspecialchars($product['stock_actual']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($lowStockProducts)): ?>
                            <tr><td colspan="3" class="text-center">No hay productos con stock bajo</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include 'partials/admin_sidebar_close.php'; ?>
</body>
</html>
