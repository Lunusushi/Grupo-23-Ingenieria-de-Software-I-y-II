<?php
require_once __DIR__ . '/../config/db.php';

class PermisoController {
    public static function obtenerUsuarios($conn) {
        $stmt = $conn->query("SELECT * FROM USUARIO");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerOperadores($conn) {
        $stmt = $conn->query("SELECT id_usuario FROM OPERADOR");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function asignarOperador($conn, $id_usuario, $cargo = "Caja") {
        $check = $conn->prepare("SELECT * FROM OPERADOR WHERE id_usuario = ?");
        $check->execute([$id_usuario]);

        if (!$check->fetch()) {
            $stmt = $conn->prepare("INSERT INTO OPERADOR (id_usuario, cargo) VALUES (?, ?)");
            $stmt->execute([$id_usuario, $cargo]);
        }
    }

    public static function revocarOperador($conn, $id_usuario) {
        $stmt = $conn->prepare("DELETE FROM OPERADOR WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
    }
}
?>
