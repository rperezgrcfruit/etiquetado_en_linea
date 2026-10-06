<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Packing - GrcFruit SPA</title>
  <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- AdminLTE CSS (v3.2, BS5 compatible) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="/packing/view/assets/css/styles.css?v=<?php echo filemtime('/var/www/grc/packing/view/assets/css/styles.css'); ?>">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.1/dist/sweetalert2.all.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.1/dist/sweetalert2.min.css" rel="stylesheet">
  <style>
    body {
      font-size: 12px;
    }
    .sortable-table thead th {
      position: sticky;
      top: 0;
      z-index: 10;
    }
    /* Navbar profesional — fondo gris azulado oscuro, no negro puro */
    .navbar-profesional {
      background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
      border-bottom: 2px solid #4caf50;
    }
    .navbar-profesional .navbar-text,
    .navbar-profesional .navbar-brand,
    .navbar-profesional .btn-outline-light {
      color: #e9ecef !important;
    }
    .navbar-profesional .navbar-text strong {
      color: #4caf50 !important;
    }
    .navbar-profesional .btn-outline-light {
      border-color: #495057;
    }
    .navbar-profesional .btn-outline-light:hover {
      background-color: #4caf50 !important;
      border-color: #4caf50;
      color: #fff !important;
    }
  </style>
</head>

<body>
  <div class="wrapp">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-profesional border-bottom">
          <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center">
            <!-- Left: usuario, temporada, empresa -->
            <div class="d-flex align-items-center gap-3 flex-wrap">
              <span class="navbar-text fw-semibold">
                <i class="fas fa-user me-1"></i>Usuario: <strong><?php echo htmlspecialchars($_SESSION['username'] ?? '-'); ?></strong>
              </span>
              <span class="navbar-text fw-semibold">
                <i class="fas fa-calendar-alt me-1"></i>Temporada: <strong><?php echo defined('TEMPORADA_ACTUAL') ? TEMPORADA_ACTUAL : date('Y'); ?></strong>
              </span>
              <span class="navbar-text fw-semibold">
                <i class="fas fa-building me-1"></i>Empresa: <strong><?php echo htmlspecialchars($_SESSION['empresa'] ?? '-'); ?></strong>
              </span>
            </div>

            <!-- Center: title -->
            <span class="navbar-text fw-bold fs-5 text-center" style="color: #4caf50; flex: 1;">
              Administracion de Procesos Packing
            </span>

            <!-- Right: Dashboard button + Salir -->
            <div class="d-flex align-items-center gap-2">
              <a href="/packing/view/main.php" class="btn btn-success px-4 py-2">
                <i class="fas fa-home me-2"></i>Dashboard
              </a>
              <a href="/packing/close" class="btn btn-outline-light px-4 py-2">
                <i class="fas fa-sign-out-alt me-1"></i>Salir
              </a>
            </div>
          </div>
        </nav>

    <!-- Loading overlay -->
    <div id="spinner"></div>
    <div id="overlay"></div>
