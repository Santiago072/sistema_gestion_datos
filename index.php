<?php
// Welcome Page
$title = "Bienvenido — SENA Juicios Evaluativos";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="/sistema_gestion_datos/assets/css/styles.css">
  <style>
    /* Estilos específicos para la landing */
    body {
      margin: 0;
      padding: 0;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, var(--bg) 0%, var(--bg2) 100%);
      font-family: 'Inter', system-ui, sans-serif;
      overflow: hidden;
    }
    
    .landing-container {
      max-width: 600px;
      padding: 40px;
      text-align: center;
      z-index: 10;
      position: relative;
    }

    .brand-icon-large {
      width: 80px;
      height: 80px;
      background: var(--primary);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px auto;
      box-shadow: 0 10px 25px var(--primary-glow);
      animation: float 6s ease-in-out infinite;
    }

    .brand-icon-large svg {
      width: 40px;
      height: 40px;
      color: #fff;
    }

    .landing-title {
      font-size: 2.5rem;
      font-weight: 800;
      color: var(--text);
      margin-bottom: 12px;
      letter-spacing: -0.5px;
    }

    .landing-title span {
      color: var(--primary);
    }

    .landing-subtitle {
      font-size: 1.1rem;
      color: var(--text-muted);
      line-height: 1.6;
      margin-bottom: 40px;
      max-width: 500px;
      margin-left: auto;
      margin-right: auto;
    }

    .btn-enter {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 14px 28px;
      font-size: 1.1rem;
      font-weight: 600;
      background: var(--primary);
      color: #fff;
      border-radius: 50px;
      text-decoration: none;
      box-shadow: 0 8px 20px var(--primary-glow);
      transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .btn-enter:hover {
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 12px 25px var(--primary-glow);
      color: #fff;
    }

    .btn-enter:active {
      transform: translateY(0) scale(0.98);
    }

    .btn-enter svg {
      width: 20px;
      height: 20px;
      transition: transform 0.3s;
    }

    .btn-enter:hover svg {
      transform: translateX(4px);
    }

    /* Fondo animado sutil */
    .bg-shape {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      z-index: 0;
      opacity: 0.4;
      animation: pulse 10s infinite alternate;
    }
    
    .bg-shape-1 {
      width: 400px;
      height: 400px;
      background: var(--primary-glow);
      top: -100px;
      left: -100px;
    }

    .bg-shape-2 {
      width: 300px;
      height: 300px;
      background: rgba(57, 217, 0, 0.2);
      bottom: -50px;
      right: -50px;
      animation-delay: -5s;
    }

    @keyframes float {
      0% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
      100% { transform: translateY(0px); }
    }

    @keyframes pulse {
      0% { transform: scale(1); opacity: 0.3; }
      100% { transform: scale(1.2); opacity: 0.5; }
    }
  </style>
  <script>
    // Recuperar tema
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) { document.documentElement.setAttribute('data-theme', savedTheme); }
  </script>
</head>
<body>

  <!-- Elementos de fondo -->
  <div class="bg-shape bg-shape-1"></div>
  <div class="bg-shape bg-shape-2"></div>

  <div class="landing-container fade-in">
    <div class="brand-icon-large">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
      </svg>
    </div>
    
    <h1 class="landing-title">SENA <span>Juicios Evaluativos</span></h1>
    <p class="landing-subtitle">
      Plataforma centralizada para la gestión, seguimiento e importación masiva de resultados y competencias de los aprendices.
    </p>

    <a href="/sistema_gestion_datos/views/pages/dashboard.php" class="btn-enter">
      Ingresar al Sistema
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
      </svg>
    </a>
  </div>

</body>
</html>
