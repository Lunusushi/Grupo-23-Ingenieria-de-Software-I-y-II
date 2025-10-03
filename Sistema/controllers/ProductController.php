<?php
require_once __DIR__ . '/../config/MySqlDb.php';

class ProductController {
    public static function obtenerProductos($conn, $id_categorias = []) {
        if (empty($id_categorias)) {
            $sql = "
                SELECT p.*
                FROM PRODUCTO p
                INNER JOIN CATEGORIA c ON c.id_categoria = p.id_categoria AND c.activa = 1
                WHERE p.activo = 1
            ";
            $stmt = $conn->query($sql);
        } else {
            $in  = implode(',', array_fill(0, count($id_categorias), '?'));
            $sql = "
                SELECT p.*
                FROM PRODUCTO p
                INNER JOIN CATEGORIA c ON c.id_categoria = p.id_categoria AND c.activa = 1
                WHERE p.activo = 1 AND p.id_categoria IN ($in)
            ";
            $stmt = $conn->prepare($sql);
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
        $stmt = $conn->query("SELECT * FROM CATEGORIA WHERE activa = 1 ORDER BY nombre_categoria");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerProductosAdmin(PDO $conn): array {
        $stmt = $conn->query("
            SELECT p.id_producto, p.nombre_producto, p.descripcion, p.precio_unitario, p.stock_actual,
                p.url_imagen_principal, p.id_categoria, p.activo, p.es_nuevo, p.es_oferta, p.es_popular,
                c.activa AS categoria_activa
            FROM PRODUCTO p
            LEFT JOIN CATEGORIA c ON c.id_categoria = p.id_categoria
            ORDER BY p.id_producto DESC
        ");
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

    public static function setActivo(PDO $conn, int $id_producto, bool $activo): void {
        $stmt = $conn->prepare("UPDATE PRODUCTO SET activo = :a WHERE id_producto = :id");
        $stmt->execute([':a' => $activo ? 1 : 0, ':id' => $id_producto]);
    }

    public static function buscarProductos($conn, $keyword) {
        $sql = "
            SELECT p.id_producto, p.nombre_producto, p.url_imagen_principal
            FROM PRODUCTO p
            INNER JOIN CATEGORIA c ON c.id_categoria = p.id_categoria AND c.activa = 1
            WHERE p.activo = 1 AND p.nombre_producto LIKE ?
            LIMIT 10
        ";
        $stmt = $conn->prepare($sql);
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

        $where = ['p.activo = 1']; // categoría activa va en el JOIN
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

        // Precio
        if (isset($f['min']) && is_numeric($f['min'])) { $where[]="p.precio_unitario >= ?"; $args[]=(float)$f['min']; }
        if (isset($f['max']) && is_numeric($f['max'])) { $where[]="p.precio_unitario <= ?"; $args[]=(float)$f['max']; }

        // Disponibles
        if (!empty($f['solo_disponibles'])) { $where[]="p.stock_actual > 0"; }

        // Búsqueda
        if (!empty($f['q'])) {
            $like = '%'.trim((string)$f['q']).'%';
            $where[] = "(p.nombre_producto LIKE ? OR p.descripcion LIKE ?)";
            $args[]  = $like; $args[] = $like;
        }

        // Orden
        $orderBy = "p.id_producto DESC";
        if ($sort==='precio_asc')  $orderBy="p.precio_unitario ASC, p.id_producto DESC";
        if ($sort==='precio_desc') $orderBy="p.precio_unitario DESC, p.id_producto DESC";
        if ($sort==='nombre')      $orderBy="p.nombre_producto ASC, p.id_producto DESC";

        $offset   = ($page - 1) * $per;
        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        // COUNT con categoría activa
        $sqlCount = "
            SELECT COUNT(*)
            FROM PRODUCTO p
            INNER JOIN CATEGORIA c ON c.id_categoria = p.id_categoria AND c.activa = 1
            $whereSql
        ";
        $stmt = $conn->prepare($sqlCount);
        $stmt->execute($args);
        $total = (int)$stmt->fetchColumn();

        // ITEMS con categoría activa
        $perInt    = (int)$per;
        $offsetInt = (int)$offset;
        $sqlItems = "
            SELECT p.id_producto, p.nombre_producto, p.descripcion, p.precio_unitario,
                p.stock_actual, p.url_imagen_principal, p.id_categoria, p.activo,
                p.es_nuevo, p.es_oferta, p.es_popular
            FROM PRODUCTO p
            INNER JOIN CATEGORIA c ON c.id_categoria = p.id_categoria AND c.activa = 1
            $whereSql
            ORDER BY $orderBy
            LIMIT $perInt OFFSET $offsetInt
        ";
        $stmt = $conn->prepare($sqlItems);
        $stmt->execute($args);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['items'=>$items,'total'=>$total,'page'=>$page,'per'=>$per];
    }

    public static function obtenerCategoriasAdmin(PDO $conn): array {
        // Incluye inactivas, padre y conteo de productos
        $sql = "
            SELECT c.id_categoria, c.nombre_categoria, c.descripcion_categoria, c.id_padre, c.activa,
                   pcount.cnt AS num_productos,
                   p.nombre_categoria AS nombre_padre
            FROM CATEGORIA c
            LEFT JOIN (
                SELECT id_categoria, COUNT(*) AS cnt
                FROM PRODUCTO
                GROUP BY id_categoria
            ) pcount ON pcount.id_categoria = c.id_categoria
            LEFT JOIN CATEGORIA p ON p.id_categoria = c.id_padre
            ORDER BY 
                (c.id_padre IS NULL) DESC,
                c.id_padre,
                c.nombre_categoria
        ";
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function crearCategoria(PDO $conn, string $nombre, ?string $descripcion, ?int $id_padre, bool $activa = true): void {
        $stmt = $conn->prepare("
            INSERT INTO CATEGORIA (nombre_categoria, descripcion_categoria, id_padre, activa)
            VALUES (:n, :d, :p, :a)
        ");
        $stmt->execute([
            ':n' => $nombre,
            ':d' => $descripcion ?: null,
            ':p' => $id_padre ?: null,
            ':a' => $activa ? 1 : 0,
        ]);
    }

    public static function actualizarCategoria(PDO $conn, int $id_categoria, string $nombre, ?string $descripcion, ?int $id_padre): void {
        $stmt = $conn->prepare("
            UPDATE CATEGORIA
            SET nombre_categoria = :n, descripcion_categoria = :d, id_padre = :p
            WHERE id_categoria = :id
        ");
        $stmt->execute([
            ':n'  => $nombre,
            ':d'  => $descripcion ?: null,
            ':p'  => $id_padre ?: null,
            ':id' => $id_categoria,
        ]);
    }

    public static function setCategoriaActiva(PDO $conn, int $id_categoria, bool $activa): void {
        $stmt = $conn->prepare("UPDATE CATEGORIA SET activa = :a WHERE id_categoria = :id");
        $stmt->execute([':a' => $activa ? 1 : 0, ':id' => $id_categoria]);
    }

    public static function eliminarCategoria(PDO $conn, int $id_categoria) {
        // 1) ¿Tiene subcategorías?
        $stmt = $conn->prepare("SELECT COUNT(*) FROM CATEGORIA WHERE id_padre = ?");
        $stmt->execute([$id_categoria]);
        $hijos = (int)$stmt->fetchColumn();
        if ($hijos > 0) {
            return "⚠️ No se puede borrar: la categoría tiene subcategorías.";
        }

        // 2) ¿Tiene productos asignados?
        $stmt = $conn->prepare("SELECT COUNT(*) FROM PRODUCTO WHERE id_categoria = ?");
        $stmt->execute([$id_categoria]);
        $prods = (int)$stmt->fetchColumn();
        if ($prods > 0) {
            return "⚠️ No se puede borrar: hay productos asignados a esta categoría.";
        }

        // 3) Borrar
        $stmt = $conn->prepare("DELETE FROM CATEGORIA WHERE id_categoria = ?");
        $stmt->execute([$id_categoria]);
        return true;
    }
}

class PlantillaController {
    public static function obtenerPlantillas(PDO $conn): array {
        $sql = "
          SELECT p.*, c.nombre_categoria,
                 c.activa AS categoria_activa
          FROM PRODUCTO_PLANTILLA p
          LEFT JOIN CATEGORIA c ON c.id_categoria = p.id_categoria
          ORDER BY p.id_plantilla DESC
        ";
        return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerPlantillaById(PDO $conn, int $id): ?array {
        $stmt = $conn->prepare("
          SELECT p.*, c.nombre_categoria, c.activa AS categoria_activa
          FROM PRODUCTO_PLANTILLA p
          LEFT JOIN CATEGORIA c ON c.id_categoria = p.id_categoria
          WHERE p.id_plantilla = ?
          LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function crearPlantilla(
        PDO $conn,
        string $nombre_plantilla,
        ?int $id_categoria,
        ?string $nombre_sugerido,
        ?string $descripcion_sugerida,
        ?float $precio_por_defecto,
        ?string $url_imagen_por_defecto,
        bool $es_nuevo_def,
        bool $es_oferta_def,
        bool $es_popular_def,
        bool $activa
    ): void {
        $stmt = $conn->prepare("
          INSERT INTO PRODUCTO_PLANTILLA
          (nombre_plantilla, id_categoria, nombre_sugerido, descripcion_sugerida,
           precio_por_defecto, url_imagen_por_defecto, es_nuevo_def, es_oferta_def,
           es_popular_def, activa)
          VALUES
          (:np, :idc, :ns, :ds, :ppd, :img, :n, :o, :pop, :act)
        ");
        $stmt->execute([
            ':np'  => $nombre_plantilla,
            ':idc' => $id_categoria ?: null,
            ':ns'  => $nombre_sugerido ?: null,
            ':ds'  => $descripcion_sugerida ?: null,
            ':ppd' => $precio_por_defecto !== null ? $precio_por_defecto : null,
            ':img' => $url_imagen_por_defecto ?: null,
            ':n'   => $es_nuevo_def ? 1 : 0,
            ':o'   => $es_oferta_def ? 1 : 0,
            ':pop' => $es_popular_def ? 1 : 0,
            ':act' => $activa ? 1 : 0,
        ]);
    }

    public static function editarPlantilla(
        PDO $conn,
        int $id_plantilla,
        string $nombre_plantilla,
        ?int $id_categoria,
        ?string $nombre_sugerido,
        ?string $descripcion_sugerida,
        ?float $precio_por_defecto,
        ?string $url_imagen_por_defecto,
        bool $es_nuevo_def,
        bool $es_oferta_def,
        bool $es_popular_def,
        bool $activa
    ): void {
        $stmt = $conn->prepare("
          UPDATE PRODUCTO_PLANTILLA SET
            nombre_plantilla = :np,
            id_categoria = :idc,
            nombre_sugerido = :ns,
            descripcion_sugerida = :ds,
            precio_por_defecto = :ppd,
            url_imagen_por_defecto = :img,
            es_nuevo_def = :n,
            es_oferta_def = :o,
            es_popular_def = :pop,
            activa = :act
          WHERE id_plantilla = :id
        ");
        $stmt->execute([
            ':np'  => $nombre_plantilla,
            ':idc' => $id_categoria ?: null,
            ':ns'  => $nombre_sugerido ?: null,
            ':ds'  => $descripcion_sugerida ?: null,
            ':ppd' => $precio_por_defecto !== null ? $precio_por_defecto : null,
            ':img' => $url_imagen_por_defecto ?: null,
            ':n'   => $es_nuevo_def ? 1 : 0,
            ':o'   => $es_oferta_def ? 1 : 0,
            ':pop' => $es_popular_def ? 1 : 0,
            ':act' => $activa ? 1 : 0,
            ':id'  => $id_plantilla
        ]);
    }

    public static function eliminarPlantilla(PDO $conn, int $id): void {
        $stmt = $conn->prepare("DELETE FROM PRODUCTO_PLANTILLA WHERE id_plantilla = ?");
        $stmt->execute([$id]);
    }

    public static function setActiva(PDO $conn, int $id, bool $activa): void {
        $stmt = $conn->prepare("UPDATE PRODUCTO_PLANTILLA SET activa = :a WHERE id_plantilla = :id");
        $stmt->execute([':a' => $activa ? 1 : 0, ':id' => $id]);
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'buscar' && isset($_GET['q'])) {
    ProductController::buscarProductosJSON($conn, $_GET['q']);
}
?>
