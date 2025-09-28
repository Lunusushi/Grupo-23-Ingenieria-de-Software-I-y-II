<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION["user"] ?? null;
$userType = $_SESSION["user_type"] ?? null;
$cargo = $_SESSION["cargo"] ?? null;
?>



<!-- Pendiente: Creacion del campo de busqueda -->
 
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
          <!-- Secciones para operador -->
          <?php if ($cargo === 'administrador'): ?>
            <!-- Secciones para cargo administrador -->
            <li class="nav-item"><a class="nav-link" href="verificar_pedido.php">Verificar Pedido</a></li>
            <li class="nav-item"><a class="nav-link" href="productos_admin.php">Admin Productos</a></li>
            <li class="nav-item"><a class="nav-link" href="permisos_admin.php">Permisos</a></li>
          <?php elseif ($cargo === 'mantenedor'): ?>
            <!-- Secciones para cargo mantenedor -->
            <li class="nav-item"><a class="nav-link" href="verificar_pedido.php">Verificar Pedido</a></li>
            <li class="nav-item"><a class="nav-link" href="productos_admin.php">Admin Productos</a></li>
            <li class="nav-item"><a class="nav-link" href="permisos_admin.php">Permisos</a></li>
          <?php elseif ($cargo === 'catalogo'): ?>
            <!-- Secciones para cargo catalogo -->
            <li class="nav-item"><a class="nav-link" href="productos_admin.php">Admin Productos</a></li>
          <?php elseif ($cargo === 'caja'): ?>
            <!-- Secciones para cargo caja -->
            <li class="nav-item"><a class="nav-link" href="verificar_pedido.php">Verificar Pedido</a></li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
      
      <form id="search-form" action="catalogo.php" method="GET" style="position:relative;">
        <input
            type="text" 
            id="search-input" 
            name="q" 
            placeholder="Buscar productos..." 
            autocomplete="off"
            style="padding: 5px 10px; width: 250px; border-radius: 5px; border: 1px solid #ccc; ">
        <!-- Contenedor para las sugerencias -->
        <div id="suggestions" 
            style="position: absolute; top: 100%; left: 0; width: 250px; border: 1px solid #ccc; background: #fff; display: none; z-index: 1000;">
        </div>
      </form>

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
                  item.href = `catalogo.php?id=${product.id_producto}`;
                  item.textContent = product.nombre_producto;
                  item.style.display = 'block';
                  item.style.padding = '5px 10px';
                  item.style.textDecoration = 'none';
                  item.style.color = '#000';
                  item.addEventListener('mouseover', () => item.style.backgroundColor = '#f0f0f0');
                  item.addEventListener('mouseout', () => item.style.backgroundColor = '#fff');
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