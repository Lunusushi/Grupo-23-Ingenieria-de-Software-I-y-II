<?php
// perfil.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (
  empty($_SESSION['user']) ||
  ($_SESSION['user']['type'] ?? ($_SESSION['user_type'] ?? null)) !== 'cliente'
) {
    // Opción A: redirigir a inicio
    header('Location: index.php');
    exit;
}
require_once __DIR__ . '/config/MySqlDb.php';
require_once __DIR__ . '/partials/navbar.php';

$userId = (int)$_SESSION['user']['id'];

// 1) Traer datos actuales del usuario (por si avatar/cover cambiaron fuera de sesión)
$stmt = $conn->prepare("SELECT id_usuario, nombre, telefono, avatar_url, cover_url FROM USUARIO WHERE id_usuario = ?");
$stmt->execute([$userId]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$u) { http_response_code(404); die("Usuario no encontrado."); }

$mensaje = "";
$error   = "";

// 2) Helper para subir imagen
function subir_imagen(array $file, string $dir, string $prefix, int $maxBytes = 2_000_000) {
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return [null, null];

    // Validar tamaño
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException("La imagen supera el límite de " . number_format($maxBytes/1024/1024, 1) . " MB.");
    }

    // Validar mime
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $ext   = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => null
    };
    if (!$ext) {
        throw new RuntimeException("Formato de imagen no permitido. Usa JPG/PNG/WEBP.");
    }

    // Asegurar directorio
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true)) throw new RuntimeException("No se pudo crear el directorio de subida.");
    }

    // Nombre de archivo
    $fname = $prefix . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest  = rtrim($dir, '/\\') . '/' . $fname;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException("No se pudo guardar la imagen en el servidor.");
    }

    // Retorna ruta relativa usable en <img src="...">
    return [$dest, $mime];
}

// 3) Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre   = trim($_POST['nombre']   ?? $u['nombre']   ?? '');
        $telefono = trim($_POST['telefono'] ?? $u['telefono'] ?? '');

        $avatar_url = $u['avatar_url'] ?? null;
        $cover_url  = $u['cover_url']  ?? null;

        // Avatar
        if (!empty($_FILES['avatar']['tmp_name'])) {
            [$path, $mime] = subir_imagen($_FILES['avatar'], __DIR__ . '/uploads/avatars', 'avatar-' . $userId);
            // Guardar ruta relativa desde docroot (ajusta si tu docroot difiere)
            $avatar_url = 'uploads/avatars/' . basename($path);
        }

        // Portada
        if (!empty($_FILES['cover']['tmp_name'])) {
            [$path, $mime] = subir_imagen($_FILES['cover'], __DIR__ . '/uploads/covers', 'cover-' . $userId);
            $cover_url = 'uploads/covers/' . basename($path);
        }

        // Actualizar en BD
        $up = $conn->prepare("UPDATE USUARIO SET nombre=?, telefono=?, avatar_url=?, cover_url=? WHERE id_usuario=?");
        $up->execute([$nombre, $telefono, $avatar_url, $cover_url, $userId]);

        // Refrescar datos
        $stmt = $conn->prepare("SELECT id_usuario, nombre, telefono, avatar_url, cover_url FROM USUARIO WHERE id_usuario = ?");
        $stmt->execute([$userId]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        // Refrescar sesión (para que navbar muestre nombre actualizado)
        $_SESSION['user']['name'] = $u['nombre'];

        $mensaje = "✅ Perfil actualizado correctamente.";
    } catch (Throwable $e) {
        $error = "❌ " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mi Perfil | Los Cobres</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    .cover {
      height: 220px;
      background-color: #f6f6f6;
      background-position: center;
      background-size: cover;
      border-radius: .5rem;
    }
    .avatar {
      width: 120px; height: 120px; object-fit: cover;
      border-radius: 50%; border: 4px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,.08);
      margin-top: -60px; background: #fff;
    }
  </style>
</head>
<body>

<div class="container my-4">

  <!-- Portada -->
  <div class="cover mb-3" style="background-image:url('<?= $u['cover_url'] ? htmlspecialchars($u['cover_url']) : '' ?>');"></div>

  <div class="d-flex align-items-center mb-4">
    <img class="avatar me-3" src="<?= $u['avatar_url'] ? htmlspecialchars($u['avatar_url']) : 'https://via.placeholder.com/120x120?text=Avatar' ?>" alt="Avatar">
    <div>
      <h1 class="h4 mb-1"><?= htmlspecialchars($u['nombre'] ?? 'Sin nombre') ?></h1>
      <div class="text-muted small">ID #<?= (int)$u['id_usuario'] ?></div>
    </div>
  </div>

  <?php if ($mensaje): ?><div class="alert alert-success"><?= $mensaje ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

  <div class="card p-3">
    <form method="POST" enctype="multipart/form-data" class="row g-3">
      <div class="col-12 col-md-6">
        <label class="form-label">Nombre</label>
        <input name="nombre" class="form-control" value="<?= htmlspecialchars($u['nombre'] ?? '') ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Teléfono</label>
        <input name="telefono" class="form-control" value="<?= htmlspecialchars($u['telefono'] ?? '') ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Avatar (JPG/PNG/WebP, máx 2MB)</label>
        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="form-control">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Portada (JPG/PNG/WebP, máx 2MB)</label>
        <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="form-control">
      </div>
      <div class="col-12 text-end">
        <button class="btn btn-primary">Guardar cambios</button>
      </div>
    </form>
  </div>

</div>
</body>
</html>