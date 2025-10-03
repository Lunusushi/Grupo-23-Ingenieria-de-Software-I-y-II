<?php
  if (session_status() === PHP_SESSION_NONE) session_start();
  require_once __DIR__ . '/config/MySqlDb.php';
  require_once __DIR__ . '/controllers/ProductController.php';

  $promos = ProductController::obtenerPromosHome($conn, 5);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Inicio | Los Cobres</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    .carousel-container { margin: 2rem auto; }
    #carruselReligioso {
      max-width: 90%;
      margin: 0 auto;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0,0,0,0.15);
    }
    #carruselReligioso .carousel-item img {
      width: 100%; height: 500px; object-fit: cover;
    }
    @media (max-width: 992px) { #carruselReligioso .carousel-item img { height: 350px; } }
    @media (max-width: 576px) { #carruselReligioso .carousel-item img { height: 220px; } }
    .carousel-caption { background: rgba(0,0,0,0.4); padding: .5rem 1rem; border-radius: 4px; }
  </style>
</head>
<body class="d-flex flex-column min-vh-100">

  <?php include __DIR__ . '/partials/navbar.php'; ?>

  <main class="flex-grow-1">
    <div class="container mt-5">
      <div class="text-center">
        <h1 class="display-4">Bienvenido a Los Cobres</h1>
        <p class="lead"></p>

        <!-- Carrusel XL con fade (se mantiene) -->
        <div class="carousel-container">
          <div id="carruselReligioso" class="carousel slide carousel-fade" data-bs-ride="carousel">
            <!-- Indicadores -->
            <div class="carousel-indicators">
              <button type="button" data-bs-target="#carruselReligioso" data-bs-slide-to="0" class="active"></button>
              <button type="button" data-bs-target="#carruselReligioso" data-bs-slide-to="1"></button>
              <button type="button" data-bs-target="#carruselReligioso" data-bs-slide-to="2"></button>
              <button type="button" data-bs-target="#carruselReligioso" data-bs-slide-to="3"></button>
            </div>

            <!-- Slides (puedes hacerlos clickeables, ver sección 2) -->
            <div class="carousel-inner">
              <div class="carousel-item active">
                <img src="public/css/img/front-tienda.jpg" alt="Frente de la Tienda">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Frente de la Tienda</h5>
                </div>
              </div>
              <div class="carousel-item">
                <img src="public/css/img/cruz.jpg" alt="Cruz Religiosa">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Cruz Religiosa</h5>
                </div>
              </div>
              <div class="carousel-item">
                <img src="public/css/img/velaYBiblia.png" alt="Vela y Biblia">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Vela y Biblia</h5>
                </div>
              </div>
              <div class="carousel-item">
                <img src="public/css/img/cruz2.jpg" alt="Cruz">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Cruz</h5>
                </div>
              </div>
            </div>

            <!-- Controles -->
            <button class="carousel-control-prev" type="button" data-bs-target="#carruselReligioso" data-bs-slide="prev">
              <span class="carousel-control-prev-icon"></span>
              <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carruselReligioso" data-bs-slide="next">
              <span class="carousel-control-next-icon"></span>
              <span class="visually-hidden">Siguiente</span>
            </button>
          </div>
        </div>
        <!-- /Carrusel -->

        <hr class="my-4">

        <div class="carousel-container">
          <?php if (!empty($promos)): ?>
            <div id="carouselPromos" class="carousel slide carousel-fade" data-bs-ride="carousel">
              <div class="carousel-indicators">
                <?php foreach ($promos as $i => $_): ?>
                  <button type="button" data-bs-target="#carouselPromos" data-bs-slide-to="<?= $i ?>" class="<?= $i===0?'active':'' ?>"></button>
                <?php endforeach; ?>
              </div>
              <div class="carousel-inner">
                <?php foreach ($promos as $i => $ph): 
                  $img = $ph['imagen_url'] ?: $ph['url_imagen_principal'];
                  $title = $ph['titulo'] ?: $ph['nombre_producto'];
                  $sub   = $ph['subtitulo'] ?: ('$'.number_format((float)$ph['precio_unitario'],0,',','.'));
                ?>
                  <div class="carousel-item <?= $i===0?'active':'' ?>">
                    <a href="producto.php?id=<?= (int)$ph['id_producto'] ?>" class="d-block position-relative">
                      <img src="<?= htmlspecialchars($img) ?>" class="w-100" style="height:500px;object-fit:cover" alt="<?= htmlspecialchars($title) ?>">
                      <div class="carousel-caption d-none d-md-block" style="background:rgba(0,0,0,0.4);border-radius:.25rem;">
                        <h5><?= htmlspecialchars($title) ?></h5>
                        <p><?= htmlspecialchars($sub) ?></p>
                      </div>
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
              <button class="carousel-control-prev" type="button" data-bs-target="#carouselPromos" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
                <span class="visually-hidden">Anterior</span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#carouselPromos" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
                <span class="visually-hidden">Siguiente</span>
              </button>
            </div>
          <?php else: ?>
            <!-- Fallback: tu carrusel actual tal cual -->
            <!-- pega aquí tu carrusel estático existente -->
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
