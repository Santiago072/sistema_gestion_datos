<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- Stats Row -->
<div class="grid-4 mb-24" id="statsRow">
  <div class="kpi-card fade-in" id="statTotal">
    <div class="kpi-value" id="sTotal">—</div>
    <div class="kpi-label">Total Importaciones</div>
  </div>
  <div class="kpi-card green fade-in stagger-1" id="statOk">
    <div class="kpi-value" id="sOk">—</div>
    <div class="kpi-label">Completadas</div>
  </div>
  <div class="kpi-card orange fade-in stagger-2" id="statPending">
    <div class="kpi-value" id="sPending">—</div>
    <div class="kpi-label">En Proceso / Pendientes</div>
  </div>
  <div class="kpi-card red fade-in stagger-3" id="statErr">
    <div class="kpi-value" id="sErr">—</div>
    <div class="kpi-label">Con Errores</div>
  </div>
</div>

<!-- Main Table -->
<div class="card fade-in">
  <div class="section-header mb-16">
    <div class="section-title">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Historial de Importaciones
    </div>
    <button class="btn btn-secondary btn-sm" onclick="cargarHistorial()">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:15px;height:15px"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
      Actualizar
    </button>
  </div>

  <div class="table-wrap">
    <table id="tablaHistorial" class="table-compact">
      <thead>
        <tr>
          <th style="width:60px">#</th>
          <th>Tipo</th>
          <th>Estado</th>
          <th style="width:90px">Progreso</th>
          <th>Resumen</th>
          <th>Advertencias</th>
          <th>Fecha</th>
          <th style="width:110px">Acciones</th>
        </tr>
      </thead>
      <tbody id="tbodyHistorial">
        <tr><td colspan="8" class="empty-state">Cargando historial...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Detalle Errores -->
<div class="modal-bg" id="modalLogs">
  <div class="modal" style="max-width:680px;width:95%">
    <div class="modal-header">
      <h3 id="modalLogsTitle">Logs de Importación</h3>
      <button class="modal-close" onclick="cerrarModalLogs()">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div id="modalLogsContent" style="max-height:60vh;overflow-y:auto;padding:0 4px"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="cerrarModalLogs()">Cerrar</button>
    </div>
  </div>
</div>

<style>
.estado-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 600;
}
.estado-completado { background: rgba(57,169,0,.15); color: #39A900; }
.estado-error      { background: rgba(239,68,68,.15);  color: #ef4444; }
.estado-procesando { background: rgba(245,158,11,.15); color: #F59E0B; }
.estado-pendiente  { background: rgba(99,102,241,.15); color: #6366f1; }
.estado-error_parcial { background: rgba(245,158,11,.15); color: #F59E0B; }

.log-row { padding: 8px 12px; border-radius: 6px; margin-bottom: 6px; font-size:.82rem; border-left: 3px solid; }
.log-row.err { background: rgba(239,68,68,.07); border-color:#ef4444; }
.log-row.warn { background: rgba(245,158,11,.07); border-color:#F59E0B; }

.stat-pill { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:10px; font-size:.72rem; font-weight:600; }
.pill-green  { background:rgba(57,169,0,.15);  color:#39A900; }
.pill-orange { background:rgba(245,158,11,.15);color:#F59E0B; }
.pill-red    { background:rgba(239,68,68,.15);  color:#ef4444; }
.pill-cyan   { background:rgba(0,188,212,.15);  color:#00BCD4; }
</style>

<script>
const API = '/sistema_gestion_datos/controllers/historial_importaciones.php';

function badgeEstado(estado) {
  const map = {
    completado:    'estado-completado',
    error:         'estado-error',
    procesando:    'estado-procesando',
    pendiente:     'estado-pendiente',
    error_parcial: 'estado-error_parcial',
  };
  const icons = {
    completado:    '✓',
    error:         '✕',
    procesando:    '⟳',
    pendiente:     '◔',
    error_parcial: '⚠',
  };
  const cls = map[estado] || '';
  return `<span class="estado-badge ${cls}">${icons[estado]||'?'} ${estado}</span>`;
}

function formatFecha(str) {
  if (!str) return '—';
  const d = new Date(str);
  return d.toLocaleDateString('es-CO') + ' ' + d.toLocaleTimeString('es-CO', {hour:'2-digit', minute:'2-digit'});
}

function resumenPills(resultado) {
  if (!resultado) return '<span style="color:var(--text-dim);font-size:.75rem">—</span>';
  const pills = [];
  if (resultado.aprendices != null) pills.push(`<span class="stat-pill pill-cyan">👤 ${resultado.aprendices} aprendices</span>`);
  if (resultado.juicios    != null) pills.push(`<span class="stat-pill pill-green">⚖ ${resultado.juicios} juicios</span>`);
  if (resultado.programas  != null) pills.push(`<span class="stat-pill pill-cyan">📋 ${resultado.programas} prog.</span>`);
  if (resultado.funcionarios!= null) pills.push(`<span class="stat-pill pill-orange">👷 ${resultado.funcionarios} func.</span>`);
  if (resultado.total_filas != null) pills.push(`<span class="stat-pill pill-cyan">📄 ${resultado.total_filas} filas</span>`);
  return pills.join(' ') || '<span style="color:var(--text-dim);font-size:.75rem">Sin datos</span>';
}

function cargarHistorial() {
  fetch(`${API}?action=list`)
    .then(r => r.json())
    .then(jobs => {
      // KPIs
      const total     = jobs.length;
      const ok        = jobs.filter(j => j.estado === 'completado').length;
      const pending   = jobs.filter(j => ['pendiente','procesando'].includes(j.estado)).length;
      const err       = jobs.filter(j => ['error','error_parcial'].includes(j.estado)).length;

      animateNum('sTotal', total);
      animateNum('sOk', ok);
      animateNum('sPending', pending);
      animateNum('sErr', err);

      // Tabla
      const tbody = document.getElementById('tbodyHistorial');
      if (!jobs.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No hay importaciones registradas aún.</td></tr>';
        return;
      }

      tbody.innerHTML = jobs.map(j => {
        const errCount = parseInt(j.total_errores_log) || 0;
        const errBadge = errCount
          ? `<span class="stat-pill pill-orange" style="cursor:pointer" onclick="verLogs(${j.id}, '${escHtml(j.tipo)}')">⚠ ${errCount} advertencia${errCount>1?'s':''}</span>`
          : `<span style="color:var(--text-dim);font-size:.75rem">Sin advertencias</span>`;

        const progBar = `<div style="display:flex;align-items:center;gap:6px">
          <div class="progress-wrap" style="width:60px;height:6px">
            <div class="progress-bar ${j.progreso==100?'':'orange'}" style="width:${j.progreso||0}%"></div>
          </div>
          <span style="font-size:.72rem;color:var(--text-muted)">${j.progreso||0}%</span>
        </div>`;

        const tipoLabel = j.tipo === 'excel_aprendices' ? '📊 Excel Juicios' : (j.tipo === 'pdf_fases' ? '📄 PDF Fases' : j.tipo);

        return `<tr>
          <td class="text-muted" style="font-size:.8rem">#${j.id}</td>
          <td style="font-weight:600;font-size:.85rem">${tipoLabel}</td>
          <td>${badgeEstado(j.estado)}</td>
          <td>${progBar}</td>
          <td>${resumenPills(j.resultado)}</td>
          <td>${errBadge}</td>
          <td class="text-muted" style="font-size:.78rem;white-space:nowrap">${formatFecha(j.created_at)}</td>
          <td>
            ${errCount ? `<button class="btn btn-xs btn-ghost" onclick="verLogs(${j.id},'${escHtml(j.tipo)}')" title="Ver errores detallados">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              Ver logs
            </button>` : ''}
            <button class="btn btn-xs btn-danger" onclick="eliminarJob(${j.id})" title="Eliminar del historial" style="margin-left:4px">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
            </button>
          </td>
        </tr>`;
      }).join('');
    })
    .catch(() => {
      document.getElementById('tbodyHistorial').innerHTML = '<tr><td colspan="8" class="empty-state" style="color:var(--danger)">Error al cargar el historial.</td></tr>';
    });
}

function verLogs(id, tipo) {
  document.getElementById('modalLogsTitle').textContent = `Logs de Importación #${id} — ${tipo}`;
  document.getElementById('modalLogsContent').innerHTML = '<div class="loading" style="padding:24px 0"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></div>';
  document.getElementById('modalLogs').classList.add('open');

  fetch(`${API}?action=logs&id=${id}`)
    .then(r => r.json())
    .then(logs => {
      if (!logs.length) {
        document.getElementById('modalLogsContent').innerHTML = '<p style="text-align:center;color:var(--text-dim);padding:32px 0">No hay logs registrados para esta importación.</p>';
        return;
      }
      document.getElementById('modalLogsContent').innerHTML = logs.map(l => `
        <div class="log-row ${l.fila ? 'warn' : 'err'}">
          ${l.fila ? `<strong>Fila ${l.fila}:</strong> ` : '<strong>Error general:</strong> '}
          ${escHtml(l.mensaje_error)}
          ${l.created_at ? `<span style="float:right;color:var(--text-dim);font-size:.7rem">${formatFecha(l.created_at)}</span>` : ''}
        </div>`).join('');
    });
}

function cerrarModalLogs() {
  document.getElementById('modalLogs').classList.remove('open');
}

function eliminarJob(id) {
  if (!confirm(`¿Eliminar el registro de importación #${id} del historial?\nEsto no afecta los datos ya importados.`)) return;
  fetch(`${API}?action=delete&id=${id}`)
    .then(r => r.json())
    .then(d => { if (d.ok) cargarHistorial(); });
}

function escHtml(str) {
  return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

function animateNum(id, target) {
  const el = document.getElementById(id);
  let n = 0;
  const step = Math.ceil(target / 20);
  const iv = setInterval(() => {
    n = Math.min(n + step, target);
    el.textContent = n;
    if (n >= target) clearInterval(iv);
  }, 40);
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') cerrarModalLogs();
});

document.addEventListener('DOMContentLoaded', cargarHistorial);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
