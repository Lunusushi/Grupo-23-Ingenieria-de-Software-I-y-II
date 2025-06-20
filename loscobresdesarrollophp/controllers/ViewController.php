<?php
class ViewController {
    public static function render($page, $data = []) {
        // Basic page rendering logic based on page name
        // $data can be used to pass variables to views

        switch ($page) {
            case 'login':
                include __DIR__ . '/../login.php';
                break;
            case 'catalogo':
                include __DIR__ . '/../catalogo.php';
                break;
            case 'carrito':
                include __DIR__ . '/../carrito.php';
                break;
            case 'favoritos':
                include __DIR__ . '/../favoritos.php';
                break;
            case 'realizar_pedido':
                include __DIR__ . '/../realizar_pedido.php';
                break;
            case 'admin_index':
                include __DIR__ . '/../admin_index.php';
                break;
            case 'productos_admin':
                include __DIR__ . '/../productos_admin.php';
                break;
            case 'permisos_admin':
                include __DIR__ . '/../permisos_admin.php';
                break;
            case 'verificar_pedido':
                include __DIR__ . '/../verificar_pedido.php';
                break;
            default:
                // Default to login page or 404
                include __DIR__ . '/../login.php';
                break;
        }
    }
}
?>
