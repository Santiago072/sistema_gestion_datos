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

<script>
const API = '/sistema_gestion_datos/controllers/fases_crud.php';
let currentFaseId = null;

function closeModal(id){ document.getElementById(id).classList.remove('open'); }
function openModal(id){ document.getElementById(id).classList.add('open'); }

function getProgramaId() {
  return document.getElementById('globalPrograma')?.value || '';
}

// Cuando cambia el programa global, recargar todo
function onProgramaChange() {
  const idFicha = getProgramaId();
  // Sincronizar con el selector de la tab PDF
  const pdfSel = document.getElementById('pdfPrograma');
  if (pdfSel) pdfSel.value = idFicha;
  currentFaseId = null;
  document.getElementById('tituloActividades').textContent = '📋 Actividades';
  
  // Ocultar botones de agregar nueva fase/actividad si estamos en "Todos los programas"
  document.getElementById('btnNuevaFase').style.display = idFicha ? 'inline-block' : 'none';
  document.getElementById('btnNuevaActividad').style.display = 'none';
  
  document.getElementById('listaActividades').innerHTML = '<div class="empty-state"><p>Selecciona una fase para ver sus actividades</p></div>';
  cargarFases();
}

// ── CARGAR FASES ──
function cargarFases() {
  const idFicha = getProgramaId();
  const url = API + '?action=list_fases' + (idFicha ? '&id_ficha=' + idFicha : '');
  fetch(url).then(r=>r.json()).then(fases=>{
    document.getElementById('listaFases').innerHTML = fases.length
      ? fases.map(f=>`
        <div class="flex-center gap-8 mb-16" style="padding:12px;background:rgba(57,169,0,0.05);border:1px solid rgba(57,169,0,0.15);border-radius:8px;cursor:pointer" onclick="seleccionarFase(${f.id_fase},'${f.nombre_fase.replace(/'/g,"\\'")}')">
          <div style="width:28px;height:28px;background:rgba(57,169,0,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#39A900;font-size:.8rem;flex-shrink:0">${f.orden}</div>
          <div style="flex:1"><strong>${f.nombre_fase}</strong><br><small style="color:#7a8fa6">${f.descripcion||''}</small></div>
          ${idFicha ? `
          <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation();editarFase(${f.id_fase},'${f.nombre_fase.replace(/'/g,"\\'")}',${f.orden},'${(f.descripcion||'').replace(/'/g,"\\'")}')">✏</button>
          <button class="btn btn-sm btn-danger" onclick="event.stopPropagation();eliminarFase(${f.id_fase})">🗑</button>
          ` : ''}
        </div>`).join('')
      : '<p class="text-muted">No hay fases configuradas</p>';
  });
}

function seleccionarFase(id, nombre) {
  currentFaseId = id;
  const idFicha = getProgramaId();
  document.getElementById('tituloActividades').textContent = '📋 ' + nombre;
  document.getElementById('btnNuevaActividad').style.display = idFicha ? 'flex' : 'none';
  cargarActividades(id, nombre);
}

function cargarActividades(id, nombre) {
  const idFicha = getProgramaId();
  const url = `${API}?action=list_actividades&id_fase=${id}&nombre_fase=${encodeURIComponent(nombre || '')}` + (idFicha ? `&id_ficha=${idFicha}` : '');
  fetch(url).then(r=>r.json()).then(acts=>{
    document.getElementById('listaActividades').innerHTML = acts.length
      ? acts.map(a=>`
        <div style="padding:10px 12px;border-top:1px solid rgba(255,255,255,0.05);display:flex;align-items:center;gap:8px">
          <div style="flex:1"><strong style="font-size:.85rem">${a.nombre}</strong><br><small style="color:#7a8fa6">${a.descripcion||''}</small></div>
          ${idFicha ? `<button class="btn btn-sm btn-danger" onclick="eliminarActividad(${a.id_actividad})">🗑</button>` : ''}
        </div>`).join('')
      : '<p class="text-muted" style="padding:12px">No hay actividades en esta fase</p>';
  });
}

function openModalFase(){ document.getElementById('faseId').value='';document.getElementById('faseNombre').value='';document.getElementById('faseOrden').value=1;document.getElementById('faseDesc').value='';document.getElementById('modalFaseTitulo').textContent='Nueva Fase';openModal('modalFase'); }
function editarFase(id,nombre,orden,desc){ document.getElementById('faseId').value=id;document.getElementById('faseNombre').value=nombre;document.getElementById('faseOrden').value=orden;document.getElementById('faseDesc').value=desc;document.getElementById('modalFaseTitulo').textContent='Editar Fase';openModal('modalFase'); }
function openModalActividad(){ document.getElementById('actNombre').value='';document.getElementById('actDesc').value='';openModal('modalActividad'); }

function guardarFase(){
  const id=document.getElementById('faseId').value;
  const idFicha = getProgramaId();
  const data={
    nombre_fase: document.getElementById('faseNombre').value,
    orden:       +document.getElementById('faseOrden').value,
    descripcion: document.getElementById('faseDesc').value,
    id_ficha:    idFicha ? +idFicha : null
  };
  const action = id?'update_fase':'create_fase';
  if(id) data.id_fase=+id;
  fetch(`${API}?action=${action}`,{method:'POST',body:JSON.stringify(data)}).then(r=>r.json()).then(()=>{closeModal('modalFase');cargarFases();});
}

function eliminarFase(id){ if(!confirm('¿Eliminar esta fase y todas sus actividades?'))return;
  fetch(`${API}?action=delete_fase`,{method:'POST',body:JSON.stringify({id_fase:id})}).then(()=>cargarFases()); }

function guardarActividad(){
  const idFicha = getProgramaId();
  const data={
    nombre:      document.getElementById('actNombre').value,
    descripcion: document.getElementById('actDesc').value,
    id_fase:     currentFaseId,
    id_ficha:    idFicha ? +idFicha : null
  };
  fetch(`${API}?action=create_actividad`,{method:'POST',body:JSON.stringify(data)}).then(r=>r.json()).then(()=>{closeModal('modalActividad');cargarActividades(currentFaseId);});
}

function eliminarActividad(id){ if(!confirm('¿Eliminar esta actividad?'))return;
  fetch(`${API}?action=delete_actividad`,{method:'POST',body:JSON.stringify({id_actividad:id})}).then(()=>cargarActividades(currentFaseId)); }

cargarFases();
onProgramaChange(); // Inicializar estado de botones

// Sincronizar pdfPrograma con globalPrograma al iniciar
document.addEventListener('DOMContentLoaded', () => {
  const pdfSel = document.getElementById('pdfPrograma');
  const globalSel = document.getElementById('globalPrograma');
  if (pdfSel && globalSel) {
    pdfSel.addEventListener('change', () => { globalSel.value = pdfSel.value; });
  }
});

// ═══════════════════════════════════════════════
// PDF UPLOAD
// ═══════════════════════════════════════════════
let pdfData = null;

const dropZone = document.getElementById('pdfDropZone');
const fileInput = document.getElementById('pdfFileInput');

dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
  e.preventDefault();
  dropZone.classList.remove('dragover');
  if (e.dataTransfer.files.length) handlePdfFile(e.dataTransfer.files[0]);
});
fileInput.addEventListener('change', () => { if (fileInput.files.length) handlePdfFile(fileInput.files[0]); });

function handlePdfFile(file) {
  if (file.type !== 'application/pdf') {
    showPdfMsg('error', 'El archivo debe ser un PDF');
    return;
  }
  if (file.size > 10 * 1024 * 1024) {
    showPdfMsg('error', 'El archivo no debe superar 10MB');
    return;
  }

  document.getElementById('pdfFileName').textContent = file.name;
  document.getElementById('pdfFileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
  document.getElementById('pdfFileInfo').style.display = 'flex';
  document.getElementById('pdfPreview').style.display = 'none';
  document.getElementById('btnProcesar').style.display = 'inline-flex';
  document.getElementById('btnImportar').style.display = 'none';
  showPdfMsg('info', 'Archivo cargado. Haz clic en "Procesar PDF" para extraer los datos.');
}

function showPdfMsg(type, msg) {
  document.getElementById('pdfMsg').innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
}

function procesarPdf() {
  const file = fileInput.files[0];
  if (!file) { showPdfMsg('error', 'Primero selecciona un archivo PDF'); return; }

  const prog = document.getElementById('pdfPrograma').value;
  if (!prog) { showPdfMsg('warning', '⚠ Selecciona un programa de formación antes de procesar el PDF'); return; }

  const fd = new FormData();
  fd.append('pdf', file);

  document.getElementById('btnProcesar').disabled = true;
  document.getElementById('btnProcesar').textContent = '⏳ Procesando...';
  showPdfMsg('info', '⏳ Extrayendo texto del PDF (sección 3: Planeación)... Esto puede tomar unos segundos.');

  fetch('/sistema_gestion_datos/controllers/upload_pdf_fases.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      document.getElementById('btnProcesar').disabled = false;
      document.getElementById('btnProcesar').textContent = '🔍 Procesar PDF';

      if (d.error) { showPdfMsg('error', d.error); return; }

      pdfData = d;
      showPdfMsg('success', `✓ PDF procesado: ${d.total_caracteres.toLocaleString()} caracteres · ${d.total_paginas} páginas`);

      renderPreview(d);
    })
    .catch(err => {
      document.getElementById('btnProcesar').disabled = false;
      document.getElementById('btnProcesar').textContent = '🔍 Procesar PDF';
      showPdfMsg('error', 'Error de conexión: ' + err.message);
    });
}

function renderPreview(d) {
  const mapped = d.datos_mapeados;
  const info = d.datos_extraidos;
  let html = '';

  // Info básica detectada
  if (info.informacion_basica && Object.keys(info.informacion_basica).length) {
    html += '<div class="section-title mb-16">📌 Información detectada</div><div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px">';
    Object.entries(info.informacion_basica).forEach(([k,v]) => {
      html += `<span class="badge badge-cyan" style="padding:6px 12px;font-size:.78rem"><strong>${k}:</strong> ${v}</span>`;
    });
    html += '</div>';
  }

  // Resumen KPIs
  html += `<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px">
    <div class="kpi-card green" style="padding:14px;min-height:auto"><div class="kpi-value" style="font-size:1.4rem">${mapped.resumen.total_fases}</div><div class="kpi-label">Fases</div></div>
    <div class="kpi-card cyan" style="padding:14px;min-height:auto"><div class="kpi-value" style="font-size:1.4rem">${mapped.resumen.total_actividades}</div><div class="kpi-label">Actividades</div></div>
    <div class="kpi-card orange" style="padding:14px;min-height:auto"><div class="kpi-value" style="font-size:1.4rem">${mapped.resumen.total_competencias}</div><div class="kpi-label">Competencias</div></div>
    <div class="kpi-card blue" style="padding:14px;min-height:auto"><div class="kpi-value" style="font-size:1.4rem">${mapped.resumen.total_resultados}</div><div class="kpi-label">Resultados</div></div>
    <div class="kpi-card yellow" style="padding:14px;min-height:auto"><div class="kpi-value" style="font-size:1.4rem">${mapped.resumen.total_registros}</div><div class="kpi-label">Registros</div></div>
  </div>`;

  // Fases
  html += '<div class="section-title mb-16">📁 Fases del proyecto</div>';
  if (mapped.fases.length) {
    html += '<div class="table-wrap mb-24"><table><thead><tr><th>Orden</th><th>Fase</th><th>Descripción</th></tr></thead><tbody>';
    mapped.fases.forEach(f => {
      html += `<tr><td><span class="badge badge-green">${f.orden}</span></td><td><strong>${f.nombre_fase}</strong></td><td class="text-muted">${f.descripcion || '—'}</td></tr>`;
    });
    html += '</tbody></table></div>';
  }

  // Actividades
  if (mapped.actividades.length) {
    html += '<div class="section-title mb-16">📋 Actividades detectadas</div>';
    html += '<div class="table-wrap mb-24"><table><thead><tr><th>Actividad</th><th>Fase</th></tr></thead><tbody>';
    mapped.actividades.forEach(a => {
      html += `<tr><td><strong>${a.nombre.substring(0,80)}${a.nombre.length>80?'...':''}</strong></td><td><span class="badge badge-cyan">${a.fase_nombre}</span></td></tr>`;
    });
    html += '</tbody></table></div>';
  }

  // Registros completos (tabla fases-actividades-resultados-competencias)
  if (mapped.registros.length) {
    html += '<div class="section-title mb-16">🔗 Mapeo Fase → Actividad → Resultado → Competencia</div>';
    html += '<div class="table-wrap mb-24"><table class="table-compact"><thead><tr><th>Fase</th><th>Actividad</th><th>Cód. Resultado</th><th>Resultado de Aprendizaje</th><th>Cód. Competencia</th><th>Competencia</th></tr></thead><tbody>';
    mapped.registros.forEach(r => {
      html += `<tr>
        <td><span class="badge badge-green">${r.fase}</span></td>
        <td style="font-size:.78rem">${(r.actividad||'—').substring(0,55)}${(r.actividad||'').length>55?'...':''}</td>
        <td><code style="font-size:.72rem;color:#39A900;white-space:nowrap">${r.resultado_codigo||'—'}</code></td>
        <td style="font-size:.78rem">${(r.resultado_nombre||'—').substring(0,55)}${(r.resultado_nombre||'').length>55?'...':''}</td>
        <td><code style="font-size:.72rem;color:#FF6D00;white-space:nowrap">${r.competencia_codigo||'—'}</code></td>
        <td style="font-size:.78rem">${(r.competencia||'—').substring(0,50)}${(r.competencia||'').length>50?'...':''}</td>
      </tr>`;
    });
    html += '</tbody></table></div>';
  }

  // Texto extraído
  html += `<details style="margin-top:16px"><summary style="cursor:pointer;color:var(--primary);font-weight:600;font-size:.85rem">📝 Ver texto extraído (${d.total_caracteres} chars)</summary>
    <pre style="background:rgba(0,0,0,0.3);padding:16px;border-radius:8px;font-size:.75rem;max-height:300px;overflow:auto;color:var(--text-muted);white-space:pre-wrap;margin-top:8px">${d.texto_extraido}</pre></details>`;

  document.getElementById('pdfPreview').innerHTML = html;
  document.getElementById('pdfPreview').style.display = 'block';
  document.getElementById('btnImportar').style.display = 'inline-flex';
}

function importarDatos() {
  if (!pdfData) { showPdfMsg('error', 'Primero procesa un PDF'); return; }
  const idFicha = document.getElementById('pdfPrograma').value;
  if (!idFicha) { showPdfMsg('warning', '⚠ Selecciona un programa de formación'); return; }
  if (!confirm('¿Confirmas la importación de los datos detectados a la base de datos?')) return;

  const mapped = pdfData.datos_mapeados;
  const payload = {
    id_ficha: idFicha,
    informacion_basica: pdfData.datos_extraidos.informacion_basica || {},
    fases: mapped.fases,
    actividades: mapped.actividades,
    registros: mapped.registros
  };

  document.getElementById('btnImportar').disabled = true;
  document.getElementById('btnImportar').textContent = '⏳ Importando...';

  fetch('/sistema_gestion_datos/controllers/import_pdf_fases.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(r => r.json())
    .then(d => {
      document.getElementById('btnImportar').disabled = false;
      document.getElementById('btnImportar').textContent = '✓ Confirmar Importación';

      if (d.ok) {
        showPdfMsg('success', d.resumen || `✓ Importación completada`);
        let detHtml = '<div style="margin-top:12px;max-height:220px;overflow:auto;font-size:.8rem">';
        (d.detalle || []).forEach(line => {
          const cls = line.startsWith('✓') ? 'color:#39A900' : 'color:var(--text-muted)';
          detHtml += `<div style="${cls};padding:2px 0">${line}</div>`;
        });
        if (d.errores && d.errores.length) {
          d.errores.forEach(err => { detHtml += `<div style="color:var(--danger);padding:2px 0">⚠ ${err}</div>`; });
        }
        detHtml += '</div>';
        document.getElementById('pdfMsg').innerHTML += detHtml;
        cargarFases();
      } else {
        showPdfMsg('error', d.error || 'Error durante la importación');
      }
    })
    .catch(err => {
      document.getElementById('btnImportar').disabled = false;
      document.getElementById('btnImportar').textContent = '✓ Confirmar Importación';
      showPdfMsg('error', 'Error de conexión: ' + err.message);
    });
}

function limpiarPdf() {
  pdfData = null;
  fileInput.value = '';
  document.getElementById('pdfPrograma').value = '';
  document.getElementById('pdfFileInfo').style.display = 'none';
  document.getElementById('pdfPreview').style.display = 'none';
  document.getElementById('btnProcesar').style.display = 'none';
  document.getElementById('btnImportar').style.display = 'none';
  document.getElementById('pdfMsg').innerHTML = '';
}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
