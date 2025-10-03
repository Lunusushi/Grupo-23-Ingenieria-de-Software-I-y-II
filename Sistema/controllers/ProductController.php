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

    public static function obtenerProductoById($conn, $id_producto) {
        $stmt = $conn->prepare("SELECT * FROM PRODUCTO WHERE id_producto = ?");
        $stmt->execute([$id_producto]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

    public static function editarProducto($conn, $id_producto, $nombre, $desc, $precio, $stock, $url, $id_categoria) {
        $stmt = $conn->prepare("UPDATE PRODUCTO SET
            nombre_producto = ?, descripcion = ?, precio_unitario = ?, stock_actual = ?, url_imagen_principal = ?, id_categoria = ?
            WHERE id_producto = ?");
        $stmt->execute([$nombre, $desc, $precio, $stock, $url, $id_categoria, $id_producto]);
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
    
    public static function obtenerDestacados(PDO $conn, int $limit = 5): array {
        $sql = "
        SELECT id_producto, nombre_producto, url_imagen_principal, precio_unitario
        FROM PRODUCTO
        WHERE activo = 1 AND (es_oferta = 1 OR es_popular = 1 OR es_nuevo = 1)
        ORDER BY es_oferta DESC, es_popular DESC, es_nuevo DESC, id_producto DESC
        LIMIT :lim
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function obtenerPromosHome(PDO $conn, int $limit = 5): array {
        $sql = "
        SELECT ph.id_promo, ph.titulo, ph.subtitulo, ph.imagen_url, ph.orden,
                p.id_producto, p.nombre_producto, p.url_imagen_principal, p.precio_unitario
        FROM PROMO_HOME ph
        JOIN PRODUCTO p ON p.id_producto = ph.id_producto
        WHERE ph.activo = 1 AND p.activo = 1
        ORDER BY ph.orden ASC, ph.id_promo DESC
        LIMIT :lim
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function listarPromosHome(PDO $conn): array {
        $sql = "
        SELECT ph.*, p.nombre_producto, p.url_imagen_principal
        FROM PROMO_HOME ph
        JOIN PRODUCTO p ON p.id_producto = ph.id_producto
        ORDER BY ph.orden ASC, ph.id_promo DESC
        ";
        return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function crearPromoHome(PDO $conn, int $id_producto, ?string $titulo, ?string $subtitulo, ?string $imagen_url, int $orden, bool $activo): void {
        $sql = "INSERT INTO PROMO_HOME (id_producto, titulo, subtitulo, imagen_url, orden, activo)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_producto, $titulo, $subtitulo, $imagen_url, $orden, $activo ? 1 : 0]);
    }

    public static function actualizarPromoHome(PDO $conn, int $id_promo, array $fields): void {
        $allowed = ['id_producto','titulo','subtitulo','imagen_url','orden','activo'];
        $sets = [];
        $args = [];
        foreach ($fields as $k => $v) {
            if (in_array($k, $allowed, true)) {
                $sets[] = "$k = ?";
                $args[] = $v;
            }
        }
        if (empty($sets)) return;
        $args[] = $id_promo;
        $sql = "UPDATE PROMO_HOME SET ".implode(', ', $sets)." WHERE id_promo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($args);
    }

    public static function eliminarPromoHome(PDO $conn, int $id_promo): void {
        $stmt = $conn->prepare("DELETE FROM PROMO_HOME WHERE id_promo = ?");
        $stmt->execute([$id_promo]);
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'buscar' && isset($_GET['q'])) {
    ProductController::buscarProductosJSON($conn, $_GET['q']);
}
?>
