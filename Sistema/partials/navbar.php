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
        <li class="nav-item"><a class="nav-link" href="carrito.php">Carrito</a></li>

        <?php if ($userType === 'cliente'): ?>
          <li class="nav-item"><a class="nav-link" href="carrito.php">Carrito</a></li>
          <li class="nav-item"><a class="nav-link" href="favoritos.php">Favoritos</a></li>
          <li class="nav-item"><a class="nav-link" href="realizar_pedido.php">Pedido</a></li>

        <?php elseif ($userType === 'operador'): ?>
          <?php if ($cargo === 'administrador' || $cargo === 'mantenedor'): ?>
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

      <!-- Campo de búsqueda adaptado a Bootstrap -->
      <form id="search-form" class="d-flex align-items-center position-relative my-2 my-lg-0" action="catalogo.php" method="GET">
        <input type="text" id="search-input" name="q" class="form-control me-2" placeholder="Buscar productos..." autocomplete="off">
        <div id="suggestions" class="list-group position-absolute w-100"
            style="top: calc(100% + .25rem); z-index: 1000; display: none;"></div>
      </form>
      <ul class="navbar-nav ms-3">
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

<script>
const searchInput = document.getElementById('search-input');
const suggestionsBox = document.getElementById('suggestions');

searchInput.addEventListener('input', () => {
    const query = searchInput.value.trim();
    if (query.length === 0) {
        suggestionsBox.innerHTML = '';
        suggestionsBox.style.display = "none";
        return;
    }

    fetch(`controllers/productController.php?action=buscar&q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(products => {
            suggestionsBox.innerHTML = '';
            products.forEach(product => {
              const item = document.createElement('a');
              item.href = `producto.php?id=${product.id_producto}`;
              item.textContent = product.nombre_producto;
              item.classList.add('list-group-item', 'list-group-item-action');
              suggestionsBox.appendChild(item);
            });
            suggestionsBox.style.display = products.length ? 'block' : 'none';
        });
});

document.addEventListener('click', (e) => {
    if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
        suggestionsBox.innerHTML = '';
        suggestionsBox.style.display = 'none';
    }
});
</script>
