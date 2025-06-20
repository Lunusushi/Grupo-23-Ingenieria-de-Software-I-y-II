<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Inicio | Los Cobres</title>
  <!-- Bootstrap CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
  <style>
    .carousel-container {
      margin: 2rem auto;
    }
    #carruselReligioso {
      max-width: 90%;
      margin: 0 auto;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0,0,0,0.15);
    }
    #carruselReligioso .carousel-item img {
      width: 100%;
      height: 500px;
      object-fit: cover;
    }
    @media (max-width: 992px) {
      #carruselReligioso .carousel-item img {
        height: 350px;
      }
    }
    @media (max-width: 576px) {
      #carruselReligioso .carousel-item img {
        height: 220px;
      }
    }
    .carousel-caption {
      background: rgba(0,0,0,0.4);
      padding: 0.5rem 1rem;
      border-radius: 4px;
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/partials/navbar.php'; ?>

  <div class="container mt-5">
    <div class="text-center">
      <h1 class="display-4">Bienvenido a Los Cobres</h1>
      <p class="lead"></p>

      <!-- Carrusel XL con fade -->
      <div class="carousel-container">
        <div
          id="carruselReligioso"
          class="carousel slide carousel-fade"
          data-bs-ride="carousel"
        >

          <!-- Indicadores -->
          <div class="carousel-indicators">
            <button type="button" data-bs-target="#carruselReligioso" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#carruselReligioso" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#carruselReligioso" data-bs-slide-to="2"></button>
            <button type="button" data-bs-target="#carruselReligioso" data-bs-slide-to="3"></button>
          </div>

          <!-- Slides -->
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
    </div>
  </div>

  <!-- Bootstrap JS Bundle -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  ></script>
</body>
</html>
