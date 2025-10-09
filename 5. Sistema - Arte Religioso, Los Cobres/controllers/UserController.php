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
        // Verificar si es cliente
        $checkCliente = $conn->prepare("SELECT 1 FROM CLIENTE WHERE id_cliente = ?");
        $checkCliente->execute([$id_usuario]);
        if ($checkCliente->fetch()) {
            throw new Exception("❌ No se puede asignar un cargo administrativo a un cliente.");
        }

        // Eliminar cualquier otro rol anterior en operador
        $stmt = $conn->prepare("DELETE FROM OPERADOR WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);

        // Insertar nuevo rol
        $stmt = $conn->prepare("INSERT INTO OPERADOR (id_usuario, cargo) VALUES (?, ?)");
        $stmt->execute([$id_usuario, $cargo]);
    }

    public static function revocarCargo($conn, $id_usuario, $cargo) {
        $stmt = $conn->prepare("DELETE FROM OPERADOR WHERE id_usuario = ? AND cargo = ?");
        $stmt->execute([$id_usuario, $cargo]);
    }

    public static function revocarOperador($conn, $id_usuario) {
        $stmt = $conn->prepare("DELETE FROM OPERADOR WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
    }

    public static function puedeAsignarCargo($currentUserCargo, $targetCargo, $currentUserId, $targetUserId, $targetUserCurrentCargo) {
        // 🚫 Nadie puede cambiar su propio cargo
        if ($currentUserId === $targetUserId) {
            return false;
        }

        // ✅ Administrador puede asignar cualquier cargo a otros
        if ($currentUserCargo === 'administrador') {
            return true;
        }

        // 🚫 Mantenedor no puede modificar a administradores
        if ($currentUserCargo === 'mantenedor') {
            if ($targetUserCurrentCargo === 'administrador') {
                return false;
            }

            return in_array($targetCargo, ['caja', 'catalogo']);
        }

        return false;
    }

    public static function puedeRevocarCargo($currentUserCargo, $targetCargo, $currentUserId, $targetUserId, $targetUserCurrentCargo) {
        // 🚫 Nadie puede revocar su propio cargo de administrador
        if ($currentUserId === $targetUserId && $targetCargo === 'administrador') {
            return false;
        }

        // ✅ Administrador puede revocar cualquier cargo a otros
        if ($currentUserCargo === 'administrador') {
            return true;
        }

        // 🚫 Mantenedor no puede revocar cargos de administradores
        if ($currentUserCargo === 'mantenedor') {
            if ($targetUserCurrentCargo === 'administrador') {
                return false;
            }

            return in_array($targetCargo, ['caja', 'catalogo']);
        }

        return false;
    }
}
?>
