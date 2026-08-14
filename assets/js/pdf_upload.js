// ═══════════════════════════════════════════════
// PDF UPLOAD
// ═══════════════════════════════════════════════
let pdfData = null;

const dropZone = document.getElementById('pdfDropZone');
const fileInput = document.getElementById('pdfFileInput');

if (dropZone && fileInput) {
    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    if (e.dataTransfer.files.length) handlePdfFile(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', () => { if (fileInput.files.length) handlePdfFile(fileInput.files[0]); });
}

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

  fetch((window.BASE_URL || '') + 'index.php?module=fases&action=upload_pdf', { method: 'POST', body: fd })
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

  fetch((window.BASE_URL || '') + 'index.php?module=fases&action=import_pdf', {
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
        if (typeof cargarProyectos === 'function') cargarProyectos();
        
        // Saltar a la pestaña de proyectos automáticamente
        setTimeout(() => {
          const tabProyectos = document.getElementById('tabBtnProyectos');
          if (tabProyectos) tabProyectos.click();
        }, 1500);
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
