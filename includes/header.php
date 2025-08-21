<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>ESIBOC-DNBC</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="app/globals.css" rel="stylesheet">
  
  <!-- React y dependencias para componentes interactivos -->
  <script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
  <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
  
  <style>
      .gradient-bg {
          background: linear-gradient(135deg, #1e2a4a 0%, #2c3a60 100%);
      }
      .card-shadow {
          box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      }
      .btn-primary {
          background: linear-gradient(135deg, #e63946 0%, #d62828 100%);
          transition: all 0.3s ease;
      }
      .btn-primary:hover {
          transform: translateY(-1px);
          box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
      }
      .sidebar-item {
          transition: all 0.3s ease;
      }
      .sidebar-item:hover {
          background-color: rgba(255, 255, 255, 0.1);
          transform: translateX(4px);
      }
      .navbar-glass {
          backdrop-filter: blur(10px);
          background-color: #1e2a4a;
          color: #ffffff;
      }
  </style>
</head>
<body class="bg-gray-50 font-sans">
  <?php if (isset($_SESSION['user_id'])): ?>
  <!-- Navigation -->
  <nav class="navbar-glass border-b border-gray-700 fixed w-full z-30 top-0">
      <div class="px-3 py-3 lg:px-5 lg:pl-3">
          <div class="flex items-center justify-between">
              <div class="flex items-center justify-start">
                  <button id="toggleSidebarMobile" class="lg:hidden p-2 text-white rounded cursor-pointer hover:text-gray-200 hover:bg-gray-700">
                      <i class="fas fa-bars text-lg"></i>
                  </button>
                  <a href="dashboard.php" class="text-xl font-bold ml-2 md:mr-24 flex items-center">
                      <img src="public/images/dnbc-logo.png" alt="DNBC Logo" class="h-8 mr-2">
                      <span class="text-white">ESIBOC</span>-<span class="text-red-500">DNBC</span>
                  </a>
              </div>
              <div class="flex items-center">
                  <div class="flex items-center ml-3">
                      <div class="relative">
                          <button class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-red-500" id="user-menu-button">
                              <span class="sr-only">Abrir menú de usuario</span>
                              <div class="w-8 h-8 bg-gradient-to-r from-red-500 to-red-600 rounded-full flex items-center justify-center text-white font-semibold">
                                  <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                              </div>
                          </button>
                          <div class="hidden absolute right-0 z-50 my-4 text-base list-none bg-white divide-y divide-gray-100 rounded shadow" id="dropdown-user">
                              <div class="px-4 py-3">
                                  <p class="text-sm text-gray-900"><?php echo $_SESSION['user_name']; ?></p>
                                  <p class="text-sm font-medium text-gray-500 truncate"><?php echo $_SESSION['user_role']; ?></p>
                              </div>
                              <ul class="py-1">
                                  <li><a href="perfil.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Perfil</a></li>
                                  <li><a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Cerrar Sesión</a></li>
                              </ul>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </nav>

  <!-- Sidebar -->
  <aside id="logo-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full border-r border-gray-700 sm:translate-x-0" style="background-color: #1e2a4a;">
      <div class="h-full px-3 pb-4 overflow-y-auto" style="background: linear-gradient(to bottom, #1e2a4a, #2c3a60);">
          <ul class="space-y-2 font-medium text-white">
    <li>
        <a href="dashboard.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-tachometer-alt w-5 h-5"></i>
            <span class="ml-3">Dashboard</span>
        </a>
    </li>
    
    <?php if ($_SESSION['user_role'] === 'Administrador General'): ?>
    <li>
        <a href="usuarios.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'usuarios.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'usuarios.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-users w-5 h-5"></i>
            <span class="ml-3">Usuarios</span>
        </a>
    </li>
    <li>
        <a href="escuelas.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'escuelas.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'escuelas.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-school w-5 h-5"></i>
            <span class="ml-3">Escuelas</span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($_SESSION['user_role'] === 'Escuela'): ?>
    <li>
        <a href="usuarios.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'usuarios.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'usuarios.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-users w-5 h-5"></i>
            <span class="ml-3">Usuarios</span>
        </a>
    </li>
    <li>
        <a href="cursos.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'cursos.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'cursos.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-graduation-cap w-5 h-5" style="color: #f1c232;"></i>
            <span class="ml-3">Cursos</span>
        </a>
    </li>
    <li>
        <a href="participantes.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'participantes.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'participantes.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-user-graduate w-5 h-5"></i>
            <span class="ml-3">Participantes</span>
        </a>
    </li>
    <li>
        <a href="configuracion-escuela.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'configuracion-escuela.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'configuracion-escuela.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-cogs w-5 h-5"></i>
            <span class="ml-3">Configuración Escuela</span>
        </a>
    </li>
    <li>
        <a href="configuracion-certificados.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'configuracion-certificados.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'configuracion-certificados.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-certificate w-5 h-5"></i>
            <span class="ml-3">Configuración Certificados</span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($_SESSION['user_role'] === 'Coordinador'): ?>
    <li>
        <a href="cursos.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'cursos.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'cursos.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-graduation-cap w-5 h-5" style="color: #f1c232;"></i>
            <span class="ml-3">Cursos</span>
        </a>
    </li>
    <li>
        <a href="participantes.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'participantes.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'participantes.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-user-graduate w-5 h-5"></i>
            <span class="ml-3">Participantes</span>
        </a>
    </li>
    <li>
        <a href="calificaciones.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'calificaciones.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'calificaciones.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-clipboard-list w-5 h-5"></i>
            <span class="ml-3">Calificaciones</span>
        </a>
    </li>
    <li>
        <a href="configurar-firma.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'configurar-firma.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'configurar-firma.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-signature w-5 h-5"></i>
            <span class="ml-3">Configurar Firma</span>
        </a>
    </li>
    <li>
        <a href="documentos.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'documentos.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'documentos.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-file-alt w-5 h-5"></i>
            <span class="ml-3">Documentos</span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($_SESSION['user_role'] === 'Director de Escuela'): ?>
    <li>
        <a href="configuracion-escuela.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'configuracion-escuela.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'configuracion-escuela.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-cogs w-5 h-5"></i>
            <span class="ml-3">Configuración Escuela</span>
        </a>
    </li>
    <li>
        <a href="configurar-firma.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'configurar-firma.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'configurar-firma.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-signature w-5 h-5"></i>
            <span class="ml-3">Configurar Firma</span>
        </a>
    </li>
    <li>
        <a href="documentos.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'documentos.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'documentos.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-file-alt w-5 h-5"></i>
            <span class="ml-3">Documentos</span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($_SESSION['user_role'] === 'Educación DNBC'): ?>
    <li>
        <a href="documentos.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'documentos.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'documentos.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-file-alt w-5 h-5"></i>
            <span class="ml-3">Documentos</span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($_SESSION['user_role'] === 'Dirección Nacional'): ?>
    <li>
        <a href="configurar-firma.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'configurar-firma.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'configurar-firma.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-signature w-5 h-5"></i>
            <span class="ml-3">Configurar Firma</span>
        </a>
    </li>
    <li>
        <a href="documentos.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'documentos.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'documentos.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-file-alt w-5 h-5"></i>
            <span class="ml-3">Documentos</span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($_SESSION['user_role'] === 'Participante'): ?>
    <li>
        <a href="participante-dashboard.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'participante-dashboard.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'participante-dashboard.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-book w-5 h-5"></i>
            <span class="ml-3">Mis Cursos</span>
        </a>
    </li>
    <li>
        <a href="get-available-certificates.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'get-available-certificates.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'get-available-certificates.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-certificate w-5 h-5"></i>
            <span class="ml-3">Certificados</span>
        </a>
    </li>
    <?php endif; ?>
    
    <!-- Reportes para todos los roles -->
    <li>
        <a href="reportes.php" class="sidebar-item flex items-center p-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'reportes.php') ? 'bg-white bg-opacity-10' : ''; ?>" style="color: white; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#2c3a60'; this.style.color='#f1c232';" onmouseout="this.style.backgroundColor='<?php echo (basename($_SERVER['PHP_SELF']) == 'reportes.php') ? 'rgba(255,255,255,0.1)' : 'transparent'; ?>'; this.style.color='white';">
            <i class="fas fa-chart-bar w-5 h-5"></i>
            <span class="ml-3">Reportes</span>
        </a>
    </li>
</ul>
      </div>
  </aside>

  <div class="p-4 sm:ml-64">
      <div class="p-4 mt-14">
  <?php endif; ?>
