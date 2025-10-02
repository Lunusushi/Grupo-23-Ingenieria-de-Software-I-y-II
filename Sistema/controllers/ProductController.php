<?php
require_once __DIR__ . '/../config/MySqlDb.php';

class ProductController {
    public static function obtenerProductos($conn, $id_categorias = []) {
        if (empty($id_categorias)) {
            $stmt = $conn->query("SELECT * FROM PRODUCTO WHERE activo = 1");
        } else {
            $in = implode(',', array_fill(0, count($id_categorias), '?'));
            $stmt = $conn->prepare("SELECT * FROM PRODUCTO WHERE activo = 1 AND id_categoria IN ($in)");
            $stmt->execute($id_categorias);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerCategorias($conn) {
        $stmt = $conn->query("SELECT * FROM CATEGORIA");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function agregarProducto($conn, $nombre, $desc, $precio, $stock, $url, $id_categoria) {
        $stmt = $conn->prepare("INSERT INTO PRODUCTO 
            (nombre_producto, descripcion, precio_unitario, stock_actual, url_imagen_principal, activo, id_categoria)
            VALUES (?, ?, ?, ?, ?, 1, ?)");
        $stmt->execute([$nombre, $desc, $precio, $stock, $url, $id_categoria]);
    }

    public static function eliminarProducto($conn, $id_producto) {
        try {
            $stmt = $conn->prepare("DELETE FROM PRODUCTO WHERE id_producto = ?");
            $stmt->execute([$id_producto]);
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return "⚠️ No se puede eliminar el producto porque está asociado a pedidos existentes.";
            } else {
                throw $e;
            }
        }
    }

    public static function buscarProductos($conn, $keyword) {
        $stmt = $conn->prepare("SELECT id_producto, nombre_producto, url_imagen_principal 
                                FROM PRODUCTO 
                                WHERE activo = 1 AND nombre_producto LIKE ?
                                LIMIT 10"); //esta cosa ve el limite
        $stmt->execute(['%' . $keyword . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function buscarProductosJSON($conn, $keyword) {
        $productos = self::buscarProductos($conn, $keyword);
        header('Content-Type: application/json');
        echo json_encode($productos);
        exit;
    }

    public static function listarPaginado(PDO $conn, array $f = [], int $page = 1, int $per = 12, string $sort = 'recientes'): array
    {
        $page = max(1, $page);
        $per  = max(1, min(60, $per));
        $validSort = ['recientes','precio_asc','precio_desc','nombre'];
        if (!in_array($sort, $validSort, true)) $sort = 'recientes';

        $where = ['p.activo = 1'];
        $args  = [];

        // Categorías
        if (!empty($f['cat']) && is_array($f['cat'])) {
            $cats = array_values(array_filter($f['cat'], fn($v) => ctype_digit((string)$v)));
            if (!empty($cats)) {
                $in = implode(',', array_fill(0, count($cats), '?'));
                $where[] = "p.id_categoria IN ($in)";
                $args = array_merge($args, $cats);
            }
        }

        // Rango de precio
        if (isset($f['min']) && is_numeric($f['min'])) {
            $where[] = "p.precio_unitario >= ?";
            $args[]  = (float)$f['min'];
        }
        if (isset($f['max']) && is_numeric($f['max'])) {
            $where[] = "p.precio_unitario <= ?";
            $args[]  = (float)$f['max'];
        }

        // Sólo disponibles (opcional, si lo estás pasando)
        if (!empty($f['solo_disponibles'])) {
            $where[] = "p.stock_actual > 0";
        }

        // Búsqueda por keyword (nombre o descripción)
        if (!empty($f['q'])) {
            $q = trim((string)$f['q']);
            if ($q !== '') {
                $like = '%' . $q . '%';
                $where[] = "(p.nombre_producto LIKE ? OR p.descripcion LIKE ?)";
                $args[]  = $like;
                $args[]  = $like;
            }
        }

        // Orden
        $orderBy = "p.id_producto DESC";
        if ($sort === 'precio_asc')  $orderBy = "p.precio_unitario ASC, p.id_producto DESC";
        if ($sort === 'precio_desc') $orderBy = "p.precio_unitario DESC, p.id_producto DESC";
        if ($sort === 'nombre')      $orderBy = "p.nombre_producto ASC, p.id_producto DESC";

        $offset   = ($page - 1) * $per;
        $whereSql = $where ? implode(' AND ', $where) : '1';

        // Total
        $sqlCount = "SELECT COUNT(*) FROM PRODUCTO p WHERE $whereSql";
        $stmt = $conn->prepare($sqlCount);
        $stmt->execute($args);
        $total = (int)$stmt->fetchColumn();

        // Items (NO mezclar placeholders: inyecta per/offset como enteros)
        $perInt    = (int)$per;
        $offsetInt = (int)$offset;

        $sqlItems = "
            SELECT p.id_producto, p.nombre_producto, p.descripcion, p.precio_unitario,
                p.stock_actual, p.url_imagen_principal, p.id_categoria, p.activo,
                p.es_nuevo, p.es_oferta, p.es_popular
            FROM PRODUCTO p
            WHERE $whereSql
            ORDER BY $orderBy
            LIMIT $perInt OFFSET $offsetInt
        ";
        $stmt = $conn->prepare($sqlItems);
        $stmt->execute($args); // Sólo los ? del WHERE

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'total' => $total,
            'page'  => $page,
            'per'   => $per,
        ];
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'buscar' && isset($_GET['q'])) {
    ProductController::buscarProductosJSON($conn, $_GET['q']);
}
?>
