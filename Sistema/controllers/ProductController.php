<?php
require_once __DIR__ . '/../config/MySqlDb.php';

class ProductController {
    // Lista productos activos; si pasas categorías, filtra. Siempre exige categoría activa.
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

    // Solo categorías activas (vista cliente)
    public static function obtenerCategorias($conn) {
        $stmt = $conn->query("SELECT * FROM CATEGORIA WHERE activa = 1 ORDER BY nombre_categoria");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Para grilla admin (incluye estado de la categoría)
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
            }
            throw $e;
        }
    }

    public static function setActivo(PDO $conn, int $id_producto, bool $activo): void {
        $stmt = $conn->prepare("UPDATE PRODUCTO SET activo = :a WHERE id_producto = :id");
        $stmt->execute([':a' => $activo ? 1 : 0, ':id' => $id_producto]);
    }

    // Autocomplete (cliente): solo activos y con categoría activa
    public static function buscarProductos($conn, $keyword) {
        $sql = "
            SELECT p.id_producto, p.nombre_producto, p.url_imagen_principal
            FROM PRODUCTO p
            INNER JOIN CATEGORIA c ON c.id_categoria = p.id_categoria AND c.activa = 1
            WHERE p.activo = 1 AND p.nombre_producto LIKE ?
            LIMIT 10
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['%'.$keyword.'%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function buscarProductosJSON($conn, $keyword) {
        $productos = self::buscarProductos($conn, $keyword);
        header('Content-Type: application/json');
        echo json_encode($productos);
        exit;
    }

    // Autocomplete admin: activos e inactivos
    public static function buscarProductosAdmin(PDO $conn, string $keyword): array {
        $sql = "
            SELECT id_producto, nombre_producto, url_imagen_principal, activo
            FROM PRODUCTO
            WHERE nombre_producto LIKE ?
            ORDER BY id_producto DESC
            LIMIT 10
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['%'.$keyword.'%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function buscarProductosAdminJSON(PDO $conn, string $keyword): void {
        $items = self::buscarProductosAdmin($conn, $keyword);
        header('Content-Type: application/json');
        echo json_encode($items);
        exit;
    }

    public static function listarPaginado(PDO $conn, array $f = [], int $page = 1, int $per = 12, string $sort = 'recientes'): array
    {
        $page = max(1, $page);
        $per  = max(1, min(60, $per));
        $validSort = ['recientes','precio_asc','precio_desc','nombre'];
        if (!in_array($sort, $validSort, true)) $sort = 'recientes';

        $where = ['p.activo = 1']; // categoría activa se exige en el JOIN
        $args  = [];

        // Categorías (checkbox)
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

        // Filtro por PLANTILLA (colección de productos) — exige plantilla activa
        if (!empty($f['plantilla']) && ctype_digit((string)$f['plantilla'])) {
            $where[] = "p.id_producto IN (
                SELECT pp.id_producto
                FROM PLANTILLA_PRODUCTO pp
                INNER JOIN PLANTILLA pl ON pl.id_plantilla = pp.id_plantilla
                WHERE pp.id_plantilla = ? AND pl.activa = 1
            )";
            $args[]  = (int)$f['plantilla'];
        }

        // Filtro por colección de categorías — exige colección activa
        if (!empty($f['catset']) && ctype_digit((string)$f['catset'])) {
            $where[] = "p.id_categoria IN (
                SELECT pci.id_categoria
                FROM PLANTILLA_CAT_ITEM pci
                INNER JOIN PLANTILLA_CAT pc ON pc.id_plantilla_cat = pci.id_plantilla_cat
                WHERE pci.id_plantilla_cat = ? AND pc.activa = 1
            )";
            $args[] = (int)$f['catset'];
        }

        // Orden
        $orderBy = "p.id_producto DESC";
        if ($sort==='precio_asc')  $orderBy="p.precio_unitario ASC, p.id_producto DESC";
        if ($sort==='precio_desc') $orderBy="p.precio_unitario DESC, p.id_producto DESC";
        if ($sort==='nombre')      $orderBy="p.nombre_producto ASC, p.id_producto DESC";

        $offset   = ($page - 1) * $per;
        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        // COUNT
        $sqlCount = "
            SELECT COUNT(*)
            FROM PRODUCTO p
            INNER JOIN CATEGORIA c ON c.id_categoria = p.id_categoria AND c.activa = 1
            $whereSql
        ";
        $stmt = $conn->prepare($sqlCount);
        $stmt->execute($args);
        $total = (int)$stmt->fetchColumn();

        // ITEMS
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
}

class PlantillaController {
    public static function listar(PDO $conn): array {
        $sql = "SELECT id_plantilla, nombre, activa, fecha_creacion
                FROM PLANTILLA ORDER BY id_plantilla DESC";
        return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtener(PDO $conn, int $id_plantilla): ?array {
        $stmt = $conn->prepare("SELECT * FROM PLANTILLA WHERE id_plantilla = ? LIMIT 1");
        $stmt->execute([$id_plantilla]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function crear(PDO $conn, string $nombre, bool $activa = true): void {
        // Normaliza un poco el nombre
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new Exception('El nombre no puede ir vacío.');
        }

        try {
            $stmt = $conn->prepare("INSERT INTO PLANTILLA (nombre, activa) VALUES (?, ?)");
            $stmt->execute([$nombre, $activa ? 1 : 0]);
        } catch (PDOException $e) {
            // 1062 = duplicate entry
            if (isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062) {
                throw new Exception('⚠️ Ya existe una plantilla con ese nombre.');
            }
            throw $e;
        }
    }

    // Reemplaza por esta versión con try/catch
    public static function renombrar(PDO $conn, int $id_plantilla, string $nuevoNombre): void {
        $nuevoNombre = trim($nuevoNombre);
        if ($nuevoNombre === '') {
            throw new Exception('El nombre no puede ir vacío.');
        }

        try {
            $stmt = $conn->prepare("UPDATE PLANTILLA SET nombre = ? WHERE id_plantilla = ?");
            $stmt->execute([$nuevoNombre, $id_plantilla]);
        } catch (PDOException $e) {
            if (isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062) {
                throw new Exception('⚠️ Ya existe una plantilla con ese nombre.');
            }
            throw $e;
        }
    }

    public static function setActiva(PDO $conn, int $id_plantilla, bool $activa): void {
        $stmt = $conn->prepare("UPDATE PLANTILLA SET activa = ? WHERE id_plantilla = ?");
        $stmt->execute([$activa ? 1 : 0, $id_plantilla]);
    }

    public static function eliminar(PDO $conn, int $id_plantilla): void {
        $stmt = $conn->prepare("DELETE FROM PLANTILLA WHERE id_plantilla = ?");
        $stmt->execute([$id_plantilla]);
    }

    /* === Ítems (productos en la plantilla) === */
    public static function agregarProducto(PDO $conn, int $id_plantilla, int $id_producto, int $orden = 0): void {
        $stmt = $conn->prepare("
          INSERT INTO PLANTILLA_PRODUCTO (id_plantilla, id_producto, orden)
          VALUES (:p, :prod, :o)
          ON DUPLICATE KEY UPDATE orden = VALUES(orden)
        ");
        $stmt->execute([':p'=>$id_plantilla, ':prod'=>$id_producto, ':o'=>$orden]);
    }

    public static function quitarProducto(PDO $conn, int $id_plantilla, int $id_producto): void {
        $stmt = $conn->prepare("DELETE FROM PLANTILLA_PRODUCTO WHERE id_plantilla = ? AND id_producto = ?");
        $stmt->execute([$id_plantilla, $id_producto]);
    }

    public static function setOrden(PDO $conn, int $id_plantilla, int $id_producto, int $orden): void {
        $stmt = $conn->prepare("
          UPDATE PLANTILLA_PRODUCTO SET orden = :o
          WHERE id_plantilla = :p AND id_producto = :prod
        ");
        $stmt->execute([':o'=>$orden, ':p'=>$id_plantilla, ':prod'=>$id_producto]);
    }

    public static function listarProductos(PDO $conn, int $id_plantilla): array {
        $sql = "
          SELECT pp.id_producto, pp.orden,
                 p.nombre_producto, p.url_imagen_principal, p.precio_unitario, p.stock_actual, p.activo
          FROM PLANTILLA_PRODUCTO pp
          INNER JOIN PRODUCTO p ON p.id_producto = pp.id_producto
          WHERE pp.id_plantilla = ?
          ORDER BY pp.orden ASC, p.id_producto DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_plantilla]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* === Acciones masivas === */
    public static function activarPorPlantilla(PDO $conn, int $id_plantilla, bool $activar): int {
        $sql = "
          UPDATE PRODUCTO
          SET activo = :a
          WHERE id_producto IN (
            SELECT id_producto FROM PLANTILLA_PRODUCTO WHERE id_plantilla = :p
          )
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':a' => $activar ? 1 : 0, ':p' => $id_plantilla]);
        return $stmt->rowCount();
    }
}

class PlantillaCategoriaController {

    public static function listarColecciones(PDO $conn): array {
        $sql = "
          SELECT p.id_plantilla_cat, p.nombre, p.activa, p.fecha_creacion,
                 COALESCE(pc.cnt, 0) AS num_categorias
          FROM PLANTILLA_CAT p
          LEFT JOIN (
            SELECT id_plantilla_cat, COUNT(*) AS cnt
            FROM PLANTILLA_CAT_ITEM
            GROUP BY id_plantilla_cat
          ) pc ON pc.id_plantilla_cat = p.id_plantilla_cat
          ORDER BY p.id_plantilla_cat DESC
        ";
        return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerColeccion(PDO $conn, int $id): ?array {
        $stmt = $conn->prepare("SELECT * FROM PLANTILLA_CAT WHERE id_plantilla_cat = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function crearColeccion(PDO $conn, string $nombre, bool $activa = true): void {
        $stmt = $conn->prepare("INSERT INTO PLANTILLA_CAT (nombre, activa) VALUES (:n, :a)");
        $stmt->execute([':n' => $nombre, ':a' => $activa ? 1 : 0]);
    }

    public static function actualizarColeccion(PDO $conn, int $id, string $nombre, bool $activa): void {
        $stmt = $conn->prepare("UPDATE PLANTILLA_CAT SET nombre = :n, activa = :a WHERE id_plantilla_cat = :id");
        $stmt->execute([':n' => $nombre, ':a' => $activa ? 1 : 0, ':id' => $id]);
    }

    public static function eliminarColeccion(PDO $conn, int $id): void {
        $stmt = $conn->prepare("DELETE FROM PLANTILLA_CAT WHERE id_plantilla_cat = ?");
        $stmt->execute([$id]);
    }

    public static function setActiva(PDO $conn, int $id, bool $activa): void {
        $stmt = $conn->prepare("UPDATE PLANTILLA_CAT SET activa = :a WHERE id_plantilla_cat = :id");
        $stmt->execute([':a' => $activa ? 1 : 0, ':id' => $id]);
    }

    public static function obtenerCategoriasAdmin(PDO $conn): array {
        $sql = "
          SELECT c.id_categoria, c.nombre_categoria, c.descripcion_categoria, c.id_padre, c.activa,
                 (SELECT COUNT(*) FROM PRODUCTO p WHERE p.id_categoria = c.id_categoria) AS num_productos,
                 p.nombre_categoria AS nombre_padre
          FROM CATEGORIA c
          LEFT JOIN CATEGORIA p ON p.id_categoria = c.id_padre
          ORDER BY (c.id_padre IS NULL) DESC, c.id_padre, c.nombre_categoria
        ";
        return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function crearCategoria(PDO $conn, string $nombre, ?string $desc, ?int $padre, bool $activa): void {
        $stmt = $conn->prepare("INSERT INTO CATEGORIA (nombre_categoria, descripcion_categoria, id_padre, activa) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $desc, $padre, $activa ? 1 : 0]);
    }

    public static function actualizarCategoria(PDO $conn, int $id, string $nombre, ?string $desc, ?int $padre): void {
        $stmt = $conn->prepare("UPDATE CATEGORIA SET nombre_categoria = ?, descripcion_categoria = ?, id_padre = ? WHERE id_categoria = ?");
        $stmt->execute([$nombre, $desc, $padre, $id]);
    }

    public static function setCategoriaActiva(PDO $conn, int $id, bool $activa): void {
        $stmt = $conn->prepare("UPDATE CATEGORIA SET activa = ? WHERE id_categoria = ?");
        $stmt->execute([$activa ? 1 : 0, $id]);
    }

    public static function eliminarCategoria(PDO $conn, int $id): bool|string {
        try {
            $stmt = $conn->prepare("DELETE FROM CATEGORIA WHERE id_categoria = ?");
            $stmt->execute([$id]);
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return "⚠️ No se puede eliminar la categoría porque tiene subcategorías o productos asociados.";
            }
            throw $e;
        }
    }

    // Devuelve [id_categoria => orden] de una colección
    public static function obtenerCategoriasDeColeccion(PDO $conn, int $id_plantilla_cat): array {
        $stmt = $conn->prepare("
          SELECT id_categoria, orden
          FROM PLANTILLA_CAT_ITEM
          WHERE id_plantilla_cat = ?
          ORDER BY orden, id_categoria
        ");
        $stmt->execute([$id_plantilla_cat]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) { $out[(int)$r['id_categoria']] = (int)$r['orden']; }
        return $out;
    }

    public static function guardarCategoriasDeColeccion(PDO $conn, int $id_plantilla_cat, array $catIds, array $ordenes): void {
        $conn->beginTransaction();
        try {
            $del = $conn->prepare("DELETE FROM PLANTILLA_CAT_ITEM WHERE id_plantilla_cat = ?");
            $del->execute([$id_plantilla_cat]);

            if (!empty($catIds)) {
                $ins = $conn->prepare("
                  INSERT INTO PLANTILLA_CAT_ITEM (id_plantilla_cat, id_categoria, orden)
                  VALUES (:p, :c, :o)
                ");
                foreach ($catIds as $cid) {
                    if (!ctype_digit((string)$cid)) continue;
                    $cid   = (int)$cid;
                    $orden = isset($ordenes[$cid]) && is_numeric($ordenes[$cid]) ? (int)$ordenes[$cid] : 0;
                    $ins->execute([':p' => $id_plantilla_cat, ':c' => $cid, ':o' => $orden]);
                }
            }

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'buscar' && isset($_GET['q'])) {
    ProductController::buscarProductosJSON($conn, $_GET['q']);
}

if (isset($_GET['action']) && $_GET['action'] === 'buscar_admin' && isset($_GET['q'])) {
    ProductController::buscarProductosAdminJSON($conn, $_GET['q']);
}