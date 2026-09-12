<?php
require_once dirname(__DIR__, 3) . '/config/url_config.php';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
$db = getDB();
// Proyectos y programas disponibles
$proyectosList = $db->query("SELECT id_proyecto, nombre_programa, codigo_programa_sofia, nombre_proyecto, total_resultados FROM proyectos_formativos ORDER BY nombre_programa")->fetchAll();
$programas = $db->query("SELECT id_ficha, nombre, id_proyecto FROM programas ORDER BY nombre")->fetchAll();
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- ── BARRA SELECTOR DE PROYECTO FORMATIVO GLOBAL ── -->
<div class="prog-bar fade-in mb-24">
  <i class="bi bi-book-half" style="font-size:1.15rem;color:var(--primary);flex-shrink:0"></i>
  <span class="prog-label">Proyecto Formativo:</span>
  <select id="globalProyecto" onchange="onProyectoChange()" style="flex:1;max-width:540px;padding:8px 12px;border-radius:var(--radius-sm);background:var(--bg);border:1px solid var(--card-border);color:var(--text);font-size:0.9rem">
    <option value="">— Seleccionar Proyecto Formativo —</option>
    <?php foreach($proyectosList as $pj): ?>
      <option value="<?= htmlspecialchars($pj['id_proyecto']) ?>">
        <?= htmlspecialchars($pj['nombre_programa'] ?: $pj['nombre_proyecto']) ?> <?= !empty($pj['codigo_programa_sofia']) ? '(Código: ' . htmlspecialchars($pj['codigo_programa_sofia']) . ')' : '' ?>
      </option>
    <?php endforeach; ?>
  </select>
  <!-- Input oculto de compatibilidad interna si algún componente requiere idFicha -->
  <input type="hidden" id="globalPrograma" value="">
  <button class="btn btn-primary btn-sm" onclick="document.getElementById('tabBtnPDF').click()" style="margin-left:auto;display:inline-flex;align-items:center;gap:6px">
    <i class="bi bi-plus-lg"></i>
    Nuevo Proyecto (PDF)
  </button>
</div>

<!-- ── TABS ── -->
<div class="tabs fade-in stagger-1">
  <button class="tab-btn active" data-tab="tabProyectos" id="tabBtnProyectos">
    <i class="bi bi-folder2-open" style="font-size:1rem"></i>
    Proyecto Formativo
  </button>
  <button class="tab-btn" data-tab="tabFases" id="tabBtnFases">
    <i class="bi bi-diagram-3" style="font-size:1rem"></i>
    Fases y Actividades
  </button>
  <button class="tab-btn" data-tab="tabPDF" id="tabBtnPDF">
    <i class="bi bi-file-earmark-pdf" style="font-size:1rem"></i>
    Carga PDF
  </button>
</div>

<!-- ══════════════════════════════════════════════════════
     TAB 0 — PROYECTOS (ESTRUCTURA + FICHAS ASIGNADAS)
     ══════════════════════════════════════════════════════ -->
<div class="tab-pane active" id="tabProyectos">
  <div id="proyectoContenedor" style="display:none">
    <!-- Info cabecera -->
    <div class="card mb-24" style="background: linear-gradient(135deg, rgba(57,169,0,0.1), rgba(0,0,0,0)); border-left: 4px solid #39A900">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
        <div style="flex:1;min-width:280px">
          <h2 id="pNombre" style="margin-bottom:4px;color:var(--text-light);word-break:break-word;overflow-wrap:anywhere;line-height:1.35;"></h2>
          <div style="color:var(--text-muted);font-size:0.9rem;word-break:break-word;overflow-wrap:anywhere;" id="pSub"></div>
          <div style="margin-top:12px;display:flex;gap:16px;font-size:0.85rem;flex-wrap:wrap">
            <div><strong style="color:var(--text-dim)">Centro:</strong> <span id="pCentro"></span></div>
            <div><strong style="color:var(--text-dim)">Regional:</strong> <span id="pRegional"></span></div>
            <div><strong style="color:var(--text-dim)">Duración:</strong> <span id="pTiempo"></span></div>
          </div>
          <div id="pTotalesGlobales"></div>
        </div>
        <button class="btn btn-danger btn-sm" id="btnEliminarProyecto" style="white-space:nowrap;display:inline-flex;align-items:center;gap:6px">
          <i class="bi bi-trash3"></i> Eliminar Proyecto
        </button>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         TARJETA: FICHAS / PROGRAMAS ASIGNADOS A ESTE PROYECTO
         ═══════════════════════════════════════════════════════════════ -->
    <div class="card mb-24 fade-in" style="background:var(--bg2);border:1px solid var(--card-border)">
      <div class="section-header" style="margin-bottom:14px">
        <div class="section-title" style="display:flex;align-items:center;gap:8px;font-size:0.98rem">
          <i class="bi bi-people-fill" style="font-size:1.15rem;color:var(--primary)"></i>
          Fichas / Programas vinculados a este Proyecto
        </div>
        <span class="badge badge-green" id="badgeTotalFichas">0 vinculadas</span>
      </div>
      
      <p style="font-size:0.83rem;color:var(--text-muted);margin-bottom:16px">
        Los juicios evaluativos y aprendices de las fichas vinculadas se comparan directamente con las fases y resultados de este proyecto formativo.
      </p>

      <!-- Lista dinámica de fichas vinculadas -->
      <div id="listaFichasVinculadas" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px">
        <!-- Renderizado dinámicamente en JS -->
      </div>

      <!-- Barra para vincular una nueva ficha disponible -->
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;background:var(--bg);padding:12px 14px;border-radius:8px;border:1px dashed var(--card-border)">
        <label style="font-size:0.85rem;font-weight:600;color:var(--text-dim);white-space:nowrap">Vincular otra ficha existente:</label>
        <select id="selectFichaParaVincular" style="flex:1;min-width:240px;padding:8px 12px;border-radius:6px;border:1px solid var(--card-border);background:var(--bg2);color:var(--text);font-size:0.85rem">
          <option value="">-- Seleccionar ficha del sistema --</option>
        </select>
        <button class="btn btn-primary btn-sm" onclick="vincularFichaAProyecto()" style="display:inline-flex;align-items:center;gap:6px">
          <i class="bi bi-link-45deg" style="font-size:1.1rem"></i>
          Vincular Ficha
        </button>
      </div>
    </div>

    <!-- Fases Cards -->
    <div class="section-title mb-16">Estructura de Fases y Actividades Curriculares</div>
    <div id="fasesContenedor"></div>
  </div>

  <div id="emptyState" class="card fade-in text-center" style="display:block; padding: 40px 20px;">
    <div style="width:64px;height:64px;margin:0 auto 16px;border-radius:50%;background:rgba(57,169,0,0.1);display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:1.8rem">
      <i class="bi bi-folder-check"></i>
    </div>
    <h3 id="emptyStateTitle" style="color:var(--text-light); margin-bottom:8px;">Gestión de Proyectos y Fases Formativas</h3>
    <p id="emptyStateMsg" class="text-muted" style="max-width:560px; margin:0 auto 20px; font-size:0.95rem;">Selecciona un proyecto formativo en el selector superior para consultar sus fases, actividades y fichas vinculadas, o carga un nuevo proyecto desde el archivo PDF (GFPI-F-016).</p>
    <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
      <button class="btn btn-primary" onclick="document.getElementById('tabBtnPDF').click()" style="display:inline-flex;align-items:center;gap:6px">
        <i class="bi bi-file-earmark-arrow-up"></i>
        Cargar Proyecto desde PDF
      </button>
    </div>
    <div id="listaProyectosDisponibles" style="margin-top:32px; text-align:left;"></div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════
     TAB 1 — FASES Y ACTIVIDADES
     ══════════════════════════════════════════════════════ -->
<div class="tab-pane" id="tabFases">
  <div class="grid-1-2" style="align-items: start;">

    <!-- ── Columna izquierda: lista de fases ── -->
    <div class="card fade-in" style="min-width:0">
      <div class="section-header mb-16">
        <div style="display:flex;align-items:center;gap:10px">
          <div class="section-title">
            <i class="bi bi-diagram-3" style="color:var(--primary);margin-right:6px"></i>
            Fases
          </div>
          <span class="list-count" id="countFases">0</span>
        </div>
        <button id="btnNuevaFase" class="btn btn-primary btn-sm" onclick="openModalFase()" style="display:none;align-items:center;gap:6px">
          <i class="bi bi-plus-lg"></i>
          Nueva
        </button>
      </div>

      <!-- Búsqueda de fases -->
      <div class="search-box mb-16">
        <i class="bi bi-search search-icon" style="left:12px;top:50%;transform:translateY(-50%);position:absolute;color:var(--text-dim)"></i>
        <input type="text" id="searchFases" placeholder="Buscar fases…" oninput="filtrarFases(this.value)">
        <button class="search-clear" id="clearFases" onclick="limpiarBusquedaFases()">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div id="listaFases">
        <div class="loading"><i class="bi bi-arrow-repeat spin" style="font-size:1.8rem;color:var(--primary)"></i></div>
      </div>
    </div>

    <!-- ── Columna derecha: actividades de la fase seleccionada ── -->
    <div class="card fade-in stagger-1" style="min-width:0">
      <div class="section-header mb-16">
        <div style="display:flex;align-items:center;gap:10px">
          <div class="section-title" id="tituloActividades">
            <i class="bi bi-list-task" style="color:var(--primary);margin-right:6px"></i>
            Actividades
          </div>
          <span class="list-count" id="countActividades" style="display:none">0</span>
        </div>
        <button class="btn btn-primary btn-sm" id="btnNuevaActividad" style="display:none;align-items:center;gap:6px" onclick="openModalActividad()">
          <i class="bi bi-plus-lg"></i>
          Agregar
        </button>
      </div>

      <!-- Barra de filtro de actividades (visible solo cuando hay fase seleccionada) -->
      <div id="filtroActividadesBar" style="display:none" class="mb-16">
        <div class="search-box">
          <i class="bi bi-search search-icon" style="left:12px;top:50%;transform:translateY(-50%);position:absolute;color:var(--text-dim)"></i>
          <input type="text" id="searchActividades" placeholder="Buscar actividades…" oninput="filtrarActividades(this.value)">
          <button class="search-clear" id="clearActividades" onclick="limpiarBusquedaActividades()">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
      </div>

      <div id="listaActividades">
        <div class="empty-panel">
          <i class="bi bi-cursor" style="font-size:1.8rem;color:var(--text-dim)"></i>
          <p>Selecciona una fase para<br>ver sus actividades</p>
        </div>
      </div>
    </div>

  </div><!-- /grid-1-2 -->
</div>

<!-- ══════════════════════════════════════════════════════
     TAB 2 — CARGA PDF
     ══════════════════════════════════════════════════════ -->
<div class="tab-pane" id="tabPDF">
  <div class="card fade-in mb-24">
    <div class="section-header mb-16">
      <div class="section-title">
        <i class="bi bi-file-earmark-pdf" style="color:var(--primary);margin-right:6px"></i>
        Carga Masiva desde PDF — GFPI-F-016
      </div>
      <button class="btn btn-ghost btn-sm" onclick="limpiarPdf()" style="display:inline-flex;align-items:center;gap:6px">
        <i class="bi bi-trash3"></i>
        Limpiar todo
      </button>
    </div>

    <!-- Selector opcional o nota informativa -->
    <div style="background:var(--bg2);border:1px solid var(--card-border);border-left:3px solid var(--primary);border-radius:8px;padding:12px 16px;margin-bottom:18px">
      <div style="font-weight:600;color:var(--text);font-size:0.9rem;display:flex;align-items:center;gap:6px">
        <i class="bi bi-info-circle text-primary"></i> Subida independiente del Proyecto Formativo
      </div>
      <div style="font-size:0.8rem;color:var(--text-muted);margin-top:2px">
        El PDF (formato GFPI-F-016) se procesará y registrará como un <strong>Proyecto Formativo</strong> global en el sistema, extrayendo sus fases, competencias y resultados. Posteriormente podrás vincularle los programas o fichas que desees desde la pestaña "Proyecto Formativo".
      </div>
    </div>

    <!-- Drop Zone -->
    <div class="drop-zone" id="pdfDropZone">
      <i class="bi bi-cloud-arrow-up" style="font-size:2.4rem;color:var(--primary)"></i>
      <p><strong>Arrastra tu PDF aquí</strong> o haz clic para seleccionar</p>
      <p style="font-size:.75rem;color:var(--text-dim);margin-top:6px">Proyecto Formativo SENA (GFPI-F-016) · Sección 3: Planeación · Máx 10 MB</p>
      <input type="file" id="pdfFileInput" accept=".pdf" style="display:none">
    </div>

    <!-- Info del archivo cargado -->
    <div id="pdfFileInfo" style="display:none;align-items:center;gap:12px;padding:12px 16px;background:rgba(57,217,0,.08);border:1px solid rgba(57,217,0,.2);border-radius:var(--radius-sm);margin-top:14px">
      <i class="bi bi-file-earmark-check" style="font-size:1.8rem;color:var(--primary);flex-shrink:0"></i>
      <div style="flex:1"><strong id="pdfFileName"></strong><br><small class="text-muted" id="pdfFileSize"></small></div>
    </div>

    <!-- Acciones -->
    <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap">
      <button class="btn btn-primary" id="btnProcesar" style="display:none;align-items:center;gap:6px" onclick="procesarPdf()">
        <i class="bi bi-gear-fill"></i>
        Procesar PDF
      </button>
      <button class="btn btn-sm" id="btnImportar" style="display:none;background:var(--accent);color:#fff;box-shadow:0 4px 15px rgba(255,109,0,.3);align-items:center;gap:6px" onclick="importarDatos()">
        <i class="bi bi-check-circle-fill"></i>
        Confirmar Importación
      </button>
    </div>

    <div id="pdfMsg" style="margin-top:14px"></div>
  </div>

  <!-- Preview del PDF procesado -->
  <div class="card fade-in mb-24" id="pdfPreview" style="display:none"></div>
</div>

<!-- ── MODAL FASE ── -->
<div class="modal-bg" id="modalFase">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modalFaseTitulo">Nueva Fase</h3>
      <button class="modal-close" onclick="closeModal('modalFase')"><i class="bi bi-x-lg"></i></button>
    </div>
    <input type="hidden" id="faseId">
    <div class="form-row">
      <div class="form-group" style="grid-column:1/-1">
        <label for="faseNombre">Nombre de la Fase <span style="color:var(--danger)">*</span></label>
        <input type="text" id="faseNombre" placeholder="Ej: Análisis, Planeación, Ejecución…">
      </div>
      <div class="form-group">
        <label for="faseOrden">Orden</label>
        <input type="number" id="faseOrden" value="1" min="1" max="20">
      </div>
    </div>
    <div class="form-group">
      <label for="faseDesc">Descripción</label>
      <textarea id="faseDesc" rows="3" placeholder="Descripción opcional de la fase…"></textarea>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modalFase')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarFase()" style="display:inline-flex;align-items:center;gap:6px">
        <i class="bi bi-check-lg"></i>
        Guardar
      </button>
    </div>
  </div>
</div>

<!-- ── MODAL ACTIVIDAD ── -->
<div class="modal-bg" id="modalActividad">
  <div class="modal">
    <div class="modal-header">
      <h3>Nueva Actividad</h3>
      <button class="modal-close" onclick="closeModal('modalActividad')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="form-group">
      <label for="actNombre">Nombre de la Actividad <span style="color:var(--danger)">*</span></label>
      <input type="text" id="actNombre" placeholder="Ej: Levantamiento de requisitos…">
    </div>
    <div class="form-group">
      <label for="actDesc">Descripción</label>
      <textarea id="actDesc" rows="2" placeholder="Descripción opcional…"></textarea>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modalActividad')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarActividad()" style="display:inline-flex;align-items:center;gap:6px">
        <i class="bi bi-check-lg"></i>
        Guardar
      </button>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>assets/js/fases.js"></script>
<script src="<?= BASE_URL ?>assets/js/pdf_upload.js"></script>

<script>
/* ── Tabs ── */
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(btn.dataset.tab).classList.add('active');
  });
});

// Auto-seleccionar tab si viene en URL (?tab=pdf)
const urlParams = new URLSearchParams(window.location.search);
if(urlParams.get('tab') === 'pdf') {
  document.getElementById('tabBtnPDF').click();
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
