<?php
/**
 * Vista: Visor de Documentación Institucional
 * Sistema de Gestión de Datos — SENA
 * Permite renderizar y leer los archivos Markdown de la carpeta /docs de forma estilizada, limpia y con UTF-8 perfecto.
 */
require_once dirname(__DIR__, 3) . '/config/url_config.php';

// Conversor básico y seguro de Markdown a HTML (sin dependencias externas pesadas)
function renderMarkdown(string $md): string {
    // Asegurar UTF-8 limpio
    $html = htmlspecialchars($md, ENT_NOQUOTES, 'UTF-8');

    // Títulos h1, h2, h3, h4
    $html = preg_replace('/^# (.*?)$/m', '<h1 class="doc-h1">$1</h1>', $html);
    $html = preg_replace('/^## (.*?)$/m', '<h2 class="doc-h2">$1</h2>', $html);
    $html = preg_replace('/^### (.*?)$/m', '<h3 class="doc-h3">$1</h3>', $html);
    $html = preg_replace('/^#### (.*?)$/m', '<h4 class="doc-h4">$1</h4>', $html);

    // Separadores horizontales
    $html = preg_replace('/^---$/m', '<hr class="doc-divider">', $html);

    // Bloques de código ```code```
    $html = preg_replace_callback('/```([a-zA-Z0-9_-]*)\n([\s\S]*?)```/', function ($matches) {
        $lang = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
        return '<pre class="doc-codeblock"><code>' . trim($matches[2]) . '</code></pre>';
    }, $html);

    // Código en línea `code`
    $html = preg_replace('/`([^`]+)`/', '<code class="doc-inline-code">$1</code>', $html);

    // Negrita e Itálica
    $html = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html);
    $html = preg_replace('/\*([^\*]+)\*/s', '<em>$1</em>', $html);

    // Listas no ordenadas (* o -)
    $html = preg_replace('/^[*-] (.*?)$/m', '<li class="doc-list-item">$1</li>', $html);

    // Listas ordenadas (1. 2.)
    $html = preg_replace('/^\d+\. (.*?)$/m', '<li class="doc-list-item-num">$1</li>', $html);

    // Párrafos y saltos de línea normales
    $lines = explode("\n", $html);
    $output = [];
    $inList = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (str_starts_with($trimmed, '<li class="doc-list-item">')) {
            if (!$inList) {
                $output[] = '<ul class="doc-ul">';
                $inList = true;
            }
            $output[] = $trimmed;
        } elseif (str_starts_with($trimmed, '<li class="doc-list-item-num">')) {
            if (!$inList) {
                $output[] = '<ol class="doc-ol">';
                $inList = true;
            }
            $output[] = $trimmed;
        } else {
            if ($inList) {
                $output[] = '</ul>';
                $inList = false;
            }
            if (!empty($trimmed) && !str_starts_with($trimmed, '<h') && !str_starts_with($trimmed, '<hr') && !str_starts_with($trimmed, '<pre') && !str_starts_with($trimmed, '<code') && !str_starts_with($trimmed, '```')) {
                $output[] = '<p class="doc-p">' . $trimmed . '</p>';
            } else {
                $output[] = $trimmed;
            }
        }
    }
    if ($inList) {
        $output[] = '</ul>';
    }

    return implode("\n", $output);
}

$renderedHtml = renderMarkdown($rawContent ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($titleDoc ?? 'Documentación') ?> &bull; SENA Juicios</title>
  
  <!-- Tipografía Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/styles.css">

  <style>
    body.doc-viewer-body {
      background-color: #f8fafc;
      color: #1e293b;
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .doc-top-bar {
      background: #ffffff;
      border-bottom: 1px solid #e2e8f0;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    }

    .doc-bar-container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 14px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }

    .doc-back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #0f172a;
      text-decoration: none;
      font-size: 14px;
      font-weight: 600;
      padding: 8px 16px;
      background: #f1f5f9;
      border-radius: 9999px;
      transition: all 0.2s ease;
      border: 1px solid #e2e8f0;
    }

    .doc-back-link:hover {
      background: #e2e8f0;
      color: var(--sena-green);
      transform: translateX(-2px);
    }

    .doc-brand-title {
      display: flex;
      align-items: center;
      gap: 10px;
      font-family: 'Outfit', sans-serif;
      font-size: 16px;
      font-weight: 700;
      color: #0f172a;
    }

    .doc-badge-status {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      padding: 3px 10px;
      border-radius: 12px;
      background: rgba(36, 148, 0, 0.1);
      color: var(--sena-green);
      border: 1px solid rgba(36, 148, 0, 0.25);
    }

    .doc-container {
      max-width: 920px;
      margin: 40px auto 60px auto;
      padding: 0 24px;
      width: 100%;
      box-sizing: border-box;
      flex: 1;
    }

    .doc-card-sheet {
      background: #ffffff;
      border-radius: 20px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
      padding: 48px 56px;
      line-height: 1.75;
    }

    .doc-h1 {
      font-family: 'Outfit', sans-serif;
      font-size: 30px;
      font-weight: 800;
      color: #0f172a;
      border-bottom: 2px solid #f1f5f9;
      padding-bottom: 16px;
      margin-top: 0;
      margin-bottom: 24px;
      letter-spacing: -0.02em;
    }

    .doc-h2 {
      font-family: 'Outfit', sans-serif;
      font-size: 22px;
      font-weight: 700;
      color: #1e293b;
      margin-top: 36px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .doc-h3 {
      font-family: 'Outfit', sans-serif;
      font-size: 18px;
      font-weight: 700;
      color: #334155;
      margin-top: 24px;
      margin-bottom: 12px;
    }

    .doc-h4 {
      font-family: 'Outfit', sans-serif;
      font-size: 15px;
      font-weight: 700;
      color: #475569;
      margin-top: 20px;
      margin-bottom: 10px;
    }

    .doc-p {
      font-size: 15px;
      color: #334155;
      margin-bottom: 18px;
    }

    .doc-divider {
      border: none;
      height: 1px;
      background: #e2e8f0;
      margin: 32px 0;
    }

    .doc-ul, .doc-ol {
      padding-left: 24px;
      margin-bottom: 20px;
    }

    .doc-list-item, .doc-list-item-num {
      font-size: 15px;
      color: #334155;
      margin-bottom: 8px;
    }

    .doc-codeblock {
      background: #0f172a;
      color: #f8fafc;
      padding: 18px 22px;
      border-radius: 12px;
      font-family: 'JetBrains Mono', monospace;
      font-size: 13.5px;
      overflow-x: auto;
      margin: 20px 0;
      border: 1px solid #1e293b;
    }

    .doc-inline-code {
      font-family: 'JetBrains Mono', monospace;
      font-size: 13px;
      background: #f1f5f9;
      color: #0f172a;
      padding: 2px 7px;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
    }

    strong {
      color: #0f172a;
      font-weight: 600;
    }

    .doc-footer-simple {
      text-align: center;
      padding: 20px 0 30px;
      font-size: 13px;
      color: #64748b;
    }

    @media (max-width: 768px) {
      .doc-card-sheet {
        padding: 28px 20px;
      }
      .doc-h1 {
        font-size: 24px;
      }
      .doc-h2 {
        font-size: 19px;
      }
    }
  </style>
</head>
<body class="doc-viewer-body">

  <!-- BARRA DE NAVEGACIÓN SUPERIOR DEL VISOR -->
  <header class="doc-top-bar">
    <div class="doc-bar-container">
      <a href="<?= BASE_URL ?>?module=landing" class="doc-back-link">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
        <span>Volver al Portal</span>
      </a>

      <div class="doc-brand-title">
        <span>SENA Juicios &bull; Documentación Oficial</span>
        <span class="doc-badge-status">UTF-8 Verificado</span>
      </div>

      <a href="<?= BASE_URL ?>?module=dashboard" class="btn-landing btn-landing-primary btn-landing-sm" style="padding: 8px 18px; font-size:13px;">
        <span>Abrir Sistema</span>
      </a>
    </div>
  </header>

  <!-- CONTENIDO DE LA DOCUMENTACIÓN -->
  <main class="doc-container">
    <article class="doc-card-sheet">
      <?= $renderedHtml ?>
    </article>
  </main>

  <footer class="doc-footer-simple">
    &copy; <?= date('Y') ?> SENA Juicios &bull; Sistema de Gestión de Datos y Analítica Sofia Plus.
  </footer>

</body>
</html>
