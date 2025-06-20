<?php
require_once __DIR__ . '/../config/db.php';

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
        $stmt = $conn->prepare("SELECT cargo FROM OPERADOR WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        $currentCargo = $stmt->fetchColumn();

        if ($currentCargo === $cargo) {
            // Remove operador entry
            self::revocarOperador($conn, $id_usuario);
        }
        // If multiple cargos per user are supported, implement logic here to remove specific cargo only
    }
}
?>
