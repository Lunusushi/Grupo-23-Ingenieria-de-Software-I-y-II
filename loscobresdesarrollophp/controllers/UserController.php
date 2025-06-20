<?php
require_once __DIR__ . '/../config/MySqlDb.php';

class UserController {
    public static function obtenerUsuarios($conn) {
        $stmt = $conn->query("SELECT * FROM USUARIO");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerOperadores($conn) {
        $stmt = $conn->query("SELECT id_usuario, cargo FROM OPERADOR");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $operadores = [];
        foreach ($result as $row) {
            $operadores[$row['id_usuario']] = $row['cargo'];
        }
        return $operadores;
    }

    public static function asignarOperador($conn, $id_usuario, $cargo = "caja") {
        $check = $conn->prepare("SELECT * FROM OPERADOR WHERE id_usuario = ?");
        $check->execute([$id_usuario]);

        if (!$check->fetch()) {
            // Remove from CLIENTE table if exists
            $delCliente = $conn->prepare("DELETE FROM CLIENTE WHERE id_usuario = ?");
            $delCliente->execute([$id_usuario]);

            $stmt = $conn->prepare("INSERT INTO OPERADOR (id_usuario, cargo) VALUES (?, ?)");
            $stmt->execute([$id_usuario, $cargo]);
        } else {
            // Update cargo if already exists
            $stmt = $conn->prepare("UPDATE OPERADOR SET cargo = ? WHERE id_usuario = ?");
            $stmt->execute([$cargo, $id_usuario]);
        }
    }

    public static function revocarOperador($conn, $id_usuario) {
        $stmt = $conn->prepare("DELETE FROM OPERADOR WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
    }

    public static function revocarCargo($conn, $id_usuario, $cargo) {
        $stmt = $conn->prepare("DELETE FROM OPERADOR WHERE id_usuario = ? AND cargo = ?");
        $stmt->execute([$id_usuario, $cargo]);
    }

    public static function puedeAsignarCargo($currentUserCargo, $targetCargo, $currentUserId, $targetUserId, $targetUserCurrentCargo) {
    // Administrador puede asignar todo, excepto a sí mismo degradarse
        if ($currentUserCargo === 'administrador') {
            if ($targetUserId === $currentUserId && $targetCargo !== 'administrador') {
                return false; // No puede degradarse a sí mismo
            }
            return true;
        }

        // Mantenedor solo puede asignar caja o catálogo, nunca admin ni a admin
        if ($currentUserCargo === 'mantenedor') {
            if (in_array($targetCargo, ['caja', 'catalogo']) && $targetUserCurrentCargo !== 'administrador') {
                return true;
            }
        }
        return false;
    }

    public static function puedeRevocarCargo($currentUserCargo, $targetCargo, $currentUserId, $targetUserId, $targetUserCurrentCargo) {
        // Administrador no puede revocar su propio cargo de admin
        if ($currentUserCargo === 'administrador') {
            if ($targetCargo === 'administrador' && $targetUserId === $currentUserId) {
                return false;
            }
            return true;
        }

        // Mantenedor no puede revocar admin
        if ($currentUserCargo === 'mantenedor') {
            if (in_array($targetCargo, ['caja', 'catalogo']) && $targetUserCurrentCargo !== 'administrador') {
                return true;
            }
        }

        return false;
    }

    public static function asignarOperador($conn, $id_usuario, $cargo) {
        // Primero, eliminar si ya tiene otro cargo (opcional)
        $stmt = $conn->prepare("DELETE FROM OPERADOR WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);

        // Insertar nuevo cargo
        $stmt = $conn->prepare("INSERT INTO OPERADOR (id_usuario, cargo) VALUES (?, ?)");
        $stmt->execute([$id_usuario, $cargo]);
    }

    public static function revocarCargo($conn, $id_usuario, $cargo) {
        $stmt = $conn->prepare("DELETE FROM OPERADOR WHERE id_usuario = ? AND cargo = ?");
        $stmt->execute([$id_usuario, $cargo]);
    }
}
}
?>
