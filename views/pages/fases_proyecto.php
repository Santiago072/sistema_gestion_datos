<?php
require_once dirname(__DIR__, 2) . '/config/url_config.php';
require_once __DIR__ . '/../layouts/header.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
$db = getDB();
$programas = $db->query("SELECT id_ficha, nombre FROM programas ORDER BY nombre")->fetchAll();
?>

<!-- ── BARRA SELECTOR DE PROGRAMA GLOBAL ── -->
<div class="prog-bar fade-in mb-24">
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:18px;height:18px;color:var(--primary);flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
  <span class="prog-label">Programa de Formación:</span>
  <select id="globalPrograma" onchange="onProgramaChange()">
    <option value="">— Todos los programas —</option>
    <?php foreach($programas as $p): ?>
      <option value="<?= htmlspecialchars($p['id_ficha']) ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['id_ficha']) ?>)</option>
    <?php endforeach; ?>
  </select>
  <span class="prog-hint">⚠ Selecciona un programa antes de cargar el PDF.</span>
</div>

<!-- ── TABS ── -->
<div class="tabs fade-in stagger-1">
  <button class="tab-btn active" data-tab="tabProyectos" id="tabBtnProyectos">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:15px;height:15px"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" /></svg>
    Proyectos
  </button>
  <button class="tab-btn" data-tab="tabFases" id="tabBtnFases">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:15px;height:15px"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125z"/></svg>
    Fases y Actividades
  </button>
  <button class="tab-btn" data-tab="tabPDF" id="tabBtnPDF">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:15px;height:15px"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
    Carga PDF
  </button>
</div>

<!-- ══════════════════════════════════════════════════════
     TAB 0 — PROYECTOS (NUEVO)
     ══════════════════════════════════════════════════════ -->
<div class="tab-pane active" id="tabProyectos">
  <div id="proyectoContenedor" style="display:none">
    <!-- Info cabecera -->
    <div class="card mb-24" style="background: linear-gradient(135deg, rgba(57,169,0,0.1), rgba(0,0,0,0)); border-left: 4px solid #39A900">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
          <h2 id="pNombre" style="margin-bottom:4px;color:var(--text-light)"></h2>
          <div style="color:var(--text-muted);font-size:0.9rem" id="pSub"></div>
          <div style="margin-top:12px;display:flex;gap:16px;font-size:0.85rem">
            <div><strong style="color:var(--text-dim)">Centro:</strong> <span id="pCentro"></span></div>
            <div><strong style="color:var(--text-dim)">Regional:</strong> <span id="pRegional"></span></div>
            <div><strong style="color:var(--text-dim)">Duración:</strong> <span id="pTiempo"></span></div>
          </div>
          <div id="pTotalesGlobales"></div>
        </div>
        <button class="btn btn-danger btn-sm" id="btnEliminarProyecto">🗑 Eliminar Proyecto</button>
      </div>
    </div>

    <!-- Fases Cards -->
    <div class="section-title mb-16">Resumen de Fases y Actividades</div>
    <div id="fasesContenedor"></div>
  </div>

  <div id="emptyState" class="empty-state card fade-in" style="display:none">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:40px;height:40px;color:#4a5f78;margin:0 auto 12px"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
    <p>Este programa no tiene un proyecto formativo cargado.</p>
    <button class="btn btn-primary mt-16" onclick="document.getElementById('tabBtnPDF').click()">Cargar desde PDF</button>
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
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125z"/></svg>
            Fases
          </div>
          <span class="list-count" id="countFases">0</span>
        </div>
        <button id="btnNuevaFase" class="btn btn-primary btn-sm" onclick="openModalFase()" style="display:none">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
          Nueva
        </button>
      </div>

      <!-- Búsqueda de fases -->
      <div class="search-box mb-16">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        <input type="text" id="searchFases" placeholder="Buscar fases…" oninput="filtrarFases(this.value)">
        <button class="search-clear" id="clearFases" onclick="limpiarBusquedaFases()">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div id="listaFases">
        <div class="loading"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></div>
      </div>
    </div>

    <!-- ── Columna derecha: actividades de la fase seleccionada ── -->
    <div class="card fade-in stagger-1" style="min-width:0">
      <div class="section-header mb-16">
        <div style="display:flex;align-items:center;gap:10px">
          <div class="section-title" id="tituloActividades">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
            Actividades
          </div>
          <span class="list-count" id="countActividades" style="display:none">0</span>
        </div>
        <button class="btn btn-primary btn-sm" id="btnNuevaActividad" style="display:none" onclick="openModalActividad()">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
          Agregar
        </button>
      </div>

      <!-- Barra de filtro de actividades (visible solo cuando hay fase seleccionada) -->
      <div id="filtroActividadesBar" style="display:none" class="mb-16">
        <div class="search-box">
          <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
          <input type="text" id="searchActividades" placeholder="Buscar actividades…" oninput="filtrarActividades(this.value)">
          <button class="search-clear" id="clearActividades" onclick="limpiarBusquedaActividades()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>

      <div id="listaActividades">
        <div class="empty-panel">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59"/></svg>
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
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
        Carga Masiva desde PDF — GFPI-F-016
      </div>
      <button class="btn btn-ghost btn-sm" onclick="limpiarPdf()">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
        Limpiar todo
      </button>
    </div>

    <!-- Selector de programa (sincronizado con el global) -->
    <div class="form-group" style="max-width:500px">
      <label for="pdfPrograma">Programa de Formación <span style="color:var(--danger)">*</span></label>
      <select id="pdfPrograma" onchange="document.getElementById('globalPrograma').value=this.value">
        <option value="">— Seleccione programa —</option>
        <?php foreach($programas as $p): ?>
          <option value="<?= $p['id_ficha'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= $p['id_ficha'] ?>)</option>
        <?php endforeach; ?>
      </select>
      <small class="text-muted" style="margin-top:4px;display:block">⚠ Obligatorio: asocia el PDF al programa correcto antes de procesar.</small>
    </div>

    <!-- Drop Zone -->
    <div class="drop-zone" id="pdfDropZone">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
      <p><strong>Arrastra tu PDF aquí</strong> o haz clic para seleccionar</p>
      <p style="font-size:.75rem;color:var(--text-dim);margin-top:6px">Proyecto Formativo SENA (GFPI-F-016) · Sección 3: Planeación · Máx 10 MB</p>
      <input type="file" id="pdfFileInput" accept=".pdf" style="display:none">
    </div>

    <!-- Info del archivo cargado -->
    <div id="pdfFileInfo" style="display:none;align-items:center;gap:12px;padding:12px 16px;background:rgba(57,217,0,.08);border:1px solid rgba(57,217,0,.2);border-radius:var(--radius-sm);margin-top:14px">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:22px;height:22px;color:var(--primary);flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
      <div style="flex:1"><strong id="pdfFileName"></strong><br><small class="text-muted" id="pdfFileSize"></small></div>
    </div>

    <!-- Acciones -->
    <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap">
      <button class="btn btn-primary" id="btnProcesar" style="display:none" onclick="procesarPdf()">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        Procesar PDF
      </button>
      <button class="btn btn-sm" id="btnImportar" style="display:none;background:var(--accent);color:#fff;box-shadow:0 4px 15px rgba(255,109,0,.3)" onclick="importarDatos()">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
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
      <button class="modal-close" onclick="closeModal('modalFase')"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
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
      <button class="btn btn-primary" onclick="guardarFase()">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
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
      <button class="modal-close" onclick="closeModal('modalActividad')"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
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
      <button class="btn btn-primary" onclick="guardarActividad()">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
        Guardar
      </button>
    </div>
  </div>
</div>

<script src="/sistema_gestion_datos/assets/js/fases.js"></script>
<script src="/sistema_gestion_datos/assets/js/pdf_upload.js"></script>

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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
