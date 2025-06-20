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
      display: flex;
      justify-content: center;
      margin: 2rem 0;
    }
    #carruselReligioso {
      width: 100%;
      max-width: 700px;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 16px rgba(0,0,0,0.1);
    }
    #carruselReligioso img {
      width: 100%;
      height: 320px;
      object-fit: cover;
    }
    @media (max-width: 768px) {
      #carruselReligioso img {
        height: 180px;
      }
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/partials/navbar.php'; ?>

  <div class="container mt-5">
    <div class="text-center">
      <h1 class="display-4">Bienvenido a Los Cobres</h1>
      <p class="lead">awa</p>

      <!-- Carrusel Religioso (local) -->
      <div class="carousel-container">
        <div
          id="carruselReligioso"
          class="carousel slide"
          data-bs-ride="carousel"
        >
          <div class="carousel-indicators">
            <button type="button" data-bs-target="#carruselReligioso" data-bs-slide-to="0" class="active" aria-current="true"></button>
            <button type="button" data-bs-target="#carruselReligioso" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#carruselReligioso" data-bs-slide-to="2"></button>
          </div>
          <div class="carousel-inner">
            <div class="carousel-item active">
              <img src="public\img\front-tienda.jpg" class="d-block" alt="Tienda de Los Cobres">
            </div>
            <div class="carousel-item">
              <img src="public\img\cruz.jpg" class="d-block" alt="Cruz">
            </div>
            <div class="carousel-item">
              <img src="public\img\velaYBilbia.jpg" class="d-block" alt="Vela y Biblia">
            </div>
            <div class="carousel-item">
              <img src="public\img\figurasReligiosas.jpg" class="d-block" alt="Figuras Religiosas">
            </div>
          </div>
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
