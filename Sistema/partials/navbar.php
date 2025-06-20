<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION["user"] ?? null;
$userType = $_SESSION["user_type"] ?? null;
$cargo = $_SESSION["cargo"] ?? null;
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">🛒 Los Cobres</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <!-- Disponible para todos -->
        <li class="nav-item"><a class="nav-link" href="catalogo.php">Catálogo</a></li>

        <?php if ($userType === 'cliente'): ?>
          <!-- Secciones específicas para CLIENTE -->
          <li class="nav-item"><a class="nav-link" href="carrito.php">Carrito</a></li>
          <li class="nav-item"><a class="nav-link" href="favoritos.php">Favoritos</a></li>
          <li class="nav-item"><a class="nav-link" href="realizar_pedido.php">Pedido</a></li>

        <?php elseif ($userType === 'operador'): ?>
          <!-- Secciones para OPERADOR según su cargo -->
          <?php if ($cargo === 'administrador'): ?>
            <li class="nav-item"><a class="nav-link" href="verificar_pedido.php">Verificar Pedido</a></li>
            <li class="nav-item"><a class="nav-link" href="productos_admin.php">Admin Productos</a></li>
            <li class="nav-item"><a class="nav-link" href="permisos_admin.php">Permisos</a></li>

          <?php elseif ($cargo === 'mantenedor'): ?>
            <li class="nav-item"><a class="nav-link" href="verificar_pedido.php">Verificar Pedido</a></li>
            <li class="nav-item"><a class="nav-link" href="productos_admin.php">Admin Productos</a></li>
            <li class="nav-item"><a class="nav-link" href="permisos_admin.php">Permisos</a></li>

          <?php elseif ($cargo === 'catalogo'): ?>
            <li class="nav-item"><a class="nav-link" href="productos_admin.php">Admin Productos</a></li>

          <?php elseif ($cargo === 'caja'): ?>
            <li class="nav-item"><a class="nav-link" href="verificar_pedido.php">Verificar Pedido</a></li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav">
        <?php if ($user): ?>
          <li class="nav-item d-flex align-items-center">
            <span class="me-3 text-white">Hola, <?= htmlspecialchars($user['name']) ?></span>
            <a class="nav-link text-danger" href="logout.php">Cerrar sesión</a>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Iniciar sesión</a></li>
          <li class="nav-item"><a class="nav-link" href="register.php">Registrarse</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
