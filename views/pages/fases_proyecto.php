<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../config/database.php';
$db = getDB();
$competencias = $db->query("SELECT id_competencia, nombre FROM competencias GROUP BY nombre ORDER BY nombre")->fetchAll();
$resultados   = $db->query("SELECT id_resultado, nombre FROM resultados ORDER BY nombre")->fetchAll();
$fases        = $db->query("SELECT * FROM fases_proyecto ORDER BY orden")->fetchAll();
$programas    = $db->query("SELECT id_ficha, nombre FROM programas ORDER BY nombre")->fetchAll();
?>

<!-- Selector global de programa (visible en toda la página) -->
<div class="card mb-24" style="background:var(--card-bg);border:1px solid rgba(255,255,255,0.05);display:flex;align-items:center;gap:14px">
  <span style="font-weight:600;white-space:nowrap;color:var(--text-muted)">📚 Programa:</span>
  <select id="globalPrograma" onchange="onProgramaChange()" style="flex:1;max-width:480px;padding:10px 14px;border-radius:var(--radius-sm);background:rgba(0,0,0,0.2);border:1px solid rgba(255,255,255,0.1);color:#fff">
    <option value="">-- Todos los programas --</option>
    <?php foreach($programas as $p): ?>
      <option value="<?= htmlspecialchars($p['id_ficha']) ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['id_ficha']) ?>)</option>
    <?php endforeach; ?>
  </select>
  <small style="color:var(--text-dim)">⚠ En la carga de PDF, selecciona un programa antes de procesar.</small>
</div>

<div class="tabs" data-tabs>
  <button class="tab-btn active" data-tab="tabFases">🏗 Fases y Actividades</button>
  <button class="tab-btn" data-tab="tabPDF">📄 Carga PDF</button>
</div>
<script>
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById(btn.dataset.tab).classList.add('active');
    });
  });
</script>

<!-- ── TAB FASES Y ACTIVIDADES ── -->
<div class="tab-pane active" id="tabFases">
  <div class="grid-2 mb-24">
    <!-- Lista de Fases -->
    <div class="card p-24" style="flex:1;min-width:300px;display:flex;flex-direction:column">
      <div class="flex-between mb-24">
        <h2 style="font-size:1.1rem;color:white;display:flex;align-items:center;gap:8px">📁 Fases del Proyecto</h2>
        <button id="btnNuevaFase" class="btn btn-sm btn-success" onclick="openModalFase()" style="display:none">+ Nueva Fase</button>
      </div>
      <div id="listaFases"></div>
    </div>
    <!-- Actividades de la fase seleccionada -->
    <div class="card fade-in stagger-1">
      <div class="section-header">
        <div class="section-title" id="tituloActividades">📋 Actividades</div>
        <button class="btn btn-primary btn-sm" id="btnNuevaActividad" style="display:none" onclick="openModalActividad()">+ Agregar</button>
      </div>
      <div id="listaActividades">
        <div class="empty-state"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:40px;height:40px;color:#4a5f78"><path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59"/></svg>
          <p>Selecciona una fase para ver sus actividades</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── TAB 4: CARGA PDF ── -->
<div class="tab-pane" id="tabPDF">
  <div class="card fade-in mb-24">
    <div class="section-header">
      <div class="section-title">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:20px;height:20px"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
        Carga Masiva desde PDF (GFPI-F-016)
      </div>
      <button class="btn btn-secondary btn-sm" onclick="limpiarPdf()">🗑 Limpiar</button>
    </div>

    <!-- Programa selector (sincronizado con el selector global) -->
    <div class="form-group" style="margin-top:16px;max-width:480px">
      <label style="margin-bottom:6px;display:block;font-weight:600">🏫 Programa de Formación</label>
      <select id="pdfPrograma" onchange="document.getElementById('globalPrograma').value=this.value" style="width:100%;padding:12px 14px;font-size:.9rem">
        <option value="">-- Seleccione programa --</option>
        <?php foreach($programas as $p): ?>
          <option value="<?= $p['id_ficha'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= $p['id_ficha'] ?>)</option>
        <?php endforeach; ?>
      </select>
      <small style="color:var(--text-dim);margin-top:4px;display:block">⚠ Obligatorio: selecciona a qué programa pertenece el proyecto formativo</small>
    </div>

    <!-- Drop Zone -->
    <div class="drop-zone" id="pdfDropZone">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
      <p><strong>Arrastra tu PDF aquí</strong> o haz clic para seleccionar</p>
      <p style="font-size:.75rem;color:var(--text-dim);margin-top:8px">Proyecto Formativo SENA (GFPI-F-016) · Sección 3: Planeación del proyecto · Máx 10MB</p>
      <input type="file" id="pdfFileInput" accept=".pdf" style="display:none">
    </div>

    <!-- File info -->
    <div id="pdfFileInfo" style="display:none;align-items:center;gap:12px;padding:12px 16px;background:rgba(57,169,0,0.08);border:1px solid rgba(57,169,0,0.2);border-radius:8px;margin-top:16px">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:24px;height:24px;color:#39A900;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
      <div style="flex:1"><strong id="pdfFileName"></strong><br><small class="text-muted" id="pdfFileSize"></small></div>
    </div>

    <!-- Actions -->
    <div style="display:flex;gap:12px;margin-top:16px">
      <button class="btn btn-primary" id="btnProcesar" style="display:none" onclick="procesarPdf()">🔍 Procesar PDF</button>
      <button class="btn btn-primary" id="btnImportar" style="display:none;background:#FF6D00;box-shadow:0 4px 15px rgba(255,109,0,0.3)" onclick="importarDatos()">✓ Confirmar Importación</button>
    </div>

    <!-- Messages -->
    <div id="pdfMsg" style="margin-top:16px"></div>
  </div>

  <!-- Preview -->
  <div class="card fade-in mb-24" id="pdfPreview" style="display:none"></div>
</div>

<!-- Modal Fase -->
<div class="modal-bg" id="modalFase">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modalFaseTitulo">Nueva Fase</h3>
      <button class="modal-close" onclick="closeModal('modalFase')"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    <input type="hidden" id="faseId">
    <div class="form-group"><label>Nombre de la Fase</label><input type="text" id="faseNombre" placeholder="Ej: Análisis"></div>
    <div class="form-group"><label>Orden</label><input type="number" id="faseOrden" value="1" min="1"></div>
    <div class="form-group"><label>Descripción</label><textarea id="faseDesc" rows="3" placeholder="Descripción de la fase..."></textarea></div>
    <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:16px">
      <button class="btn btn-secondary" onclick="closeModal('modalFase')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarFase()">Guardar</button>
    </div>
  </div>
</div>

<!-- Modal Actividad -->
<div class="modal-bg" id="modalActividad">
  <div class="modal">
    <div class="modal-header">
      <h3>Nueva Actividad</h3>
      <button class="modal-close" onclick="closeModal('modalActividad')"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    <div class="form-group"><label>Nombre de la Actividad</label><input type="text" id="actNombre" placeholder="Ej: Levantamiento de requisitos"></div>
    <div class="form-group"><label>Descripción</label><textarea id="actDesc" rows="2" placeholder="Descripción..."></textarea></div>
    <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:16px">
      <button class="btn btn-secondary" onclick="closeModal('modalActividad')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarActividad()">Guardar</button>
    </div>
  </div>
</div>

<script src="/sistema_gestion_datos/assets/js/fases.js"></script>
<script src="/sistema_gestion_datos/assets/js/pdf_upload.js"></script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
