<?php
// partials/admin_sidebar_open.php
if (session_status() === PHP_SESSION_NONE) session_start();

$user     = $_SESSION['user'] ?? null;
$userType = $user['type']   ?? ($_SESSION['user_type'] ?? null);
$cargo    = $user['cargo']  ?? ($_SESSION['cargo'] ?? null);

// Guard de acceso: sólo operadores con cargo
if ($userType !== 'operador' || !$cargo) {
  header('Location: login.php');
  exit;
}
?>
<!-- Encabezado superior con botón de menú siempre visible -->
<header class="navbar navbar-dark bg-dark">
  <div class="container-fluid">
    <button class="btn btn-outline-light me-2" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#adminSidebar"
            aria-controls="adminSidebar">☰ Menú</button>

    <a class="navbar-brand" href="admin_index.php">Los Cobres · Administración</a>

    <div class="d-flex align-items-center ms-auto">
      <a href="index.php" class="btn btn-sm btn-outline-light me-2">Ver tienda</a>
      <?php if ($user): ?>
        <span class="text-white small me-3">
          <?= htmlspecialchars($user['name'] ?? 'Operador') ?><?= $cargo ? ' · '.htmlspecialchars($cargo) : '' ?>
        </span>
        <a class="btn btn-sm btn-danger" href="logout.php">Salir</a>
      <?php else: ?>
        <a class="btn btn-sm btn-outline-light" href="login.php">Iniciar sesión</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<style>
  .admin-shell { min-height: 100vh; }
  #adminSidebar { width: 260px; }
  #admin-suggestions { position: absolute; top: calc(100% + .25rem); z-index: 1051; display: none; }
</style>

<div class="admin-shell d-flex">
  <!-- Sidebar: offcanvas SIEMPRE (sin -lg) -->
  <nav class="offcanvas offcanvas-start text-bg-dark border-end"
       tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel"
       data-bs-scroll="true" data-bs-backdrop="true">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="adminSidebarLabel">Menú operador</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column">
      <ul class="nav nav-pills flex-column mb-3">
        <?php if ($cargo === 'administrador' || $cargo === 'mantenedor'): ?>
          <li class="nav-item"><a class="nav-link text-white" href="admin_index.php"       data-bs-dismiss="offcanvas">📊 Dashboard</a></li>
          <li class="nav-item"><a class="nav-link text-white" href="verificar_pedido.php"  data-bs-dismiss="offcanvas">🧾 Verificar Pedido</a></li>
          <li class="nav-item"><a class="nav-link text-white" href="admin_productos.php"   data-bs-dismiss="offcanvas">📦 Admin Productos</a></li>
          <li class="nav-item"><a class="nav-link text-white" href="admin_categorias.php"  data-bs-dismiss="offcanvas">🗂 Categorías</a></li>
          <li class="nav-item"><a class="nav-link text-white" href="admin_promos.php"      data-bs-dismiss="offcanvas">🎯 Promos Home</a></li>
          <li class="nav-item"><a class="nav-link text-white" href="admin_permisos.php"    data-bs-dismiss="offcanvas">🔐 Permisos</a></li>
        <?php elseif ($cargo === 'catalogo'): ?>
          <li class="nav-item"><a class="nav-link text-white" href="admin_productos.php"   data-bs-dismiss="offcanvas">📦 Admin Productos</a></li>
          <li class="nav-item"><a class="nav-link text-white" href="admin_categorias.php"  data-bs-dismiss="offcanvas">🗂 Categorías</a></li>
          <li class="nav-item"><a class="nav-link text-white" href="admin_promos.php"      data-bs-dismiss="offcanvas">🎯 Promos Home</a></li>
        <?php elseif ($cargo === 'caja'): ?>
          <li class="nav-item"><a class="nav-link text-white" href="verificar_pedido.php"  data-bs-dismiss="offcanvas">🧾 Verificar Pedido</a></li>
        <?php endif; ?>
      </ul>

      <!-- Buscador propio del panel (IDs únicos) -->
      <form id="admin-search-form" class="position-relative mt-auto" action="catalogo.php" method="GET" autocomplete="off">
        <input type="text" id="admin-search-input" name="q" class="form-control form-control-sm" placeholder="Buscar en tienda…">
        <div id="admin-suggestions" class="list-group w-100"></div>
      </form>
    </div>
  </nav>

  <!-- Contenido principal del panel (se cierra en admin_sidebar_close.php) -->
  <main class="flex-grow-1 p-3">
    <!-- TU CONTENIDO COMIENZA AQUÍ -->

<script>
(function(){
  const input = document.getElementById('admin-search-input');
  const box   = document.getElementById('admin-suggestions');
  if (!input || !box) return;

  input.addEventListener('input', async () => {
    const q = input.value.trim();
    if (!q) { box.style.display='none'; box.innerHTML=''; return; }
    try {
      const res = await fetch(`controllers/productController.php?action=buscar&q=${encodeURIComponent(q)}`);
      const items = await res.json();
      box.innerHTML = '';
      items.forEach(p => {
        const a = document.createElement('a');
        a.href = `producto.php?id=${p.id_producto}`;
        a.className = 'list-group-item list-group-item-action';
        a.textContent = `#${p.id_producto} — ${p.nombre_producto}`;
        box.appendChild(a);
      });
      box.style.display = items.length ? 'block' : 'none';
    } catch(e) {
      box.style.display='none'; box.innerHTML='';
    }
  });

  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !box.contains(e.target)) {
      box.style.display='none'; box.innerHTML='';
    }
  });
})();
</script>
