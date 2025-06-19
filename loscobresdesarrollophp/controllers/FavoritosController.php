<?php
require_once __DIR__ . '/../config/db.php';
session_start();

class FavoritosController {
    public static function obtenerLista($conn, $id_cliente) {
        // Obtener o crear lista de favoritos
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
        // Validar límite
        $check = $conn->prepare("SELECT COUNT(*) as total FROM ITEM_FAVORITO WHERE id_lista = ?");
        $check->execute([$id_lista]);
        $total = $check->fetchColumn();

        if ($total >= 30) {
            return "❌ Límite de 30 favoritos alcanzado.";
        }

        // Verificar si ya existe
        $check2 = $conn->prepare("SELECT * FROM ITEM_FAVORITO WHERE id_lista = ? AND id_producto = ?");
        $check2->execute([$id_lista, $id_producto]);
        if ($check2->fetch()) {
            return "⚠️ El producto ya está en favoritos.";
        }

        // Insertar
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
}
?>
