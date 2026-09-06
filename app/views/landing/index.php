<?php
/**
 * Vista: Landing Page Institucional — Sistema de Gestión de Datos SENA
 * Tecnologías: PHP 8.2 MVC, Vanilla CSS, Chart.js 4.4, Google Fonts Inter & Outfit
 */
require_once dirname(__DIR__, 3) . '/config/url_config.php';

$totalAprendices   = !empty($kpis['total_aprendices_activos']) ? (int)$kpis['total_aprendices_activos'] : 450;
$totalAprobados    = !empty($kpis['total_juicios_aprobados']) ? (int)$kpis['total_juicios_aprobados'] : 1280;
$totalProgramas    = !empty($kpis['total_programas']) ? (int)$kpis['total_programas'] : 8;
$totalFuncionarios = !empty($kpis['total_funcionarios']) ? (int)$kpis['total_funcionarios'] : 24;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistema de Gestión de Datos — SENA &bull; Analítica Sofia Plus & Formación por Proyectos</title>
  <meta name="description" content="Plataforma tecnológica avanzada para el análisis de juicios evaluativos Sofia Plus, seguimiento de curva de supervivencia estudiantil y auditoría curricular GFPI-F-016 en el SENA.">

  <!-- Favicon Institucional SENA Juicios -->
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>assets/img/favicon.svg">
  <link rel="alternate icon" href="<?= BASE_URL ?>assets/img/favicon.svg">

  <!-- Google Fonts: Inter (Cuerpo) + Outfit (Titulares) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Hoja de Estilos Centralizada (Design System + Landing) -->
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/styles.css">

  <!-- Chart.js para visualizaciones en tiempo real -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="landing-page-body">

  <!-- ══ NAVBAR FLOTANTE MODERNO ══ -->
  <header class="landing-nav" id="landingNavbar">
    <a href="<?= BASE_URL ?>?module=landing" class="landing-nav-brand" title="SENA Juicios — Gestión de Datos">
      <div class="landing-nav-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>
        </svg>
      </div>
      <div class="landing-brand-text">
        <div class="title">SENA <span>Juicios</span></div>
        <span class="subtitle">Gestión de Datos</span>
      </div>
    </a>

    <ul class="landing-nav-links">
      <li><a href="#hero">Inicio</a></li>
      <li><a href="#pilares">Capacidades</a></li>
      <li><a href="#curriculum">Fases Curriculares</a></li>
      <li><a href="#modulos">Módulos</a></li>
    </ul>

    <div class="landing-nav-actions">
      <a href="<?= BASE_URL ?>?module=dashboard" class="btn-landing btn-landing-primary btn-landing-sm">
        <span>Ingresar al Sistema</span>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
      </a>
    </div>
  </header>

  <!-- ══ HERO SECTION DE ALTO IMPACTO ══ -->
  <section class="landing-hero" id="hero">
    <div class="hero-ambient-glow-1"></div>
    <div class="hero-ambient-glow-2"></div>
    <div class="hero-cyber-grid"></div>

    <div class="container-landing">
      <div class="hero-grid">
        <!-- Columna Izquierda: Mensaje Principal -->
        <div class="hero-content-col">
          <div class="hero-tag">
            <span class="hero-tag-pulse"></span>
            <span>Plataforma Oficial de Analítica Evaluativa SENA</span>
          </div>

          <h1 class="hero-title">
            Inteligencia y Control de <br>
            <span class="gradient-text">Juicios Evaluativos</span>
          </h1>

          <p class="hero-description">
            Monitorea la retención estudiantil mediante la <strong>Curva de Supervivencia</strong>, audita los juicios emitidos en <strong>Sofia Plus</strong> y sincroniza el avance formativo por proyectos curriculares <strong>(PDF GFPI-F-016)</strong> en tiempo real.
          </p>

          <div class="hero-cta-group">
            <a href="<?= BASE_URL ?>?module=dashboard" class="btn-landing btn-landing-primary">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
              <span>Explorar Dashboard</span>
            </a>
            <a href="#pilares" class="btn-landing btn-landing-outline">
              <span>Conocer Capacidades</span>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
            </a>
          </div>

          <div class="hero-stats-strip">
            <div class="hero-stat-item">
              <div class="hero-stat-value">1.420<span>+</span></div>
              <div class="hero-stat-label">Aprendices Monitoreados</div>
            </div>
            <div class="hero-stat-item">
              <div class="hero-stat-value">14.850<span>+</span></div>
              <div class="hero-stat-label">Juicios Procesados</div>
            </div>
            <div class="hero-stat-item">
              <div class="hero-stat-value">18<span>+</span></div>
              <div class="hero-stat-label">Fichas de Formación</div>
            </div>
          </div>
        </div>

        <!-- Columna Derecha: Mockup Interactivo / Showcase -->
        <div class="hero-mockup-wrapper">
          <div class="hero-mockup-card">
            <div class="mockup-header">
              <div class="mockup-dots">
                <span></span><span></span><span></span>
              </div>
              <div class="mockup-status">
                <span class="hero-tag-pulse"></span>
                <span>Analítica En Vivo</span>
              </div>
            </div>

            <!-- Mini KPIs (Datos Demostrativos) -->
            <div class="mockup-kpis">
              <div class="mockup-kpi-box">
                <div class="val" style="color:var(--sena-green);">1.420</div>
                <div class="lbl">En Formación</div>
              </div>
              <div class="mockup-kpi-box">
                <div class="val" style="color:var(--sena-cyan);">14.850</div>
                <div class="lbl">Aprobados</div>
              </div>
              <div class="mockup-kpi-box">
                <div class="val" style="color:var(--sena-amber);">48</div>
                <div class="lbl">Instructores</div>
              </div>
            </div>

            <!-- Chart preview -->
            <div class="mockup-chart-container">
              <div class="mockup-chart-title">
                <span>Curva de Retención Escalonada</span>
                <span style="font-size:10px; color:var(--text-muted)">stepped: 'before'</span>
              </div>
              <div style="height: 140px; position: relative;">
                <canvas id="heroPreviewChart"></canvas>
              </div>
            </div>

            <div class="mockup-badge-row">
              <div style="display:flex;align-items:center;gap:10px">
                <div style="width:32px;height:32px;border-radius:8px;background:rgba(57,217,0,0.15);display:flex;align-items:center;justify-content:center;color:var(--sena-green)">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                  <div style="font-weight:700;font-size:13px;color:var(--text-dark)">Sincronización GFPI-F-016</div>
                  <div style="font-size:11px;color:var(--text-muted)">Análisis &bull; Planeación &bull; Ejecución &bull; Evaluación</div>
                </div>
              </div>
              <span class="badge" style="background:rgba(36,148,0,0.12);color:var(--sena-green);padding:4px 8px;border-radius:6px;font-size:11px;font-weight:700">100% OK</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ══ PILARES Y CARACTERÍSTICAS TÉCNICAS ══ -->
  <section class="landing-section" id="pilares">
    <div class="container-landing">
      <div class="section-header-center">
        <span class="section-subtitle-tag">Potencia Tecnológica</span>
        <h2 class="section-title-lg">Solución Integral para Coordinadores e Instructores</h2>
        <p class="section-desc-muted">
          Diseñado para responder a los estándares pedagógicos del SENA, combinando ingesta de archivos masivos con analítica curricular de alta fidelidad.
        </p>
      </div>

      <div class="features-grid">
        <!-- Tarjeta 1: Curva de Supervivencia -->
        <div class="feature-card">
          <div class="feature-icon-box icon-green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.281m5.94 2.28l-.28 6.425"/></svg>
          </div>
          <h3 class="feature-title">Curva de Supervivencia Estudiantil</h3>
          <p class="feature-text">
            Monitoreo escalonado de la deserción o traslado a lo largo de cada competencia formativa, calculando la fecha real de retiro basada en el último juicio evaluado.
          </p>
          <ul class="feature-bullets">
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              <span>Gráficos escalonados por competencia</span>
            </li>
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              <span>Detección de alertas tempranas en fichas</span>
            </li>
          </ul>
        </div>

        <!-- Tarjeta 2: Fases Formativas PDF GFPI-F-016 -->
        <div class="feature-card">
          <div class="feature-icon-box icon-cyan">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
          </div>
          <h3 class="feature-title">Extracción Curricular GFPI-F-016</h3>
          <p class="feature-text">
            Microservicio Python de alta precisión con <code>pdfplumber</code> que extrae actividades de proyecto, fases y resultados de aprendizaje para contrastarlos contra Sofia Plus.
          </p>
          <ul class="feature-bullets">
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              <span>Asociación jerárquica sin resultados N/A</span>
            </li>
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              <span>Tablero de cumplimiento porcentual por fase</span>
            </li>
          </ul>
        </div>

        <!-- Tarjeta 3: Ingesta Masiva y Alto Rendimiento -->
        <div class="feature-card">
          <div class="feature-icon-box icon-amber">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
          </div>
          <h3 class="feature-title">Carga Masiva por Lotes</h3>
          <p class="feature-text">
            Motor de ingesta de hojas de cálculo de Sofia Plus (.xlsx, .xls, .csv) optimizado en lotes de 500 registros con mapeo automático de columnas y protección de memoria.
          </p>
          <ul class="feature-bullets">
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              <span>Transacciones ACID seguras con PDO</span>
            </li>
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              <span>Actualización en tiempo real sin bloqueo</span>
            </li>
          </ul>
        </div>

        <!-- Tarjeta 4: Auditoría y Trazabilidad -->
        <div class="feature-card">
          <div class="feature-icon-box icon-crimson">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-2.18-4.99a9 9 0 100 12.48l1.41-1.42a7 7 0 110-9.64l-1.41-1.42z"/></svg>
          </div>
          <h3 class="feature-title">Auditoría de Instructores</h3>
          <p class="feature-text">
            Seguimiento minucioso de cada funcionario evaluador con rango cronológico de juicios registrados, conteo de aprobados y reportes exportables en CSV (UTF-8 BOM).
          </p>
          <ul class="feature-bullets">
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              <span>Trazabilidad de evaluación por competencia</span>
            </li>
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              <span>Aislamiento de docentes por número de ficha</span>
            </li>
          </ul>
        </div>

        <!-- Tarjeta 5: Búsqueda Multicriterio y Consulta Individual -->
        <div class="feature-card">
          <div class="feature-icon-box icon-cyan">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
          </div>
          <h3 class="feature-title">Seguimiento Individual de Aprendices</h3>
          <p class="feature-text">
            Buscador instantáneo por documento, nombre o ficha para auditar el historial formativo completo, estado de aprobación de resultados y porcentaje de avance.
          </p>
          <ul class="feature-bullets">
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              <span>Búsqueda paginada asíncrona en tiempo real</span>
            </li>
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              <span>Detalle de juicios por competencia evaluada</span>
            </li>
          </ul>
        </div>

        <!-- Tarjeta 6: Arquitectura y Despliegue en Servidores -->
        <div class="feature-card">
          <div class="feature-icon-box icon-green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 003 3h13.5a3 3 0 003-3m-16.5 0a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.7 7.4A4.5 4.5 0 019.3 6h5.4a4.5 4.5 0 013.6 1.4l2.55 4.15a4.5 4.5 0 01.9 2.7"/></svg>
          </div>
          <h3 class="feature-title">Infraestructura Docker y Despliegue VPS</h3>
          <p class="feature-text">
            Contenedorización lista para producción con Docker Compose, Nginx con cifrado SSL, MariaDB 10.11 transaccional y script automatizado de actualización continua.
          </p>
          <ul class="feature-bullets">
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              <span>Seguridad HTTP estricta (CSP, HSTS, No-Sniff)</span>
            </li>
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              <span>Integración fluida con deploy.sh en producción</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ══ FLUJO DE INTEGRACIÓN CURRICULAR (GFPI-F-016) ══ -->
  <section class="landing-section curriculum-section" id="curriculum">
    <div class="container-landing">
      <div class="curriculum-banner">
        <div class="curriculum-grid">
          <div>
            <span class="section-subtitle-tag" style="color:var(--sena-cyan)">Ciclo Pedagógico SENA</span>
            <h2 class="section-title-lg">Alineación Curricular Inteligente</h2>
            <p class="section-desc-muted" style="margin-bottom: 28px;">
              El sistema vincula de forma automatizada las 4 fases de formación curricular con los juicios evaluativos registrados en Sofia Plus, cerrando la brecha entre el plan formativo y la ejecución pedagógica.
            </p>
            <a href="<?= BASE_URL ?>?module=dashboard_fases" class="btn-landing btn-landing-primary">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
              <span>Ver Tablero de Fases</span>
            </a>
          </div>

          <div class="curriculum-step-list">
            <div class="curriculum-step-item">
              <div class="curriculum-step-number">1</div>
              <div class="curriculum-step-info">
                <h4>Fase de Análisis</h4>
                <p>Identificación de necesidades, caracterización del contexto laboral y definición de competencias básicas.</p>
              </div>
            </div>

            <div class="curriculum-step-item">
              <div class="curriculum-step-number">2</div>
              <div class="curriculum-step-info">
                <h4>Fase de Planeación</h4>
                <p>Estructuración de actividades técnicas, diseño pedagógico y programación de resultados esperados.</p>
              </div>
            </div>

            <div class="curriculum-step-item">
              <div class="curriculum-step-number">3</div>
              <div class="curriculum-step-info">
                <h4>Fase de Ejecución</h4>
                <p>Desarrollo técnico de proyectos formativos, talleres aplicados y emisión masiva de juicios de aprobación.</p>
              </div>
            </div>

            <div class="curriculum-step-item">
              <div class="curriculum-step-number">4</div>
              <div class="curriculum-step-info">
                <h4>Fase de Evaluación</h4>
                <p>Verificación de cumplimiento integral, sustentación de proyectos y transición a etapa práctica o productiva.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ══ ACCESO RÁPIDO A MÓDULOS ══ -->
  <section class="landing-section" id="modulos">
    <div class="container-landing">
      <div class="section-header-center">
        <span class="section-subtitle-tag">Navegación Modular</span>
        <h2 class="section-title-lg">Módulos del Sistema Operativo</h2>
        <p class="section-desc-muted">
          Accede directamente a cada una de las herramientas de gestión según el rol de administración o coordinación.
        </p>
      </div>

      <div class="modules-quick-grid">
        <a href="<?= BASE_URL ?>?module=dashboard" class="module-link-card">
          <div class="module-card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
          </div>
          <h4 class="module-card-title">Dashboard Principal</h4>
          <p class="module-card-desc">Curva de supervivencia, comparativa de juicios, KPIs globales y tabla detallada de retiros.</p>
        </a>

        <a href="<?= BASE_URL ?>?module=consulta" class="module-link-card">
          <div class="module-card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
          </div>
          <h4 class="module-card-title">Consulta por Aprendiz</h4>
          <p class="module-card-desc">Historial individual de juicios por competencia y estado de avance formativo en Sofia Plus.</p>
        </a>

        <a href="<?= BASE_URL ?>?module=carga" class="module-link-card">
          <div class="module-card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
          </div>
          <h4 class="module-card-title">Carga Masiva</h4>
          <p class="module-card-desc">Procesamiento de reportes Sofia Plus en formatos .xlsx, .xls y .csv por lotes optimizados.</p>
        </a>

        <a href="<?= BASE_URL ?>?module=fases" class="module-link-card">
          <div class="module-card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
          </div>
          <h4 class="module-card-title">Proyectos & Fases</h4>
          <p class="module-card-desc">Subida de archivos PDF curriculares GFPI-F-016 y gestión de actividades de proyecto.</p>
        </a>

        <a href="<?= BASE_URL ?>?module=dashboard_fases" class="module-link-card">
          <div class="module-card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
          </div>
          <h4 class="module-card-title">Dashboard de Fases</h4>
          <p class="module-card-desc">Métricas visuales de cumplimiento porcentual por fase y ficha con barras de progreso.</p>
        </a>

        <a href="<?= BASE_URL ?>?module=eliminacion" class="module-link-card">
          <div class="module-card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
          </div>
          <h4 class="module-card-title">Eliminación Controlada</h4>
          <p class="module-card-desc">Depuración y gestión controlada de aprendices y programas formativos con confirmación modal.</p>
        </a>
      </div>
    </div>
  </section>

  <!-- ══ CTA FINAL INVITANDO AL USO ══ -->
  <section class="landing-section" style="padding-top: 40px;">
    <div class="container-landing">
      <div class="landing-cta-banner">
        <span class="cta-tagline">Comienza Ahora Mismo</span>
        <h2 class="cta-heading">Monitorea y Optimiza el Avance Formativo</h2>
        <p class="cta-text">
          Toma decisiones basadas en datos reales para disminuir la deserción estudiantil y elevar la calidad educativa en cada ficha de formación.
        </p>
        <div style="display:flex;justify-content:center;gap:16px;flex-wrap:wrap">
          <a href="<?= BASE_URL ?>?module=dashboard" class="btn-landing btn-landing-primary">
            <span>Abrir Dashboard Ejecutivo</span>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
          </a>
          <a href="<?= BASE_URL ?>?module=carga" class="btn-landing btn-landing-outline">
            <span>Cargar Reporte Sofia Plus</span>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- ══ FOOTER INSTITUCIONAL OSCURO (DISEÑO EXCLUSIVO SENA JUICIOS) ══ -->
  <footer class="landing-footer-dark">
    <!-- Header Tecnológico Institucional: Red de Evaluación & Matriz de Fases -->
    <div class="footer-status-bar">
      <div class="footer-status-brand">
        <span class="status-indicator-dot"></span>
        <div class="status-texts">
          <span class="status-title">AUDITORÍA CONTINUA DE JUICIOS EVALUATIVOS</span>
          <span class="status-sub">Sincronización Formativa con Sofia Plus &bull; GFPI-F-016</span>
        </div>
      </div>
      
      <!-- Nodos de las 4 Fases Formativas SENA -->
      <div class="footer-curriculum-nodes">
        <div class="curr-node done">
          <span class="curr-node-dot"></span>
          <span class="curr-node-name">1. Análisis</span>
        </div>
        <div class="curr-node-connector"></div>
        <div class="curr-node done">
          <span class="curr-node-dot"></span>
          <span class="curr-node-name">2. Planeación</span>
        </div>
        <div class="curr-node-connector"></div>
        <div class="curr-node active">
          <span class="curr-node-dot pulse"></span>
          <span class="curr-node-name">3. Ejecución</span>
        </div>
        <div class="curr-node-connector"></div>
        <div class="curr-node eval">
          <span class="curr-node-dot"></span>
          <span class="curr-node-name">4. Evaluación</span>
        </div>
      </div>

      <div class="footer-stack-badges">
        <span class="badge-tech">PHP 8.2 MVC</span>
        <span class="badge-tech">Python Parser</span>
        <span class="badge-tech">MariaDB 10</span>
      </div>
    </div>

    <div class="container-landing">
      <div class="footer-dark-grid">
        <!-- Columna 1: Identidad Institucional -->
        <div class="footer-brand-col">
          <div class="footer-dark-brand">
            <div class="brand-icon-dark">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
            </div>
            <h3 class="footer-title-dark">SENA <span class="txt-green">JUICIOS</span></h3>
          </div>
          <p class="footer-desc-dark">
            Sistema institucional de alto rendimiento para el monitoreo de curvas de deserción, analítica de juicios evaluativos Sofia Plus y auditoría de proyectos formativos GFPI-F-016.
          </p>
          <div class="footer-legal-tag">
            Servicio Nacional de Aprendizaje &bull; Dirección de Formación Profesional
          </div>
        </div>

        <!-- Columna 2: Módulos del Sistema -->
        <div class="footer-nav-col">
          <h4 class="footer-dark-heading">Módulos del Sistema</h4>
          <ul class="footer-dark-links">
            <li><a href="<?= BASE_URL ?>?module=dashboard">Dashboard de Fichas & KPIs</a></li>
            <li><a href="<?= BASE_URL ?>?module=consulta">Consulta por Aprendiz</a></li>
            <li><a href="<?= BASE_URL ?>?module=carga">Carga Masiva Sofia Plus</a></li>
            <li><a href="<?= BASE_URL ?>?module=dashboard_fases">Cumplimiento por Fases</a></li>
            <li><a href="<?= BASE_URL ?>?module=fases">Gestión de Proyectos Curriculares</a></li>
            <li><a href="<?= BASE_URL ?>?module=eliminacion">Eliminación Controlada</a></li>
          </ul>
        </div>

        <!-- Columna 3: Acceso Operativo -->
        <div class="footer-action-col">
          <h4 class="footer-dark-heading">Acceso Operativo</h4>
          <p class="footer-action-desc">
            Ingresa a la consola operativa para auditar las fichas de caracterización y evaluar el avance de los aprendices.
          </p>
          <a href="<?= BASE_URL ?>?module=dashboard" class="btn-footer-console">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
            <span>Abrir Consola Operativa</span>
          </a>
        </div>
      </div>

      <div class="footer-bottom-dark">
        <div>
          &copy; <?= date('Y') ?> SENA Juicios &bull; Analítica Formativa y Gestión Curricular. Todos los derechos reservados.
        </div>
        <div class="footer-bottom-tech">
          <span>PHP 8.2</span> &bull; <span>Docker VPS</span> &bull; <span>Chart.js</span> &bull; <span>MariaDB</span>
        </div>
      </div>
    </div>
  </footer>

  <!-- ══ SCRIPT DE INTERACTIVIDAD & GRÁFICO PREVIEW ══ -->
  <script>
    // Navbar efecto scroll blur
    window.addEventListener('scroll', () => {
      const nav = document.getElementById('landingNavbar');
      if (window.scrollY > 40) {
        nav.classList.add('scrolled');
      } else {
        nav.classList.remove('scrolled');
      }
    });

    // Gráfico de previsualización en el Mockup
    document.addEventListener('DOMContentLoaded', () => {
      const ctx = document.getElementById('heroPreviewChart');
      if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx.getContext('2d'), {
          type: 'line',
          data: {
            labels: ['C1', 'C2', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8'],
            datasets: [
              {
                label: 'Retención ADSO',
                data: [35, 34, 32, 31, 29, 28, 28, 27],
                borderColor: '#39d900',
                backgroundColor: 'rgba(57, 217, 0, 0.1)',
                fill: true,
                stepped: 'before',
                borderWidth: 2.5,
                tension: 0,
                pointBackgroundColor: '#249400',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4
              },
              {
                label: 'Retención Redes',
                data: [30, 29, 27, 25, 23, 22, 22, 21],
                borderColor: '#0284c7',
                backgroundColor: 'rgba(2, 132, 199, 0.06)',
                fill: true,
                stepped: 'before',
                borderWidth: 2,
                tension: 0,
                pointBackgroundColor: '#0284c7',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: true,
                position: 'top',
                labels: {
                  boxWidth: 10,
                  font: { size: 11, family: "'Inter', sans-serif" },
                  color: '#475569'
                }
              },
              tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.92)',
                borderColor: '#e2e8f0',
                borderWidth: 1,
                titleColor: '#fff',
                bodyColor: '#f1f5f9',
                padding: 10
              }
            },
            scales: {
              x: {
                grid: { display: false },
                ticks: { color: '#64748b', font: { size: 10 } }
              },
              y: {
                min: 15,
                max: 38,
                grid: { color: 'rgba(226, 232, 240, 0.8)' },
                ticks: { color: '#64748b', font: { size: 10 }, stepSize: 5 }
              }
            }
          }
        });
      }
    });
  </script>
</body>
</html>
