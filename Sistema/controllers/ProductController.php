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
}
?>
