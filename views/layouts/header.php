<?php
$current_page = basename($_SERVER['PHP_SELF']);
$page_titles  = [
    'dashboard.php'              => 'Dashboard Principal',
    'consulta_aprendiz.php'      => 'Consulta por Aprendiz',
    'carga_masiva.php'           => 'Carga Masiva Juicios',
    'eliminacion_masiva.php'     => 'Eliminar Programas',
    'historial_importaciones.php'=> 'Historial de Importaciones',
    'fases_proyecto.php'         => 'Fases del Proyecto Formativo',
    'proyectos_formativos.php'   => 'Proyectos Formativos por Ficha',
    'dashboard_fases.php'        => 'Dashboard de Fases',
];
$title = $page_titles[$current_page] ?? 'Sistema SENA';

function navItem(string $href, string $label, string $icon, string $current): string {
    $base   = basename($href);
    $active = ($base === $current) ? 'active' : '';
    return "<a href=\"{$href}\" class=\"nav-item {$active}\" title=\"{$label}\">{$icon}<span>{$label}</span></a>";
}

$icons = [
'dashboard' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>',
'consulta'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>',
'carga'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>',
'juicios'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>',
'eliminar'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>',
'fases'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>',
'dfases'    => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>',
'historial' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Sistema de Juicios Evaluativos SENA — Gestión de competencias y resultados de aprendizaje">
  <title><?= htmlspecialchars($title) ?> — SENA Juicios</title>
  <link rel="stylesheet" href="/sistema_gestion_datos/assets/css/styles.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script>
    // Theme persistence - execute immediately to avoid flash
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
      document.documentElement.setAttribute('data-theme', savedTheme);
    }
  </script>
</head>
<body>

<button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menú">
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-logo">
        <div class="brand-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
        </div>
        <div class="brand-text">
          <h2>SENA Juicios</h2>
          <span>Sistema Evaluativo</span>
        </div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-label">Principal</div>
      <?= navItem('/sistema_gestion_datos/views/pages/dashboard.php',         'Dashboard',              $icons['dashboard'], $current_page) ?>
      <?= navItem('/sistema_gestion_datos/views/pages/consulta_aprendiz.php', 'Consulta por Aprendiz',  $icons['consulta'],  $current_page) ?>
      <div class="nav-label">Gestión de Datos</div>
      <?= navItem('/sistema_gestion_datos/views/pages/carga_masiva.php',           'Carga Masiva Juicios',      $icons['carga'],     $current_page) ?>
      <?= navItem('/sistema_gestion_datos/views/pages/eliminacion_masiva.php',    'Eliminar Programas',        $icons['eliminar'],  $current_page) ?>
      <?= navItem('/sistema_gestion_datos/views/pages/historial_importaciones.php','Historial Importaciones',   $icons['historial'], $current_page) ?>
      <div class="nav-label">Fases Formativas</div>
      <?= navItem('/sistema_gestion_datos/views/pages/proyectos_formativos.php','Proyectos Formativos', $icons['dfases'],    $current_page) ?>
      <?= navItem('/sistema_gestion_datos/views/pages/fases_proyecto.php',    'Gestión de Fases',       $icons['fases'],     $current_page) ?>
      <?= navItem('/sistema_gestion_datos/views/pages/dashboard_fases.php',   'Dashboard de Fases',     $icons['dashboard'], $current_page) ?>
    </nav>
    <div class="sidebar-footer">
      SENA &copy; <?= date('Y') ?> — Sistema Evaluativo
    </div>
  </aside>

  <main class="main-content">
    <div class="topbar fade-in">
      <h1><?= htmlspecialchars($title) ?></h1>
      <div class="topbar-right">
        <button id="themeToggleBtn" class="btn btn-secondary btn-icon" title="Cambiar Tema" aria-label="Cambiar Tema">
          <svg id="themeIconSun" style="display:none;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg>
          <svg id="themeIconMoon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" /></svg>
        </button>
        <span class="topbar-badge"><?= date('d/m/Y') ?></span>
      </div>
    </div>

<script>
// ── Sidebar toggle (mobile only < 781px) ──────────────────────────────────
const sidebar        = document.getElementById('sidebar');
const mobileMenuBtn  = document.getElementById('mobileMenuBtn');
const sidebarOverlay = document.getElementById('sidebarOverlay');

function openSidebar() {
  sidebar.classList.add('open');
  sidebarOverlay.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeSidebar() {
  sidebar.classList.remove('open');
  sidebarOverlay.classList.remove('open');
  document.body.style.overflow = '';
}

if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openSidebar);
if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

// Close on ESC
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });

// Ensure sidebar resets when window resizes above 780px
window.addEventListener('resize', () => {
  if (window.innerWidth > 780) closeSidebar();
});

// ── Theme Toggle ──────────────────────────────────────────────────────────
const themeBtn  = document.getElementById('themeToggleBtn');
const iconSun   = document.getElementById('themeIconSun');
const iconMoon  = document.getElementById('themeIconMoon');
const htmlEl    = document.documentElement;

function updateThemeIcons() {
  const isLight = htmlEl.getAttribute('data-theme') === 'light';
  iconSun.style.display  = isLight ? 'none'  : 'block';
  iconMoon.style.display = isLight ? 'block' : 'none';
}

if (themeBtn) {
  updateThemeIcons();
  themeBtn.addEventListener('click', () => {
    const newTheme = htmlEl.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    htmlEl.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcons();

    if (typeof Chart !== 'undefined') {
      const isLight = newTheme === 'light';
      Chart.defaults.color = isLight ? '#475569' : '#94a3b8';
      Chart.defaults.scale.grid.color = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.05)';
      Object.values(Chart.instances).forEach(c => c.update());
    }
  });
}
</script>
