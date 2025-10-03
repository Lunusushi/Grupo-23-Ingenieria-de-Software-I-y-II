<?php
    if (session_status() === PHP_SESSION_NONE) session_start();
    $storeName = 'Los Cobres';
    $address   = 'Av. Ejemplo 123, Santiago, Chile';
    // Puedes usar también coordenadas: $mapQuery = '-33.45,-70.666';
    $mapQuery  = $storeName . ' ' . $address;

    $embedSrc       = 'https://www.google.com/maps?q=' . urlencode($mapQuery) . '&hl=es&z=16&output=embed';
    $directionsUrl  = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($mapQuery);

    // Pendiente el aprender a usar esta wea jej
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Ubicación | Los Cobres</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">
  <?php include __DIR__ . '/partials/navbar.php'; ?>
  <main class="flex-grow-1">
    <div class="container my-4">
        <h1 class="h4 mb-3">Cómo llegar</h1>
        <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
            <div class="card-body">
                <h5>Los Cobres</h5>
                <p class="mb-1">Embajador Doussinague 1767, Vitacura, Chile</p>
                <p class="mb-1">Horario: Lun–Sáb 10:00–19:00</p>
                <p class="mb-1">Tel: +56 2 2345 6789</p>
            </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="ratio ratio-16x9">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d208.21010491211845!2d-70.56841271646306!3d-33.38772713658648!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9662c92b63c97363%3A0xf2c5155b4fe663b2!2sEmbajador%20Doussinague%201767%2C%207640607%20Vitacura%2C%20Regi%C3%B3n%20Metropolitana%2C%20Chile!5e0!3m2!1ses!2sar!4v1759445509314!5m2!1ses!2sar" width="400" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        <a class="btn btn-outline-primary mt-2" href="<?= $directionsUrl ?>" target="_blank" rel="noopener">
            Abrir en Google Maps
        </a>
        </div>
        </div>
    </div>
    </main>
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
