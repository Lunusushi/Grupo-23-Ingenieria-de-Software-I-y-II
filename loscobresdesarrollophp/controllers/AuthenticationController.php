<?php
require_once __DIR__ . '/../config/MySqlDb.php';

class AuthenticationController {
    public static function login($email, $password, $user_type) {
        global $conn;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($user_type === 'cliente') {
            $stmt = $conn->prepare("SELECT * FROM USUARIO WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user'] = [
                    'id' => $user['id_usuario'],
                    'name' => $user['nombre'],
                    'email' => $user['email'],
                    'type' => 'cliente'
                ];
                return true;
            }
        } else {
            $stmt = $conn->prepare("SELECT o.id_usuario, o.cargo, u.nombre, u.email FROM OPERADOR o JOIN USUARIO u ON o.id_usuario = u.id_usuario WHERE u.email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $stmt2 = $conn->prepare("SELECT password_hash FROM USUARIO WHERE id_usuario = ?");
                $stmt2->execute([$user['id_usuario']]);
                $userPass = $stmt2->fetchColumn();
                if ($userPass && password_verify($password, $userPass)) {
                    $_SESSION['user'] = [
                        'id' => $user['id_usuario'],
                        'name' => $user['nombre'],
                        'email' => $email,
                        'type' => 'operador',
                        'cargo' => $user['cargo']
                    ];
                    return true;
                }
            }
        }
        return false;
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }

    public static function getCurrentUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }

    public static function validatePedidoToken($codigo) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM PEDIDO WHERE codigo_verificacion = ?");
        $stmt->execute([$codigo]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        return $pedido ?: null;
    }
}
?>
