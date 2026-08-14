<?php 
require_once dirname(__DIR__, 2) . '/config/url_config.php';
require_once dirname(__DIR__) . '/layouts/header.php'; 
require_once dirname(__DIR__, 2) . '/config/database.php';
?>

<div class="grid-1-2 mb-24">
  <!-- Zona de carga -->
  <div class="card fade-in">
    <div class="section-title mb-16">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:20px;height:20px;color:#39A900"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
      Cargar Archivo Excel / CSV
    </div>

    <div class="drop-zone" id="dropZone">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
      <p>Arrastra tu archivo aquí<br>o <strong>haz clic para seleccionar</strong></p>
      <p style="font-size:.75rem;color:#4a5f78;margin-top:8px">Formatos: <strong>.xlsx / .xls</strong> (Excel) · <strong>.csv</strong> (separado por ;)</p>
      <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" style="display:none">
    </div>

    <!-- Info archivo seleccionado -->
    <div id="fileInfo" style="display:none;margin-top:12px;padding:10px 14px;background:rgba(57,169,0,0.08);border:1px solid rgba(57,169,0,0.2);border-radius:8px;font-size:.85rem">
      <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:1.4rem">📄</span>
        <div><strong id="fileName">—</strong><br><span style="color:#7a8fa6" id="fileSize">—</span></div>
      </div>
    </div>

    <!-- Progress -->
    <div id="progressWrap" style="display:none;margin-top:16px">
      <div class="progress-label"><span id="progLabel">Procesando...</span><span id="progPct">0%</span></div>
      <div class="progress-wrap"><div class="progress-bar" id="progBar" style="width:0%"></div></div>
    </div>

    <!-- Botones -->
    <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap">
      <button class="btn btn-primary" id="btnSubir" style="display:none" onclick="subirArchivo()">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
        Procesar y Guardar
      </button>
      <button class="btn btn-secondary" id="btnLimpiarUpload" style="display:none;background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.3)" onclick="limpiarSubida()">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
        Limpiar y Subir Otro
      </button>
      <a href="data:text/csv;charset=utf-8,documento;tipo_documento;nombres;apellidos;estado;ficha;programa;competencia;resultado_aprendizaje;tipo_juicio;fecha_juicio;documento_funcionario;nombre_funcionario%0A1020304050;C%C3%A9dula de ciudadan%C3%ADa;Juan David;Mart%C3%ADnez Torres;En formaci%C3%B3n;1;Tecnolog%C3%ADa en ADSO;Construir soluciones de software;Implementar BD relacionales;Aprobado;2025-04-01 09:00:00;1;Carlos G%C3%B3mez" download="plantilla_sena.csv" class="btn btn-secondary btn-sm">⬇ Plantilla CSV</a>
    </div>

    <div id="resultados" style="margin-top:16px"></div>
  </div>

  <!-- Instrucciones -->
  <div style="display:flex;flex-direction:column;gap:16px">
    <div class="card fade-in stagger-1">
      <div class="section-title mb-12">📋 Columnas del Archivo</div>
      <div class="alert alert-info" style="margin-bottom:12px">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
        El sistema detecta las columnas automáticamente
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Columna</th><th>Tabla destino</th><th>Req.</th></tr></thead>
          <tbody>
            <tr><td><code>documento</code></td><td>aprendices</td><td><span class="badge badge-red">Sí</span></td></tr>
            <tr><td><code>tipo_documento</code></td><td>aprendices</td><td><span class="badge badge-gray">No</span></td></tr>
            <tr><td><code>nombres</code></td><td>aprendices</td><td><span class="badge badge-orange">Rec.</span></td></tr>
            <tr><td><code>apellidos</code></td><td>aprendices</td><td><span class="badge badge-orange">Rec.</span></td></tr>
            <tr><td><code>estado</code></td><td>aprendices</td><td><span class="badge badge-gray">No</span></td></tr>
            <tr><td><code>ficha</code> / <code>id_ficha</code></td><td>programas</td><td><span class="badge badge-orange">Rec.</span></td></tr>
            <tr><td><code>programa</code></td><td>programas</td><td><span class="badge badge-gray">No</span></td></tr>
            <tr><td><code>competencia</code></td><td>competencias</td><td><span class="badge badge-gray">No</span></td></tr>
            <tr><td><code>resultado_aprendizaje</code></td><td>resultados</td><td><span class="badge badge-gray">No</span></td></tr>
            <tr><td><code>tipo_juicio</code></td><td>juicios</td><td><span class="badge badge-gray">No</span></td></tr>
            <tr><td><code>fecha_juicio</code></td><td>juicios</td><td><span class="badge badge-gray">No</span></td></tr>
            <tr><td><code>documento_funcionario</code></td><td>funcionarios</td><td><span class="badge badge-gray">No</span></td></tr>
            <tr><td><code>nombre_funcionario</code></td><td>funcionarios</td><td><span class="badge badge-gray">No</span></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card fade-in stagger-2">
      <div class="section-title mb-12">✅ Estados válidos</div>
      <div style="display:flex;flex-wrap:wrap;gap:8px">
        <span class="badge badge-cyan">En formación</span>
        <span class="badge badge-red">Retirado</span>
        <span class="badge badge-gray">Trasladado</span>
        <span class="badge badge-green">Egresado</span>
      </div>
      <div class="section-title mb-12" style="margin-top:16px">⚖ Tipos de Juicio</div>
      <div style="display:flex;flex-wrap:wrap;gap:8px">
        <span class="badge badge-green">Aprobado</span>
        <span class="badge badge-orange">Por evaluar</span>
        <span class="badge badge-red">No aprobado</span>
      </div>
    </div>
  </div>
</div>

<!-- Previsualización -->
<div class="card fade-in stagger-3" id="previewCard" style="display:none">
  <div class="section-header mb-12">
    <div class="section-title">👁 Previsualización (primeras 20 filas)</div>
    <span id="totalFilas" class="badge badge-cyan"></span>
  </div>
  <div class="table-wrap" id="previewWrap" style="max-height:360px;overflow-y:auto"></div>
</div>

<script>
let selectedFile = null;

const drop     = document.getElementById('dropZone');
const fileInput= document.getElementById('fileInput');

drop.addEventListener('click', () => fileInput.click());
drop.addEventListener('dragover', e => { e.preventDefault(); drop.classList.add('dragover'); });
drop.addEventListener('dragleave', () => drop.classList.remove('dragover'));
drop.addEventListener('drop', e => { e.preventDefault(); drop.classList.remove('dragover'); handleFile(e.dataTransfer.files[0]); });
fileInput.addEventListener('change', () => handleFile(fileInput.files[0]));

function formatBytes(b) {
  if (b < 1024) return b + ' B';
  if (b < 1048576) return (b/1024).toFixed(1) + ' KB';
  return (b/1048576).toFixed(1) + ' MB';
}

function limpiarSubida() {
  selectedFile = null;
  fileInput.value = '';
  document.getElementById('fileName').textContent = '—';
  document.getElementById('fileSize').textContent = '—';
  document.getElementById('fileInfo').style.display = 'none';
  const btnSubir = document.getElementById('btnSubir');
  btnSubir.style.display = 'none';
  btnSubir.disabled = false;
  document.getElementById('btnLimpiarUpload').style.display = 'none';
  document.getElementById('progressWrap').style.display = 'none';
  document.getElementById('resultados').innerHTML = '';
  document.getElementById('previewCard').style.display = 'none';
  document.getElementById('previewWrap').innerHTML = '';
}

function handleFile(file) {
  if (!file) return;
  const ext = file.name.split('.').pop().toLowerCase();
  if (!['xlsx','xls','csv'].includes(ext)) { showMsg('error','Solo se permiten archivos .xlsx, .xls o .csv'); return; }

  limpiarSubida(); // Limpiar UI previo
  selectedFile = file;
  document.getElementById('fileName').textContent = file.name;
  document.getElementById('fileSize').textContent = formatBytes(file.size) + ' · ' + ext.toUpperCase();
  document.getElementById('fileInfo').style.display = 'block';
  document.getElementById('btnSubir').style.display = 'flex';

  // Previsualización solo para CSV (xlsx no se puede leer en el cliente sin lib)
  if (ext === 'csv') {
    const reader = new FileReader();
    reader.onload = e => previewCSV(e.target.result);
    reader.readAsText(file, 'UTF-8');
  } else {
    // Para xlsx mostramos aviso
    document.getElementById('previewCard').style.display = 'block';
    document.getElementById('totalFilas').textContent = 'Excel — previsualización disponible tras procesar';
    document.getElementById('previewWrap').innerHTML = '<div class="alert alert-info"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg><div>Archivo Excel listo. Haz clic en <strong>"Procesar y Guardar"</strong> para cargarlo.</div></div>';
  }
}

function previewCSV(text) {
  const lines = text.split('\n').filter(l => l.trim());
  document.getElementById('totalFilas').textContent = (lines.length - 1) + ' filas de datos';
  document.getElementById('previewCard').style.display = 'block';

  const headers = lines[0].split(';');
  const preview = lines.slice(1, 21);
  const html = `<table>
    <thead><tr>${headers.map(h=>`<th>${h.trim()}</th>`).join('')}</tr></thead>
    <tbody>${preview.map(l=>`<tr>${l.split(';').map(c=>`<td>${c.trim()}</td>`).join('')}</tr>`).join('')}</tbody>
  </table>`;
  document.getElementById('previewWrap').innerHTML = html;
  if (lines.length > 21) {
    document.getElementById('previewWrap').insertAdjacentHTML('beforeend',
      `<p style="text-align:center;color:#7a8fa6;padding:8px;font-size:.78rem">... y ${lines.length-21} filas más</p>`);
  }
}

function subirArchivo() {
  if (!selectedFile) return;

  const pw   = document.getElementById('progressWrap');
  const pb   = document.getElementById('progBar');
  const ppct = document.getElementById('progPct');
  const plbl = document.getElementById('progLabel');
  pw.style.display = 'block';
  document.getElementById('btnSubir').disabled = true;
  document.getElementById('btnLimpiarUpload').style.display = 'none';

  // Barra animada mientras se sube y procesa
  let prog = 0;
  pb.style.width = '0%';
  ppct.textContent = '0%';
  plbl.textContent = 'Subiendo archivo...';
  const iv = setInterval(() => {
    prog = Math.min(prog + 1, 90);
    pb.style.width = prog + '%';
    ppct.textContent = prog + '%';
    if (prog < 30) plbl.textContent = 'Leyendo archivo...';
    else if (prog < 65) plbl.textContent = 'Procesando filas...';
    else plbl.textContent = 'Guardando en base de datos...';
  }, 200);

  const fd = new FormData();
  fd.append('archivo', selectedFile);

  fetch((window.BASE_URL || '') + 'index.php?module=carga&action=upload_excel', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      clearInterval(iv);
      pb.style.width = '100%';
      ppct.textContent = '100%';
      plbl.textContent = 'Completado';
      document.getElementById('btnSubir').style.display = 'none';
      document.getElementById('btnLimpiarUpload').style.display = 'flex';

      if (d.error) { showMsg('error', d.message); return; }

      const erroresHtml = (d.errores || []).slice(0, 8).join('<br>');
      const resHtml = `
        <div class="alert alert-success">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <div><strong>¡Carga exitosa!</strong> Se procesaron <strong>${d.total_filas}</strong> filas.</div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-top:12px">
          <div style="background:rgba(57,169,0,0.1);border:1px solid rgba(57,169,0,0.25);border-radius:8px;padding:12px;text-align:center">
            <div style="font-size:1.6rem;font-weight:800;color:#39A900">${d.programas}</div>
            <div style="font-size:.72rem;color:#7a8fa6">Programas</div>
          </div>
          <div style="background:rgba(0,188,212,0.1);border:1px solid rgba(0,188,212,0.25);border-radius:8px;padding:12px;text-align:center">
            <div style="font-size:1.6rem;font-weight:800;color:#00BCD4">${d.aprendices}</div>
            <div style="font-size:.72rem;color:#7a8fa6">Aprendices</div>
          </div>
          <div style="background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.25);border-radius:8px;padding:12px;text-align:center">
            <div style="font-size:1.6rem;font-weight:800;color:#3B82F6">${d.funcionarios}</div>
            <div style="font-size:.72rem;color:#7a8fa6">Funcionarios</div>
          </div>
          <div style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.25);border-radius:8px;padding:12px;text-align:center">
            <div style="font-size:1.6rem;font-weight:800;color:#F59E0B">${d.juicios}</div>
            <div style="font-size:.72rem;color:#7a8fa6">Juicios</div>
          </div>
        </div>
        ${d.columnas_detectadas ? `<div style="margin-top:10px;font-size:.75rem;color:#7a8fa6">Columnas detectadas: <em>${d.columnas_detectadas.join(', ')}</em></div>` : ''}
        ${d.errores && d.errores.length ? `<div class="alert alert-warning" style="margin-top:10px"><div><strong>${d.errores.length} advertencias:</strong><br>${erroresHtml}</div></div>` : ''}`;

      document.getElementById('resultados').innerHTML = resHtml;
    })
    .catch(e => {
      clearInterval(iv);
      document.getElementById('btnSubir').disabled = false;
      showMsg('error', 'Error de conexión: ' + e.message);
    });
}

function showMsg(type, msg) {
  const map = { success: 'alert-success', error: 'alert-error', warning: 'alert-warning' };
  document.getElementById('resultados').innerHTML = `<div class="alert ${map[type]}">${msg}</div>`;
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>


