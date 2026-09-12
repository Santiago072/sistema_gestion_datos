<?php 
require_once dirname(__DIR__, 3) . '/config/url_config.php';
require_once dirname(__DIR__) . '/layouts/header.php'; 
require_once dirname(__DIR__, 3) . '/config/database.php';
$db = getDB();
$programas = $db->query("SELECT id_ficha, nombre FROM programas ORDER BY nombre")->fetchAll();
?>

<!-- Controles de Filtro Global y Exportación -->
<div class="card mb-24" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px">
  <div style="display:flex;align-items:center;gap:12px;flex:1 1 360px">
    <label style="font-weight:600;white-space:nowrap;color:var(--text-muted)">Filtrar por Programa:</label>
    <select id="filtroProgramaGlobal" onchange="actualizarDashboardFases()" style="flex:1;max-width:480px;padding:10px 14px;border-radius:var(--radius-sm);background:var(--bg);border:1px solid var(--card-border);color:var(--text)">
      <option value="">-- Todos los Programas --</option>
      <?php foreach($programas as $p): ?>
        <option value="<?= htmlspecialchars($p['id_ficha']) ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['id_ficha']) ?>)</option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-secondary btn-sm" onclick="document.getElementById('filtroProgramaGlobal').value=''; actualizarDashboardFases()">Limpiar</button>
  </div>

  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <!-- Botón Exportar Excel con Estilo -->
    <button type="button" class="btn btn-secondary btn-sm" onclick="exportarDetalleExcel()" style="display:inline-flex;align-items:center;gap:6px;padding:9px 15px;font-size:0.82rem;font-weight:600;border:1px solid var(--card-border)" title="Exportar informe detallado y diseñado a Microsoft Excel (.xls)">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#39A900" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
      Exportar a Excel (.xls)
    </button>
    <!-- Botón Informe Ejecutivo Imprimible / PDF -->
    <button type="button" class="btn btn-primary btn-sm" onclick="imprimirInformeEjecutivo()" style="display:inline-flex;align-items:center;gap:6px;padding:9px 15px;font-size:0.82rem;font-weight:600" title="Generar informe imprimible / Guardar como PDF">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.048-.363-2.138-.363-3.25 0-6.075 4.925-11 11-11s11 4.925 11 11c0 1.112-.123 2.202-.363 3.25m-6.357 5.62A9.01 9.01 0 0112 21a9.01 9.01 0 01-5.003-1.521m10.006 0A9.01 9.01 0 0021 13.829m-18 0A9.01 9.01 0 006.997 19.48M12 9v6m0 0l-3-3m3 3l3-3" /></svg>
      Informe Ejecutivo (PDF)
    </button>
  </div>
</div>

<!-- Card de Resumen del Grupo y Deserción -->
<div class="card fade-in mb-24" id="resumenGrupoCard" style="padding:20px 24px;border-left:4px solid #39A900;background:linear-gradient(135deg, rgba(57,169,0,0.06), rgba(0,0,0,0))">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px">
    <div>
      <div style="font-weight:700;font-size:1.05rem;color:var(--text);display:flex;align-items:center;gap:8px">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:20px;height:20px;color:var(--primary)"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
        Estado y Composición de la Ficha
      </div>
      <div style="font-size:0.85rem;color:var(--text-muted);margin-top:2px">
        El avance por fase se calcula sobre los aprendices actualmente <strong>activos (En formación)</strong> para reflejar el progreso real del grupo.
      </div>
    </div>
    <div style="display:flex;gap:16px;flex-wrap:wrap">
      <div style="background:var(--bg);border:1px solid var(--card-border);padding:8px 14px;border-radius:8px;text-align:center;min-width:90px">
        <div id="statMatriculados" style="font-size:1.25rem;font-weight:800;color:var(--text)">—</div>
        <div style="font-size:0.72rem;color:var(--text-dim);text-transform:uppercase;font-weight:600">Iniciaron</div>
      </div>
      <div style="background:var(--bg);border:1px solid var(--card-border);padding:8px 14px;border-radius:8px;text-align:center;min-width:90px">
        <div id="statActivos" style="font-size:1.25rem;font-weight:800;color:#39A900">—</div>
        <div style="font-size:0.72rem;color:var(--text-dim);text-transform:uppercase;font-weight:600">Activos</div>
      </div>
      <div style="background:var(--bg);border:1px solid var(--card-border);padding:8px 14px;border-radius:8px;text-align:center;min-width:90px">
        <div id="statDesertados" style="font-size:1.25rem;font-weight:800;color:#FF6D00">—</div>
        <div style="font-size:0.72rem;color:var(--text-dim);text-transform:uppercase;font-weight:600">Retirados</div>
      </div>
      <div style="background:var(--bg);border:1px solid var(--card-border);padding:8px 14px;border-radius:8px;text-align:center;min-width:90px">
        <div id="statTasaDesercion" style="font-size:1.25rem;font-weight:800;color:#ef4444">—</div>
        <div style="font-size:0.72rem;color:var(--text-dim);text-transform:uppercase;font-weight:600">Deserción</div>
      </div>
      <div style="background:var(--bg);border:1px solid var(--card-border);padding:8px 14px;border-radius:8px;text-align:center;min-width:110px">
        <div id="statCobertura" style="font-size:1.25rem;font-weight:800;color:#0ea5e9">—</div>
        <div style="font-size:0.72rem;color:var(--text-dim);text-transform:uppercase;font-weight:600" title="Resultados con juicios emitidos vs total definidos en el Proyecto Formativo">Cobertura PDF</div>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     DIAGNÓSTICO PREDICTIVO & SEMÁFORO DE FASES (CUELLOS DE BOTELLA)
     ══════════════════════════════════════════════════════════ -->
<div class="card fade-in mb-24" id="cardDiagnosticoFases" style="display:none;padding:18px 22px;border-radius:12px;background:var(--bg2);border:1px solid var(--card-border);">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px">
    <div style="display:flex;align-items:center;gap:14px">
      <div id="diagIconWrap" style="width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;background:rgba(57,169,0,0.12);color:#39A900">
        ⚡
      </div>
      <div>
        <div style="display:flex;align-items:center;gap:8px">
          <span style="font-weight:700;font-size:0.98rem;color:var(--text)" id="diagTitulo">Diagnóstico del Flujo Formativo</span>
          <span id="diagBadge" class="badge badge-green" style="font-size:0.75rem;padding:3px 8px">Al día</span>
        </div>
        <div style="font-size:0.83rem;color:var(--text-muted);margin-top:3px" id="diagMensaje">
          Analizando el avance de las fases del proyecto...
        </div>
      </div>
    </div>
    <div id="diagDetallePills" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <!-- Pills dinámicos generados en JS -->
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     LÍNEA DE TIEMPO MEJORADA — Proyecto Formativo vs Juicios
     ══════════════════════════════════════════════════════════ -->
<div class="card fade-in mb-24" style="padding-bottom:40px">
  <div class="section-title" style="margin-bottom:8px">🗺 Línea de Tiempo — Avance por Fase</div>
  <p style="color:var(--text-muted);font-size:0.83rem;margin-bottom:32px">
    Compara los resultados de aprendizaje definidos en el <strong>Proyecto Formativo (PDF)</strong> con los juicios evaluativos registrados en el <strong>Excel</strong> para los aprendices en formación.
  </p>

  <div id="phaseTimeline" style="position:relative">
    <div class="loading"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></div>
  </div>
</div>

<!-- Gráficas -->
<div class="grid-2 mb-24">
  <div class="card fade-in stagger-1">
    <div class="section-title mb-16">📊 Aprobados vs Pendientes por Fase</div>
    <div class="chart-box"><canvas id="chartFasesBar"></canvas></div>
  </div>
  <div class="card fade-in stagger-2">
    <div class="section-title mb-16">🍩 Cumplimiento Global</div>
    <div style="display:flex;justify-content:center;align-items:center;height:100%">
      <div class="donut-wrap" style="width:220px;height:220px">
        <canvas id="chartFasesDonut"></canvas>
        <div class="donut-center"><span id="pctGlobalFases" style="color:#39A900">—</span><small>Cumplimiento</small></div>
      </div>
    </div>
  </div>
</div>

<!-- Detalle por fase con tabla expandible -->
<div class="card fade-in stagger-3">
  <div class="section-header mb-16" style="flex-wrap:wrap;gap:12px">
    <div class="section-title">📋 Detalle de Aprendices por Fase</div>
    
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;width:100%;background:var(--bg2);padding:10px 14px;border-radius:10px;border:1px solid var(--card-border);margin-top:8px">
      <!-- Buscador por aprendiz / documento -->
      <div style="position:relative;flex:1 1 180px;min-width:170px">
        <input type="text" id="filtroBuscarAprendiz" placeholder="Buscar aprendiz o documento..." oninput="onFiltrosDetalleChange()" style="padding:7px 12px 7px 30px;font-size:0.82rem;border-radius:6px;border:1px solid var(--card-border);background:var(--bg);color:var(--text);width:100%">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--text-dim)"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
      </div>

      <!-- Filtro selector de Fase -->
      <div style="flex:1 1 150px;min-width:140px">
        <select id="filtroFase" onchange="cargarDetalle(this.value)" style="padding:7px 10px;font-size:0.82rem;border-radius:6px;border:1px solid var(--card-border);background:var(--bg);color:var(--text);width:100%">
          <option value="">Todas las fases</option>
        </select>
      </div>

      <!-- Buscador / Filtro por Competencia -->
      <div style="position:relative;flex:1 1 190px;min-width:160px">
        <input type="text" id="filtroCompetencia" placeholder="Filtrar por competencia..." oninput="onFiltrosDetalleChange()" style="padding:7px 12px 7px 28px;font-size:0.82rem;border-radius:6px;border:1px solid var(--card-border);background:var(--bg);color:var(--text);width:100%">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:13px;height:13px;position:absolute;left:8px;top:50%;transform:translateY(-50%);color:var(--text-dim)"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
      </div>

      <!-- Buscador / Filtro por Resultado de Aprendizaje -->
      <div style="position:relative;flex:1 1 190px;min-width:160px">
        <input type="text" id="filtroResultado" placeholder="Filtrar por resultado..." oninput="onFiltrosDetalleChange()" style="padding:7px 12px 7px 28px;font-size:0.82rem;border-radius:6px;border:1px solid var(--card-border);background:var(--bg);color:var(--text);width:100%">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:13px;height:13px;position:absolute;left:8px;top:50%;transform:translateY(-50%);color:var(--text-dim)"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
      </div>

      <!-- Filtro estado en fase -->
      <div style="flex:1 1 150px;min-width:140px">
        <select id="filtroEstadoFase" onchange="onFiltrosDetalleChange()" style="padding:7px 10px;font-size:0.82rem;border-radius:6px;border:1px solid var(--card-border);background:var(--bg);color:var(--text);width:100%">
          <option value="">Todos los estados</option>
          <option value="pendientes">⚠️ Con juicios pendientes</option>
          <option value="aprobados">✓ 100% al día</option>
        </select>
      </div>

      <!-- Botón limpiar filtros -->
      <button type="button" class="btn btn-secondary btn-sm" onclick="limpiarFiltrosDetalle()" style="padding:7px 12px;font-size:0.78rem;border:1px solid var(--card-border);white-space:nowrap" title="Limpiar todos los filtros">
        ✕ Limpiar
      </button>
    </div>
  </div>

  <!-- Leyenda informativa de convenciones -->
  <div style="display:flex;align-items:center;gap:16px;margin-bottom:14px;padding:8px 12px;background:rgba(255,255,255,0.02);border-radius:8px;border:1px solid rgba(255,255,255,0.06);font-size:0.78rem;color:var(--text-muted);flex-wrap:wrap">
    <strong style="color:var(--text)">Convenciones:</strong>
    <span><span class="badge badge-green" style="font-size:0.75rem;padding:2px 6px">✓ Aprobados</span> : Resultados con juicio aprobado</span>
    <span><span class="badge badge-yellow" style="font-size:0.75rem;padding:2px 6px">⏳ Pendientes</span> : Resultados aún por evaluar en Sofía Plus</span>
    <span><span class="badge badge-red" style="font-size:0.75rem;padding:2px 6px">✕ No aprobado</span> : Resultados reprobados</span>
    <span style="margin-left:auto;font-style:italic">Indicador: <strong style="color:var(--text)">Resultados evaluados / Resultados del proyecto</strong></span>
  </div>

  <div id="accordionFases"></div>
</div>

<style>
/* ── Línea de tiempo mejorada ── */
.fase-timeline-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
  position: relative;
}
.fase-timeline-grid::before {
  content: '';
  position: absolute;
  top: 40px;
  left: 40px;
  right: 40px;
  height: 2px;
  background: linear-gradient(90deg, rgba(57,169,0,0.4), rgba(255,109,0,0.3));
  z-index: 0;
}
.fase-card {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 14px;
  padding: 20px;
  position: relative;
  z-index: 1;
  transition: transform 0.2s, box-shadow 0.2s;
}
.fase-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 40px rgba(0,0,0,0.3);
}
.fase-card.done   { border-color: rgba(57,169,0,0.4);  background: rgba(57,169,0,0.06);  }
.fase-card.mid    { border-color: rgba(255,193,7,0.4);  background: rgba(255,193,7,0.04);  }
.fase-card.low    { border-color: rgba(255,109,0,0.35); background: rgba(255,109,0,0.04); }
.fase-card.empty  { border-color: rgba(255,255,255,0.05); opacity:.7; }

.fase-num {
  width: 44px; height: 44px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem; font-weight: 800;
  margin: 0 auto 12px;
}
.fase-card.done  .fase-num { background: rgba(57,169,0,0.2);  color: #39A900; }
.fase-card.mid   .fase-num { background: rgba(255,193,7,0.2);  color: #FFC107; }
.fase-card.low   .fase-num { background: rgba(255,109,0,0.2);  color: #FF6D00; }
.fase-card.empty .fase-num { background: rgba(255,255,255,0.07); color: #7a8fa6; }

.fase-nombre { font-weight: 700; font-size: 0.95rem; text-align:center; margin-bottom:16px; color:var(--text-light); }

.fase-progress-wrap {
  background: rgba(0,0,0,0.25);
  border-radius: 10px;
  height: 10px;
  margin-bottom: 10px;
  overflow: hidden;
}
.fase-progress-bar {
  height: 100%;
  border-radius: 10px;
  transition: width 1s ease;
}
.fase-pct { font-size: 1.4rem; font-weight: 800; text-align:center; margin-bottom: 4px; }
.fase-card.done  .fase-pct { color: #39A900; }
.fase-card.mid   .fase-pct { color: #FFC107; }
.fase-card.low   .fase-pct { color: #FF6D00; }
.fase-card.empty .fase-pct { color: #7a8fa6; }

.fase-stats {
  display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 14px;
}
.fase-stat {
  background: rgba(0,0,0,0.2);
  border-radius: 8px;
  padding: 8px;
  text-align: center;
}
[data-theme="light"] .fase-stat {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}
[data-theme="light"] .fase-progress-wrap {
  background: #e2e8f0;
}
.fase-stat-val { font-size: 1rem; font-weight: 700; color: var(--text-light); }
[data-theme="light"] .fase-stat-val { color: var(--text); }
.fase-stat-lbl { font-size: 0.7rem; color: var(--text-dim); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px; }

.fase-detail-row {
  display: flex; justify-content: space-between; align-items: center;
  font-size: 0.78rem; color: var(--text-muted); margin-top: 10px;
  border-top: 1px solid rgba(255,255,255,0.05); padding-top: 10px;
}
.fase-legend {
  display: flex; gap: 12px; margin-bottom: 16px; font-size: 0.8rem; color: var(--text-muted);
}
.legend-dot { width: 10px; height: 10px; border-radius: 50%; display:inline-block; margin-right:4px; }
</style>

<script>
let chartBar, chartDonut;
let currentResumenData = null;
let currentCumplimientoFases = [];

function escapeHtml(str) {
  return String(str ?? '').replace(/[&<>"']/g, function(m) {
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];
  });
}

function badgeJuicio(t) {
  if (t === 'Aprobado')    return `<span class="badge badge-green">✓ Aprobado</span>`;
  if (t === 'Por evaluar') return `<span class="badge badge-orange">⏳ Por evaluar</span>`;
  return `<span class="badge badge-red">✗ No aprobado</span>`;
}

function actualizarDashboardFases() {
  const idFicha = document.getElementById('filtroProgramaGlobal').value;
  const urlCumplimiento = (window.BASE_URL || '') + 'index.php?module=fases&action=cumplimiento' + (idFicha ? `&id_ficha=${idFicha}` : '');
  const urlResumen = (window.BASE_URL || '') + 'index.php?module=fases&action=resumen_grupo' + (idFicha ? `&id_ficha=${idFicha}` : '');

  // 1. Cargar Resumen del Grupo (Iniciaron, Activos, Retirados, Deserción, Cobertura)
  fetch(urlResumen).then(r=>r.json()).then(res=>{
    currentResumenData = res;
    if(res) {
      document.getElementById('statMatriculados').textContent = res.total_matriculados ?? '—';
      document.getElementById('statActivos').textContent = res.total_activos ?? '—';
      document.getElementById('statDesertados').textContent = res.total_desertados ?? '—';
      document.getElementById('statTasaDesercion').textContent = (res.tasa_desercion ?? 0) + '%';
      
      const cob = res.cobertura;
      if (cob && cob.total_proyecto > 0) {
        document.getElementById('statCobertura').innerHTML = `${cob.porcentaje}% <span style="font-size:0.7rem;color:var(--text-dim);font-weight:normal">(${cob.evaluados}/${cob.total_proyecto})</span>`;
      } else {
        document.getElementById('statCobertura').textContent = '—';
      }
    }
  }).catch(e => console.error("Error al cargar resumen de grupo", e));

  // 2. Cargar Cumplimiento por Fase
  fetch(urlCumplimiento).then(r=>r.json()).then(fases=>{
    currentCumplimientoFases = Array.isArray(fases) ? fases : [];

    // ── LÍNEA DE TIEMPO MEJORADA ──────────────────────────────
    const timeline = document.getElementById('phaseTimeline');

    if (!fases.length) {
      timeline.innerHTML = `
        <div style="padding:24px;text-align:center;background:var(--bg2);border-radius:10px;border:1px dashed var(--card-border)">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:36px;height:36px;margin:0 auto 10px;color:var(--text-dim);display:block">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
          </svg>
          <p style="color:var(--text);font-weight:600;margin-bottom:4px">Sin datos de avance o fases para mostrar</p>
          <p class="text-muted" style="font-size:0.85rem;max-width:520px;margin:0 auto">
            Si la ficha no tiene aprendices activos matriculados (0 aprendices), o aún no tiene un Proyecto Formativo asignado, no es posible calcular los porcentajes de avance por fase.
          </p>
        </div>
      `;
    } else {
      let legendHtml = `
        <div class="fase-legend">
          <span><span class="legend-dot" style="background:#39A900"></span>≥ 80 % — Avanzado</span>
          <span><span class="legend-dot" style="background:#FFC107"></span>50–79 % — En progreso</span>
          <span><span class="legend-dot" style="background:#FF6D00"></span>&lt; 50 % — Inicial</span>
          <span><span class="legend-dot" style="background:#4a5f78"></span>Sin datos</span>
        </div>
      `;

      let cardsHtml = '<div class="fase-timeline-grid">';
      fases.forEach((f, i) => {
        const pct  = parseFloat(f.porcentaje_cumplimiento_fase) || 0;
        const paresTotal    = +f.pares_total    || 0;
        const paresAprobados= +f.pares_aprobados|| 0;
        const paresNo       = +f.pares_no_aprobados || 0;
        const paresPendientes= +f.pares_pendientes || 0;
        const resultados    = +f.total_resultados_fase || 0;
        const aprendices    = +f.total_aprendices || 0;

        const cls  = paresTotal === 0 ? 'empty' : pct >= 80 ? 'done' : pct >= 50 ? 'mid' : 'low';

        const barColor = pct >= 80 ? '#39A900' : pct >= 50 ? '#FFC107' : '#FF6D00';

        // Barra de progreso: aprobado (verde) + no aprobado (rojo) del total de pares
        const pctAprobado = paresTotal ? Math.round(paresAprobados*100/paresTotal) : 0;
        const pctNoAprobado = paresTotal ? Math.round(paresNo*100/paresTotal) : 0;

        cardsHtml += `
          <div class="fase-card ${cls}" style="animation: fadeIn .5s ease ${i*0.15}s both">
            <div class="fase-num">${f.orden}</div>
            <div class="fase-nombre">${f.nombre_fase}</div>

            <div class="fase-pct">${pct}%</div>
            <div style="font-size:0.75rem;text-align:center;color:var(--text-dim);margin-bottom:10px">de cumplimiento</div>

            <!-- Barra de progreso segmentada -->
            <div class="fase-progress-wrap" title="Verde: Aprobado · Rojo: No aprobado">
              <div style="display:flex;height:100%">
                <div class="fase-progress-bar" style="width:${pctAprobado}%;background:#39A900;border-radius:10px 0 0 10px"></div>
                <div class="fase-progress-bar" style="width:${pctNoAprobado}%;background:#FF6D00;border-radius:${pctAprobado===0?'10px 0 0 10px':'0'}"></div>
              </div>
            </div>

            <!-- Stats -->
            <div class="fase-stats">
              <div class="fase-stat">
                <div class="fase-stat-val" style="color:#39A900">${paresAprobados}</div>
                <div class="fase-stat-lbl">Aprobados</div>
              </div>
              <div class="fase-stat">
                <div class="fase-stat-val" style="color:#FF6D00">${paresPendientes}</div>
                <div class="fase-stat-lbl">Por evaluar</div>
              </div>
              <div class="fase-stat">
                <div class="fase-stat-val">${resultados}</div>
                <div class="fase-stat-lbl">Resultados PF</div>
              </div>
              <div class="fase-stat">
                <div class="fase-stat-val">${aprendices}</div>
                <div class="fase-stat-lbl">Aprendices Activos</div>
              </div>
            </div>

            <div class="fase-detail-row">
              <span>Pares totales:</span>
              <strong style="color:var(--text-light)">${paresTotal}</strong>
            </div>
            ${paresNo > 0 ? `<div class="fase-detail-row"><span style="color:#FF6D00">No aprobados:</span><strong style="color:#FF6D00">${paresNo}</strong></div>` : ''}
          </div>
        `;
      });
      cardsHtml += '</div>';
      timeline.innerHTML = legendHtml + cardsHtml;
    }

    // ── SELECT de fases para filtro ───────────────────────────
    const sel = document.getElementById('filtroFase');
    const valorPrevio = sel.value;
    sel.innerHTML = '<option value="">Todas las fases</option>';
    fases.forEach(f => {
      const o = document.createElement('option');
      o.value = f.id_fase;
      o.textContent = f.nombre_fase;
      if (String(f.id_fase) === String(valorPrevio)) o.selected = true;
      sel.appendChild(o);
    });

    // ── BAR CHART — aprobados vs pendientes ───────────────────
    const ctx1 = document.getElementById('chartFasesBar').getContext('2d');
    if(chartBar) chartBar.destroy();
    chartBar = new Chart(ctx1, {
      type: 'bar',
      data: {
        labels: fases.map(f => f.nombre_fase),
        datasets: [
          {
            label: 'Pares Aprobados',
            data: fases.map(f => +f.pares_aprobados||0),
            backgroundColor: 'rgba(57,169,0,.8)',
            borderRadius: 4
          },
          {
            label: 'Pares Pendientes',
            data: fases.map(f => +f.pares_pendientes||0),
            backgroundColor: 'rgba(255,193,7,.7)',
            borderRadius: 4
          },
          {
            label: 'No Aprobados',
            data: fases.map(f => +f.pares_no_aprobados||0),
            backgroundColor: 'rgba(255,109,0,.8)',
            borderRadius: 4
          },
        ]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'top', labels: { color: '#ccc' } } },
        scales: {
          x: { stacked: false, ticks: { color: '#8a9ab5' } },
          y: { ticks: { color: '#8a9ab5' }, grid: { color: 'rgba(255,255,255,0.05)' } }
        }
      }
    });

    // ── DONUT CHART — cumplimiento global ────────────────────
    const totalAprobados  = fases.reduce((s,f) => s + (+f.pares_aprobados||0), 0);
    const totalPendientes = fases.reduce((s,f) => s + (+f.pares_pendientes||0), 0);
    const totalNoAprobados= fases.reduce((s,f) => s + (+f.pares_no_aprobados||0), 0);
    const totalPares      = fases.reduce((s,f) => s + (+f.pares_total||0), 0);
    const pctGlobal       = totalPares ? Math.round(totalAprobados*100/totalPares) : 0;
    document.getElementById('pctGlobalFases').textContent = pctGlobal + '%';

    const ctx2 = document.getElementById('chartFasesDonut').getContext('2d');
    if(chartDonut) chartDonut.destroy();
    chartDonut = new Chart(ctx2, {
      type: 'doughnut',
      data: {
        labels: ['Aprobados', 'Por evaluar', 'No aprobados'],
        datasets: [{
          data: [totalAprobados, totalPendientes, totalNoAprobados],
          backgroundColor: ['rgba(57,169,0,.85)', 'rgba(255,193,7,.75)', 'rgba(255,109,0,.85)'],
          borderWidth: 0,
          cutout: '78%'
        }]
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: '#ccc' } } } }
    });

    // ── DIAGNÓSTICO DE RITMO & CUELLOS DE BOTELLA ────────────────
    const diagCard = document.getElementById('cardDiagnosticoFases');
    if (!fases.length) {
      diagCard.style.display = 'none';
    } else {
      diagCard.style.display = 'block';
      
      // Buscar cuello de botella: fase con menor cumplimiento que ya tenga resultados
      let cuellos = [];
      let fasesCompletadas = 0;
      let fasesEnCurso = 0;
      let fasesCriticas = [];

      fases.forEach((f, idx) => {
        const pct = parseFloat(f.porcentaje_cumplimiento_fase) || 0;
        const totalPares = +f.pares_total || 0;
        const pendientes = +f.pares_pendientes || 0;

        if (pct >= 85) {
          fasesCompletadas++;
        } else if (pct > 0) {
          fasesEnCurso++;
          if (pct < 50) {
            fasesCriticas.push({ nombre: f.nombre_fase, pct: pct, pendientes: pendientes, orden: f.orden || (idx+1) });
          }
        }
      });

      const iconWrap = document.getElementById('diagIconWrap');
      const badge = document.getElementById('diagBadge');
      const titulo = document.getElementById('diagTitulo');
      const mensaje = document.getElementById('diagMensaje');
      const pills = document.getElementById('diagDetallePills');

      // Ordenar críticas por orden curricular (las primeras fases retrasadas son cuellos de botella directos)
      fasesCriticas.sort((a, b) => a.orden - b.orden);

      if (fasesCriticas.length > 0) {
        const principal = fasesCriticas[0];
        iconWrap.style.background = 'rgba(239,68,68,0.15)';
        iconWrap.style.color = '#ef4444';
        iconWrap.textContent = '⚠️';
        badge.className = 'badge badge-red';
        badge.textContent = 'Cuello de Botella Detectado';
        titulo.textContent = `Atención en Fase ${principal.nombre}`;
        mensaje.innerHTML = `La fase <strong>${principal.nombre}</strong> presenta un avance del <strong>${principal.pct}%</strong> con <strong>${principal.pendientes}</strong> juicios pendientes en aprendices activos, requiriendo refuerzo antes de continuar la ruta formativa.`;
        
        pills.innerHTML = `
          <span class="badge" style="background:rgba(239,68,68,0.12);color:#ef4444;border:1px solid rgba(239,68,68,0.3);padding:6px 12px;font-size:0.8rem">
            🛑 Retraso principal: ${principal.nombre} (${principal.pct}%)
          </span>
          <span class="badge" style="background:rgba(255,193,7,0.12);color:#d97706;border:1px solid rgba(255,193,7,0.3);padding:6px 12px;font-size:0.8rem">
            ⏳ Fases en curso: ${fasesEnCurso}
          </span>
        `;
      } else if (fasesEnCurso > 0) {
        iconWrap.style.background = 'rgba(255,193,7,0.15)';
        iconWrap.style.color = '#d97706';
        iconWrap.textContent = '⏱️';
        badge.className = 'badge badge-yellow';
        badge.textContent = 'En Proceso';
        titulo.textContent = 'Flujo Formativo Estable';
        mensaje.innerHTML = `El grupo avanza dentro de los parámetros esperados con <strong>${fasesCompletadas}</strong> fase(s) consolidadas y <strong>${fasesEnCurso}</strong> fase(s) activas sin cuellos de botella críticos.`;
        
        pills.innerHTML = `
          <span class="badge" style="background:rgba(57,169,0,0.12);color:#39A900;border:1px solid rgba(57,169,0,0.3);padding:6px 12px;font-size:0.8rem">
            ✓ ${fasesCompletadas} Fases al día
          </span>
          <span class="badge" style="background:rgba(59,130,246,0.12);color:#3b82f6;border:1px solid rgba(59,130,246,0.3);padding:6px 12px;font-size:0.8rem">
            ⚡ Ritmo continuo
          </span>
        `;
      } else {
        iconWrap.style.background = 'rgba(57,169,0,0.15)';
        iconWrap.style.color = '#39A900';
        iconWrap.textContent = '🎉';
        badge.className = 'badge badge-green';
        badge.textContent = 'Excelente';
        titulo.textContent = 'Programa Culminado o al Día';
        mensaje.innerHTML = `Todas las fases evaluadas alcanzan o superan el 85% de aprobación en los aprendices activos del grupo.`;
        
        pills.innerHTML = `
          <span class="badge badge-green" style="padding:6px 12px;font-size:0.8rem">
            ★ 100% de Fases Consolidadas
          </span>
        `;
      }
    }

    cargarDetalle(sel.value);
  });
}

let rawDetalleRows = [];
let detallePagination = {}; // { [fase]: { page: 1, perPage: 15 } }

function onFiltrosDetalleChange() {
  renderDetalleConFiltros();
}

function cargarDetalle(idFase){
  const idFicha = document.getElementById('filtroProgramaGlobal').value;
  const baseUrl = window.BASE_URL || '';
  const cleanBase = baseUrl.endsWith('/') ? baseUrl : (baseUrl + '/');
  let url = cleanBase + 'index.php?module=fases&action=detalle';
  if (idFase)  url += '&id_fase=' + encodeURIComponent(idFase);
  if (idFicha) url += '&id_ficha=' + encodeURIComponent(idFicha);

  const container = document.getElementById('accordionFases');
  container.innerHTML = '<div class="loading" style="padding:20px"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Cargando aprendices...</div>';

  fetch(url)
    .then(async r => {
      if (!r.ok) {
        const txt = await r.text();
        throw new Error(`HTTP ${r.status}: ${txt.substring(0, 150)}`);
      }
      return r.json();
    })
    .then(rows => {
      if (rows && rows.error) {
        throw new Error(rows.message || rows.error);
      }
      rawDetalleRows = Array.isArray(rows) ? rows : [];
      detallePagination = {};
      renderDetalleConFiltros();
    })
    .catch(e => {
      console.error("Error cargando detalle:", e);
      container.innerHTML = `<div style="padding:16px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);border-radius:8px;color:#ef4444;font-size:0.85rem">
        <strong>Error al cargar el detalle de aprendices:</strong><br>${escapeHtml(e.message)}
        <br><button class="btn btn-sm btn-secondary" style="margin-top:10px" onclick="cargarDetalle(document.getElementById('filtroFase')?.value)">Reintentar</button>
      </div>`;
    });
}

function limpiarFiltrosDetalle() {
  const b1 = document.getElementById('filtroBuscarAprendiz');
  const b2 = document.getElementById('filtroResultado');
  const b3 = document.getElementById('filtroCompetencia');
  const s1 = document.getElementById('filtroEstadoFase');
  const s3 = document.getElementById('filtroFase');
  if (b1) b1.value = '';
  if (b2) b2.value = '';
  if (b3) b3.value = '';
  if (s1) s1.value = '';
  if (s3 && s3.value !== '') {
    s3.value = '';
    cargarDetalle('');
    return;
  }
  renderDetalleConFiltros();
}

function renderDetalleConFiltros() {
  const query = (document.getElementById('filtroBuscarAprendiz')?.value || '').trim().toLowerCase();
  const filtroEstado = document.getElementById('filtroEstadoFase')?.value || '';
  const filtroComp = (document.getElementById('filtroCompetencia')?.value || '').trim().toLowerCase();
  const filtroRap = (document.getElementById('filtroResultado')?.value || '').trim().toLowerCase();
  const container = document.getElementById('accordionFases');

  if (!rawDetalleRows.length) {
    container.innerHTML = '<p class="text-muted" style="padding:16px">Sin datos de aprendices para la selección actual.</p>';
    return;
  }

  // Filtrar rawDetalleRows por competencia y por resultado de aprendizaje antes de agrupar
  let scopedRows = rawDetalleRows;
  if (filtroComp) {
    scopedRows = scopedRows.filter(r => (r.competencia || '').toLowerCase().includes(filtroComp));
  }
  if (filtroRap) {
    scopedRows = scopedRows.filter(r => (r.resultado_aprendizaje || '').toLowerCase().includes(filtroRap));
  }

  if (!scopedRows.length) {
    container.innerHTML = '<p class="text-muted" style="padding:16px">No se encontraron resultados ni aprendices con los filtros de competencia o resultado seleccionados.</p>';
    return;
  }

  // Agrupar por fase
  const byFase = {};
  scopedRows.forEach(r=>{
    const k = r.nombre_fase || 'Sin fase';
    if(!byFase[k]) byFase[k]=[];
    byFase[k].push(r);
  });

  const html = Object.entries(byFase).map(([fase, allItems], faseIndex)=>{
    // Estructurar aprendices y actividades para esta fase
    const actividadesMap = {};
    const aprendicesMap = {};

    allItems.forEach(it => {
      const actId = it.id_actividad || it.actividad;
      if (!actividadesMap[actId]) {
        actividadesMap[actId] = it.actividad || 'Actividad';
      }
      const appDoc = String(it.documento || it.aprendiz);
      if (!aprendicesMap[appDoc]) {
        aprendicesMap[appDoc] = {
          documento: it.documento || '',
          nombre: it.aprendiz,
          estado_aprendiz: it.estado_aprendiz,
          actividades: {},
          totalGeneral: 0,
          aprobadosGeneral: 0,
          pendientesGeneral: 0,
          noAprobadosGeneral: 0
        };
      }
      if (!aprendicesMap[appDoc].actividades[actId]) {
        aprendicesMap[appDoc].actividades[actId] = { total: 0, aprobados: 0, noAprobados: 0, pendientes: 0 };
      }
      aprendicesMap[appDoc].totalGeneral++;
      aprendicesMap[appDoc].actividades[actId].total++;

      if (it.estado_en_fase === 'Aprobado') {
        aprendicesMap[appDoc].aprobadosGeneral++;
        aprendicesMap[appDoc].actividades[actId].aprobados++;
      } else if (it.estado_en_fase === 'No aprobado') {
        aprendicesMap[appDoc].noAprobadosGeneral++;
        aprendicesMap[appDoc].actividades[actId].noAprobados++;
      } else {
        aprendicesMap[appDoc].pendientesGeneral++;
        aprendicesMap[appDoc].actividades[actId].pendientes++;
      }
    });

    // Solo incluir actividades que tengan al menos un resultado en esta fase
    const actKeys = Object.keys(actividadesMap).filter(ak => {
      // Verificar que la actividad tenga resultados reales cargados en los aprendices
      return Object.values(aprendicesMap).some(app => app.actividades[ak] && app.actividades[ak].total > 0);
    });
    // Ordenar actividades naturalmente por número/nombre
    actKeys.sort((a, b) => {
      const nomA = (actividadesMap[a] || '').toLowerCase();
      const nomB = (actividadesMap[b] || '').toLowerCase();
      return nomA.localeCompare(nomB, undefined, { numeric: true, sensitivity: 'base' });
    });
    let appKeys = Object.keys(aprendicesMap);

    // Filtrar aprendices por buscador (nombre o documento)
    if (query) {
      appKeys = appKeys.filter(k => {
        const a = aprendicesMap[k];
        return (a.nombre || '').toLowerCase().includes(query) || (a.documento || '').toLowerCase().includes(query);
      });
    }

    // Filtrar aprendices por estado en fase
    if (filtroEstado === 'pendientes') {
      appKeys = appKeys.filter(k => (aprendicesMap[k].pendientesGeneral > 0 || aprendicesMap[k].noAprobadosGeneral > 0));
    } else if (filtroEstado === 'aprobados') {
      appKeys = appKeys.filter(k => (aprendicesMap[k].totalGeneral > 0 && aprendicesMap[k].aprobadosGeneral === aprendicesMap[k].totalGeneral));
    }

    // Paginación por fase
    if (!detallePagination[fase]) {
      detallePagination[fase] = { page: 1, perPage: 15 };
    }
    const pag = detallePagination[fase];
    const totalFiltered = appKeys.length;
    const totalPages = Math.max(1, Math.ceil(totalFiltered / pag.perPage));
    if (pag.page > totalPages) pag.page = totalPages;
    const startIdx = (pag.page - 1) * pag.perPage;
    const pageKeys = appKeys.slice(startIdx, startIdx + pag.perPage);

    const tieneFiltrosActivos = Boolean(query || filtroEstado || filtroComp || filtroRap);

    // Si se aplicó algún filtro/búsqueda y no hay coincidencias en esta fase, no mostrar el acordeón
    if (tieneFiltrosActivos && appKeys.length === 0) {
      return '';
    }

    // Conteo de juicios: si se filtra, cuenta solo los de los aprendices filtrados; si no, de toda la fase
    let totalAprob = 0;
    let totalPend = 0;
    if (tieneFiltrosActivos) {
      appKeys.forEach(k => {
        totalAprob += aprendicesMap[k].aprobadosGeneral;
        totalPend  += (aprendicesMap[k].pendientesGeneral + aprendicesMap[k].noAprobadosGeneral);
      });
    } else {
      totalAprob = allItems.filter(x=>x.estado_en_fase==='Aprobado').length;
      totalPend  = allItems.filter(x=>x.estado_en_fase!=='Aprobado').length;
    }
    const id = 'acc_'+fase.replace(/[^a-zA-Z0-9]/g,'_');
    const idMatrix = 'mat_'+id;
    const idList = 'list_'+id;

    // Generar tabla matriz paginada
    let matrixHtml = `
      <div class="table-wrap" style="margin-top:10px">
        <table class="table-compact" style="font-size:0.83rem">
          <thead>
            <tr>
              <th style="min-width:200px">Aprendiz Activo (${totalFiltered} encontrados)</th>
              ${actKeys.map((ak, idx) => `<th style="text-align:center;max-width:160px" title="${escapeHtml(actividadesMap[ak])}">
                <span style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px">
                  Act. ${idx+1}: ${escapeHtml(actividadesMap[ak])}
                </span>
              </th>`).join('')}
              <th style="text-align:center;width:90px">Progreso</th>
            </tr>
          </thead>
          <tbody>
            ${pageKeys.length === 0 ? `<tr><td colspan="${actKeys.length + 2}" style="text-align:center;padding:16px;color:var(--text-muted)">No hay aprendices que coincidan con los filtros en esta fase.</td></tr>` : 
              pageKeys.map(ak => {
                const app = aprendicesMap[ak];
                const cells = actKeys.map(kAct => {
                  const data = app.actividades[kAct];
                  if (!data || data.total === 0) {
                    return `<td style="text-align:center;color:var(--text-dim)">—</td>`;
                  }
                  if (data.aprobados === data.total) {
                    return `<td style="text-align:center">
                      <span class="badge badge-green" style="font-size:0.75rem;padding:3px 8px;min-width:32px" title="Aprobado (${data.aprobados}/${data.total})">
                        ✓ ${data.aprobados}/${data.total}
                      </span>
                    </td>`;
                  } else if (data.noAprobados > 0) {
                    return `<td style="text-align:center">
                      <span class="badge badge-red" style="font-size:0.75rem;padding:3px 8px;min-width:32px" title="No aprobado: ${data.noAprobados}">
                        ✕ ${data.aprobados}/${data.total}
                      </span>
                    </td>`;
                  } else {
                    return `<td style="text-align:center">
                      <span class="badge badge-yellow" style="font-size:0.75rem;padding:3px 8px;min-width:32px" title="Pendiente de evaluar: ${data.pendientes}">
                        ⏳ ${data.aprobados}/${data.total}
                      </span>
                    </td>`;
                  }
                }).join('');

                const pctApp = app.totalGeneral > 0 ? Math.round(app.aprobadosGeneral * 100 / app.totalGeneral) : 0;
                const badgeClass = pctApp >= 80 ? 'badge-green' : (pctApp >= 50 ? 'badge-yellow' : 'badge-orange');

                return `<tr>
                  <td>
                    <strong>${escapeHtml(app.nombre)}</strong>
                    ${app.documento ? `<br><small class="text-muted">${escapeHtml(app.documento)}</small>` : ''}
                  </td>
                  ${cells}
                  <td style="text-align:center">
                    <span class="badge ${badgeClass}" style="font-weight:700">${pctApp}%</span>
                  </td>
                </tr>`;
              }).join('')
            }
          </tbody>
        </table>
      </div>
    `;

    // Paginación independiente para Lista Detallada (20 filas por página)
    if (!detallePagination[fase + '_lista']) {
      detallePagination[fase + '_lista'] = { page: 1, perPage: 20 };
    }
    const pagLista = detallePagination[fase + '_lista'];

    // Filtrar todos los registros de allItems por los filtros aplicados (aprendices válidos)
    const validAppDocsSet = new Set(appKeys);
    const allFilteredListItems = allItems.filter(it => validAppDocsSet.has(String(it.documento || it.aprendiz)));
    const totalListItems = allFilteredListItems.length;
    const totalPagesLista = Math.max(1, Math.ceil(totalListItems / pagLista.perPage));
    if (pagLista.page > totalPagesLista) pagLista.page = totalPagesLista;
    const startIdxLista = (pagLista.page - 1) * pagLista.perPage;
    const pageListItems = allFilteredListItems.slice(startIdxLista, startIdxLista + pagLista.perPage);

    let listHtml = `
      <div class="table-wrap">
        <table class="table-compact">
          <thead><tr><th>Aprendiz</th><th>Actividad</th><th>Competencia</th><th>Resultado (Proyecto)</th><th>Juicio Evaluativo</th></tr></thead>
          <tbody>
            ${pageListItems.length === 0 ? `<tr><td colspan="5" style="text-align:center;padding:16px;color:var(--text-muted)">Sin registros para esta página.</td></tr>` : 
              pageListItems.map(x=>`<tr>
                <td><strong>${escapeHtml(x.aprendiz||'—')}</strong>${x.documento?`<br><small class="text-muted">${escapeHtml(x.documento)}</small>`:''}</td>
                <td style="font-size:0.8rem;color:var(--text-dim)">${escapeHtml(x.actividad||'—')}</td>
                <td class="text-muted" style="font-size:0.8rem">${escapeHtml(x.competencia||'—')}</td>
                <td class="text-muted" style="font-size:0.8rem">${escapeHtml(x.resultado_aprendizaje||'—')}</td>
                <td>${badgeJuicio(x.estado_en_fase||'Por evaluar')}</td>
              </tr>`).join('')
            }
          </tbody>
        </table>
      </div>
      ${totalPagesLista > 1 ? `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding:8px 12px;background:var(--bg2);border-radius:8px;font-size:0.82rem;flex-wrap:wrap;gap:8px">
          <div style="color:var(--text-muted)">
            Mostrando <strong>${startIdxLista + 1}–${Math.min(startIdxLista + pagLista.perPage, totalListItems)}</strong> de <strong>${totalListItems}</strong> registros
          </div>
          <div style="display:flex;gap:6px;align-items:center">
            <button class="btn btn-secondary btn-sm" onclick="cambiarPaginaFase('${escapeHtml(fase).replace(/'/g, "\\'")}_lista', ${pagLista.page - 1})" ${pagLista.page <= 1 ? 'disabled style="opacity:0.5;cursor:not-allowed"' : ''}>
              ◀ Anterior
            </button>
            <span style="padding:0 6px;color:var(--text);font-weight:600">Pág. ${pagLista.page} de ${totalPagesLista}</span>
            <button class="btn btn-secondary btn-sm" onclick="cambiarPaginaFase('${escapeHtml(fase).replace(/'/g, "\\'")}_lista', ${pagLista.page + 1})" ${pagLista.page >= totalPagesLista ? 'disabled style="opacity:0.5;cursor:not-allowed"' : ''}>
              Siguiente ▶
            </button>
          </div>
        </div>
      ` : ''}
    `;

    // Barra de Paginación para Matriz
    let paginationHtml = '';
    if (totalPages > 1) {
      paginationHtml = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding:8px 12px;background:var(--bg2);border-radius:8px;font-size:0.82rem;flex-wrap:wrap;gap:8px">
          <div style="color:var(--text-muted)">
            Mostrando <strong>${startIdx + 1}–${Math.min(startIdx + pag.perPage, totalFiltered)}</strong> de <strong>${totalFiltered}</strong> aprendices
          </div>
          <div style="display:flex;gap:6px;align-items:center">
            <button class="btn btn-secondary btn-sm" onclick="cambiarPaginaFase('${escapeHtml(fase).replace(/'/g, "\\'")}', ${pag.page - 1})" ${pag.page <= 1 ? 'disabled style="opacity:0.5;cursor:not-allowed"' : ''}>
              ◀ Anterior
            </button>
            <span style="padding:0 6px;color:var(--text);font-weight:600">Pág. ${pag.page} de ${totalPages}</span>
            <button class="btn btn-secondary btn-sm" onclick="cambiarPaginaFase('${escapeHtml(fase).replace(/'/g, "\\'")}', ${pag.page + 1})" ${pag.page >= totalPages ? 'disabled style="opacity:0.5;cursor:not-allowed"' : ''}>
              Siguiente ▶
            </button>
          </div>
        </div>
      `;
    }

    // Preservar si estaba abierto o abrir por defecto si se filtró por una fase
    const isSingleFase = Boolean(document.getElementById('filtroFase')?.value);
    const wasOpen = openAccordions[id] || isSingleFase;
    const displayStyle = wasOpen ? 'block' : 'none';

    // Preservar la pestaña activa ('matriz' o 'lista') en esta fase
    const activeView = activeFaseViews[id] || 'matriz';
    const isMatriz = activeView === 'matriz';

    return `<div style="margin-bottom:16px;border:1px solid rgba(57,169,0,0.18);border-radius:10px;overflow:hidden;background:var(--bg)">
      <div style="padding:14px 18px;background:rgba(57,169,0,0.06);display:flex;align-items:center;justify-content:space-between;gap:12px;cursor:pointer" onclick="toggleAcc('${id}')">
        <div style="display:flex;align-items:center;gap:10px">
          <span style="font-size:1.15rem">📁</span>
          <strong style="color:var(--text);font-size:0.95rem">${escapeHtml(fase)}</strong>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <span class="badge badge-green">✓ ${totalAprob}</span>
          <span class="badge badge-orange">⏳ ${totalPend}</span>
          <span style="color:#7a8fa6;font-size:0.8rem">▼</span>
        </div>
      </div>

      <div id="${id}" style="display:${displayStyle};padding:16px">
        <!-- Switch de Vistas -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px">
          <div style="font-size:0.82rem;color:var(--text-muted)">
            Mostrando <strong>${totalFiltered}</strong> aprendices activos en esta fase.
          </div>
          <div style="display:inline-flex;background:var(--bg2);padding:3px;border-radius:8px;border:1px solid var(--card-border)">
            <button class="btn btn-sm" id="btnMat_${id}" onclick="switchFaseView('${id}', 'matriz')" style="padding:4px 12px;font-size:0.75rem;background:${isMatriz ? 'var(--primary)' : 'transparent'};color:${isMatriz ? '#000' : 'var(--text-muted)'};border:none">
              🟩 Matriz de Actividades (Heatmap)
            </button>
            <button class="btn btn-sm" id="btnList_${id}" onclick="switchFaseView('${id}', 'lista')" style="padding:4px 12px;font-size:0.75rem;background:${!isMatriz ? 'var(--primary)' : 'transparent'};color:${!isMatriz ? '#000' : 'var(--text-muted)'};border:none">
              📋 Lista Detallada
            </button>
          </div>
        </div>

        <!-- Vista Matriz -->
        <div id="${idMatrix}" style="display:${isMatriz ? 'block' : 'none'}">
          ${matrixHtml}
          ${paginationHtml}
        </div>

        <!-- Vista Lista Detallada -->
        <div id="${idList}" style="display:${!isMatriz ? 'block' : 'none'}">
          ${listHtml}
        </div>
      </div>
    </div>`;
  }).join('') || '<p class="text-muted" style="padding:16px">No hay registros que coincidan con la búsqueda.</p>';

  container.innerHTML = html;
}

function cambiarPaginaFase(fase, nuevaPag) {
  if (detallePagination[fase]) {
    detallePagination[fase].page = nuevaPag;
    renderDetalleConFiltros();
  }
}

let activeFaseViews = {}; // { [acc_id]: 'matriz' | 'lista' }

function switchFaseView(baseId, view) {
  activeFaseViews[baseId] = view;
  const matEl = document.getElementById('mat_' + baseId);
  const listEl = document.getElementById('list_' + baseId);
  const btnMat = document.getElementById('btnMat_' + baseId);
  const btnList = document.getElementById('btnList_' + baseId);

  if (view === 'matriz') {
    if (matEl) matEl.style.display = 'block';
    if (listEl) listEl.style.display = 'none';
    if (btnMat) { btnMat.style.background = 'var(--primary)'; btnMat.style.color = '#000'; }
    if (btnList) { btnList.style.background = 'transparent'; btnList.style.color = 'var(--text-muted)'; }
  } else {
    if (matEl) matEl.style.display = 'none';
    if (listEl) listEl.style.display = 'block';
    if (btnList) { btnList.style.background = 'var(--primary)'; btnList.style.color = '#000'; }
    if (btnMat) { btnMat.style.background = 'transparent'; btnMat.style.color = 'var(--text-muted)'; }
  }
}

let openAccordions = {};

function toggleAcc(id){
  const el = document.getElementById(id);
  if (!el) return;
  const isCurrentlyOpen = el.style.display !== 'none';
  el.style.display = isCurrentlyOpen ? 'none' : 'block';
  openAccordions[id] = !isCurrentlyOpen;
}

function getFilteredDetalleRows() {
  if (!rawDetalleRows || !rawDetalleRows.length) return [];
  const query = (document.getElementById('filtroBuscarAprendiz')?.value || '').trim().toLowerCase();
  const filtroEstado = document.getElementById('filtroEstadoFase')?.value || '';
  const filtroComp = (document.getElementById('filtroCompetencia')?.value || '').trim().toLowerCase();
  const filtroRap = (document.getElementById('filtroResultado')?.value || '').trim().toLowerCase();
  const idFaseSelected = document.getElementById('filtroFase')?.value || '';

  let rows = rawDetalleRows;
  if (idFaseSelected) {
    rows = rows.filter(r => String(r.id_fase) === String(idFaseSelected));
  }
  if (filtroComp) {
    rows = rows.filter(r => (r.competencia || '').toLowerCase().includes(filtroComp));
  }
  if (filtroRap) {
    rows = rows.filter(r => (r.resultado_aprendizaje || '').toLowerCase().includes(filtroRap));
  }
  if (query) {
    rows = rows.filter(r => (r.aprendiz || '').toLowerCase().includes(query) || (r.documento || '').toLowerCase().includes(query));
  }
  if (filtroEstado === 'pendientes') {
    rows = rows.filter(r => r.estado_en_fase !== 'Aprobado');
  } else if (filtroEstado === 'aprobados') {
    rows = rows.filter(r => r.estado_en_fase === 'Aprobado');
  }
  return rows;
}

function exportarDetalleExcel() {
  const rows = getFilteredDetalleRows();
  if (!rows.length) {
    alert('No hay datos disponibles para exportar con los filtros seleccionados.');
    return;
  }

  const selProg = document.getElementById('filtroProgramaGlobal');
  const progNombreCompleto = selProg && selProg.value ? selProg.options[selProg.selectedIndex]?.text : 'Todos los Programas de Formación';
  const cleanProgName = progNombreCompleto.replace(/[^a-zA-Z0-9_\-]/g, '_').substring(0, 40);

  const res = currentResumenData || {};
  const cob = res.cobertura || {};
  const fases = currentCumplimientoFases || [];

  const totalMatriculados = res.total_matriculados ?? '—';
  const totalActivos = res.total_activos ?? '—';
  const totalDesertados = res.total_desertados ?? '—';
  const tasaDesercion = (res.tasa_desercion ?? 0) + '%';
  const pctGlobal = document.getElementById('pctGlobalFases')?.textContent || '—';
  const fechaGeneracion = new Date().toLocaleDateString('es-CO', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });

  // Escape XML
  const escapeXML = (val) => {
    if (val === null || val === undefined) return '';
    return String(val)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&apos;');
  };

  let xml = '<' + '?xml version="1.0" encoding="UTF-8"?' + '>\n'
    + '<' + '?mso-application progid="Excel.Sheet"?' + '>\n'
    + '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"\n'
    + ' xmlns:o="urn:schemas-microsoft-com:office:office"\n'
    + ' xmlns:x="urn:schemas-microsoft-com:office:excel"\n'
    + ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"\n'
    + ' xmlns:html="http://www.w3.org/TR/REC-html40">\n';
  xml += `<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Title>Informe de Fases SENA</Title>
  <Author>Sistema SENA Juicios</Author>
  <Created>${new Date().toISOString()}</Created>
 </DocumentProperties>
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Center"/>
   <Borders/>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <!-- Estilos de Título e Institucionales -->
  <Style ss:ID="sMainTitle">
   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
   <Font ss:FontName="Calibri" ss:Size="16" ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#39A900" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="sSubtitle">
   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Italic="1" ss:Color="#475569"/>
   <Interior ss:Color="#F1F5F9" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="sSectionHeader">
   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
   <Font ss:FontName="Calibri" ss:Size="13" ss:Bold="1" ss:Color="#0F172A"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#39A900"/>
   </Borders>
  </Style>
  <!-- Tarjetas KPI / Resumen -->
  <Style ss:ID="sKpiLabel">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Font ss:FontName="Calibri" ss:Size="9" ss:Bold="1" ss:Color="#64748B"/>
   <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
   <Borders>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
   </Borders>
  </Style>
  <Style ss:ID="sKpiValue">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Font ss:FontName="Calibri" ss:Size="14" ss:Bold="1" ss:Color="#0F172A"/>
   <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
   <Borders>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
   </Borders>
  </Style>
  <Style ss:ID="sKpiValueGreen">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Font ss:FontName="Calibri" ss:Size="14" ss:Bold="1" ss:Color="#15803D"/>
   <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
   <Borders>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
   </Borders>
  </Style>
  <Style ss:ID="sKpiValueRed">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Font ss:FontName="Calibri" ss:Size="14" ss:Bold="1" ss:Color="#B91C1C"/>
   <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
   <Borders>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
   </Borders>
  </Style>
  <!-- Encabezados de Tablas -->
  <Style ss:ID="sTh">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#00324D" ss:Pattern="Solid"/>
   <Borders>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#002133"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#002133"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#002133"/>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#002133"/>
   </Borders>
  </Style>
  <!-- Celdas de Datos y Juicios -->
  <Style ss:ID="sTd">
   <Alignment ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
   </Borders>
  </Style>
  <Style ss:ID="sTdCenter">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
   </Borders>
  </Style>
  <Style ss:ID="sAprobado">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#15803D"/>
   <Interior ss:Color="#DCFCE7" ss:Pattern="Solid"/>
   <Borders>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BBF7D0"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BBF7D0"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BBF7D0"/>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BBF7D0"/>
   </Borders>
  </Style>
  <Style ss:ID="sPendiente">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#854D0E"/>
   <Interior ss:Color="#FEF9C3" ss:Pattern="Solid"/>
   <Borders>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FEF08A"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FEF08A"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FEF08A"/>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FEF08A"/>
   </Borders>
  </Style>
  <Style ss:ID="sNoAprobado">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#B91C1C"/>
   <Interior ss:Color="#FEE2E2" ss:Pattern="Solid"/>
   <Borders>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FECACA"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FECACA"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FECACA"/>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FECACA"/>
   </Borders>
  </Style>
 </Styles>

 <!-- HOJA 1: DETALLE DE EVALUACIONES -->
 <Worksheet ss:Name="Detalle de Evaluaciones">
  <Table ss:DefaultRowHeight="20">
   <Column ss:Width="100"/>
   <Column ss:Width="190"/>
   <Column ss:Width="110"/>
   <Column ss:Width="110"/>
   <Column ss:Width="230"/>
   <Column ss:Width="280"/>
   <Column ss:Width="300"/>
   <Column ss:Width="120"/>

   <!-- Encabezado Institucional -->
   <Row ss:Height="30">
    <Cell ss:MergeAcross="7" ss:StyleID="sMainTitle"><Data ss:Type="String">  SENA — INFORME DETALLADO DE AVANCE POR FASES</Data></Cell>
   </Row>
   <Row ss:Height="22">
    <Cell ss:MergeAcross="7" ss:StyleID="sSubtitle"><Data ss:Type="String">  Programa / Ficha: ${escapeXML(progNombreCompleto)}  |  Fecha: ${escapeXML(fechaGeneracion)}</Data></Cell>
   </Row>
   <Row ss:Height="12"></Row>

   <!-- Encabezados de Columnas -->
   <Row ss:Height="26">
    <Cell ss:StyleID="sTh"><Data ss:Type="String">Documento</Data></Cell>
    <Cell ss:StyleID="sTh"><Data ss:Type="String">Aprendiz</Data></Cell>
    <Cell ss:StyleID="sTh"><Data ss:Type="String">Estado Aprendiz</Data></Cell>
    <Cell ss:StyleID="sTh"><Data ss:Type="String">Fase</Data></Cell>
    <Cell ss:StyleID="sTh"><Data ss:Type="String">Actividad</Data></Cell>
    <Cell ss:StyleID="sTh"><Data ss:Type="String">Competencia</Data></Cell>
    <Cell ss:StyleID="sTh"><Data ss:Type="String">Resultado de Aprendizaje</Data></Cell>
    <Cell ss:StyleID="sTh"><Data ss:Type="String">Juicio Evaluativo</Data></Cell>
   </Row>
`;

  rows.forEach(r => {
    const estado = r.estado_en_fase || 'Por evaluar';
    const juicioStyle = estado === 'Aprobado' ? 'sAprobado' : (estado === 'No aprobado' ? 'sNoAprobado' : 'sPendiente');

    xml += `   <Row ss:Height="22">
    <Cell ss:StyleID="sTdCenter"><Data ss:Type="String">${escapeXML(r.documento || '')}</Data></Cell>
    <Cell ss:StyleID="sTd"><Data ss:Type="String">${escapeXML(r.aprendiz || '')}</Data></Cell>
    <Cell ss:StyleID="sTdCenter"><Data ss:Type="String">${escapeXML(r.estado_aprendiz || 'En formación')}</Data></Cell>
    <Cell ss:StyleID="sTdCenter"><Data ss:Type="String">${escapeXML(r.nombre_fase || '')}</Data></Cell>
    <Cell ss:StyleID="sTd"><Data ss:Type="String">${escapeXML(r.actividad || '')}</Data></Cell>
    <Cell ss:StyleID="sTd"><Data ss:Type="String">${escapeXML(r.competencia || '')}</Data></Cell>
    <Cell ss:StyleID="sTd"><Data ss:Type="String">${escapeXML(r.resultado_aprendizaje || '')}</Data></Cell>
    <Cell ss:StyleID="${juicioStyle}"><Data ss:Type="String">${escapeXML(estado)}</Data></Cell>
   </Row>\n`;
  });

  xml += `  </Table>
 </Worksheet>

 <!-- HOJA 2: RESUMEN EJECUTIVO Y FASES -->
 <Worksheet ss:Name="Resumen Ejecutivo">
  <Table ss:DefaultRowHeight="20">
   <Column ss:Width="40"/>
   <Column ss:Width="160"/>
   <Column ss:Width="130"/>
   <Column ss:Width="130"/>
   <Column ss:Width="130"/>
   <Column ss:Width="130"/>
   <Column ss:Width="130"/>

   <!-- Título -->
   <Row ss:Height="30">
    <Cell ss:MergeAcross="6" ss:StyleID="sMainTitle"><Data ss:Type="String">  SENA — RESUMEN EJECUTIVO DE RENDIMIENTO</Data></Cell>
   </Row>
   <Row ss:Height="22">
    <Cell ss:MergeAcross="6" ss:StyleID="sSubtitle"><Data ss:Type="String">  Programa / Ficha: ${escapeXML(progNombreCompleto)}  |  Generado: ${escapeXML(fechaGeneracion)}</Data></Cell>
   </Row>
   <Row ss:Height="14"></Row>

   <!-- Fila 1 KPIs: Labels -->
   <Row ss:Height="20">
    <Cell ss:StyleID="sKpiLabel"><Data ss:Type="String">INICIARON</Data></Cell>
    <Cell ss:StyleID="sKpiLabel"><Data ss:Type="String">ACTIVOS</Data></Cell>
    <Cell ss:StyleID="sKpiLabel"><Data ss:Type="String">DESERTADOS</Data></Cell>
    <Cell ss:StyleID="sKpiLabel"><Data ss:Type="String">TASA DESERCIÓN</Data></Cell>
    <Cell ss:StyleID="sKpiLabel"><Data ss:Type="String">CUMPLIMIENTO GLOBAL</Data></Cell>
   </Row>
   <!-- Fila 2 KPIs: Values -->
   <Row ss:Height="28">
    <Cell ss:StyleID="sKpiValue"><Data ss:Type="String">${escapeXML(totalMatriculados)}</Data></Cell>
    <Cell ss:StyleID="sKpiValueGreen"><Data ss:Type="String">${escapeXML(totalActivos)}</Data></Cell>
    <Cell ss:StyleID="sKpiValueRed"><Data ss:Type="String">${escapeXML(totalDesertados)}</Data></Cell>
    <Cell ss:StyleID="sKpiValue"><Data ss:Type="String">${escapeXML(tasaDesercion)}</Data></Cell>
    <Cell ss:StyleID="sKpiValueGreen"><Data ss:Type="String">${escapeXML(pctGlobal)}</Data></Cell>
   </Row>
   <Row ss:Height="18"></Row>

   <!-- Título Sección Fases -->
   <Row ss:Height="24">
    <Cell ss:MergeAcross="6" ss:StyleID="sSectionHeader"><Data ss:Type="String">Avance por Fases del Proyecto Formativo</Data></Cell>
   </Row>
   <Row ss:Height="26">
    <Cell ss:StyleID="sTh"><Data ss:Type="String">N°</Data></Cell>
    <Cell ss:StyleID="sTh"><Data ss:Type="String">Fase del Proyecto</Data></Cell>
    <Cell ss:StyleID="sTh"><Data ss:Type="String">Resultados Proyecto</Data></Cell>
    <Cell ss:StyleID="sTh"><Data ss:Type="String">Aprendices Activos</Data></Cell>
    <Cell ss:StyleID="sTh"><Data ss:Type="String">Pares Aprobados</Data></Cell>
    <Cell ss:StyleID="sTh"><Data ss:Type="String">Pares Pendientes</Data></Cell>
    <Cell ss:StyleID="sTh"><Data ss:Type="String">% Cumplimiento</Data></Cell>
   </Row>
`;

  fases.forEach(f => {
    const pct = parseFloat(f.porcentaje_cumplimiento_fase) || 0;
    const badgeStyle = pct >= 80 ? 'sAprobado' : (pct >= 50 ? 'sPendiente' : 'sNoAprobado');
    xml += `   <Row ss:Height="22">
    <Cell ss:StyleID="sTdCenter"><Data ss:Type="Number">${escapeXML(f.orden || 0)}</Data></Cell>
    <Cell ss:StyleID="sTd"><Data ss:Type="String">${escapeXML(f.nombre_fase || '')}</Data></Cell>
    <Cell ss:StyleID="sTdCenter"><Data ss:Type="Number">${escapeXML(f.total_resultados_fase || 0)}</Data></Cell>
    <Cell ss:StyleID="sTdCenter"><Data ss:Type="Number">${escapeXML(f.total_aprendices || 0)}</Data></Cell>
    <Cell ss:StyleID="sTdCenter"><Data ss:Type="Number">${escapeXML(f.pares_aprobados || 0)}</Data></Cell>
    <Cell ss:StyleID="sTdCenter"><Data ss:Type="Number">${escapeXML(f.pares_pendientes || 0)}</Data></Cell>
    <Cell ss:StyleID="${badgeStyle}"><Data ss:Type="String">${pct}%</Data></Cell>
   </Row>\n`;
  });

  xml += `  </Table>
 </Worksheet>
</Workbook>`;

  const blob = new Blob([xml], { type: 'application/vnd.ms-excel;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.setAttribute('href', url);
  link.setAttribute('download', `SENA_Informe_Fases_${cleanProgName}_${new Date().toISOString().slice(0,10)}.xls`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

function imprimirInformeEjecutivo() {
  const selProg = document.getElementById('filtroProgramaGlobal');
  const progNombreCompleto = selProg && selProg.value ? selProg.options[selProg.selectedIndex]?.text : 'Todos los Programas de Formación';
  const idFichaActual = selProg?.value || 'Consolidado';

  const res = currentResumenData || {};
  const cob = res.cobertura || {};
  const fases = currentCumplimientoFases || [];
  const rows = getFilteredDetalleRows();

  const totalMatriculados = res.total_matriculados ?? '—';
  const totalActivos = res.total_activos ?? '—';
  const totalDesertados = res.total_desertados ?? '—';
  const tasaDesercion = (res.tasa_desercion ?? 0) + '%';
  const coberturaText = (cob.total_proyecto > 0) ? `${cob.porcentaje}% (${cob.evaluados}/${cob.total_proyecto} resultados)` : '—';
  const pctGlobal = document.getElementById('pctGlobalFases')?.textContent || '—';

  // Resumen de aprendices pendientes únicos
  const aprendicesConPendientes = {};
  rows.forEach(r => {
    if (r.estado_en_fase !== 'Aprobado') {
      const doc = r.documento || r.aprendiz;
      if (!aprendicesConPendientes[doc]) {
        aprendicesConPendientes[doc] = {
          documento: r.documento || '—',
          aprendiz: r.aprendiz || '—',
          fases: new Set(),
          pendientes: 0
        };
      }
      aprendicesConPendientes[doc].pendientes++;
      if (r.nombre_fase) aprendicesConPendientes[doc].fases.add(r.nombre_fase);
    }
  });
  const listaPendientes = Object.values(aprendicesConPendientes);

  const printWindow = window.open('', '_blank', 'width=1000,height=800');
  if (!printWindow) {
    alert('Por favor permite ventanas emergentes para generar el informe imprimible.');
    return;
  }

  const printHtml = `<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Informe Ejecutivo de Fases - SENA</title>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      color: #1e293b;
      background: #fff;
      margin: 0;
      padding: 30px;
      font-size: 13px;
      line-height: 1.45;
    }
    .header-box {
      border-bottom: 2px solid #39A900;
      padding-bottom: 16px;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
    }
    .title {
      font-size: 20px;
      font-weight: 800;
      color: #0f172a;
      margin: 0 0 4px 0;
    }
    .subtitle {
      font-size: 13px;
      color: #64748b;
      margin: 0;
    }
    .badge-inst {
      background: #39A900;
      color: #fff;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 4px;
      font-size: 12px;
    }
    .grid-stats {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 12px;
      margin-bottom: 26px;
    }
    .stat-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 10px 14px;
      text-align: center;
    }
    .stat-val {
      font-size: 18px;
      font-weight: 800;
      color: #0f172a;
    }
    .stat-lbl {
      font-size: 11px;
      color: #64748b;
      text-transform: uppercase;
      font-weight: 600;
      margin-top: 2px;
    }
    .section-title {
      font-size: 15px;
      font-weight: 700;
      color: #0f172a;
      border-left: 4px solid #39A900;
      padding-left: 10px;
      margin: 24px 0 12px 0;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 24px;
      font-size: 12px;
    }
    th, td {
      border: 1px solid #cbd5e1;
      padding: 8px 10px;
      text-align: left;
    }
    th {
      background: #f1f5f9;
      font-weight: 700;
      color: #334155;
    }
    .badge {
      display: inline-block;
      padding: 2px 7px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 600;
    }
    .badge-green { background: #dcfce7; color: #15803d; }
    .badge-yellow { background: #fef9c3; color: #854d0e; }
    .badge-red { background: #fee2e2; color: #b91c1c; }
    .footer-note {
      font-size: 11px;
      color: #94a3b8;
      text-align: center;
      margin-top: 30px;
      border-top: 1px solid #e2e8f0;
      padding-top: 14px;
    }
    @media print {
      body { padding: 0; }
      .no-print { display: none; }
      button { display: none; }
      @page { margin: 15mm; size: letter; }
    }
  </style>
</head>
<body>
  <div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: flex-end; gap: 10px;">
    <button onclick="window.print()" style="background: #39A900; color: #fff; border: none; padding: 8px 18px; border-radius: 6px; font-weight: 700; cursor: pointer;">🖨️ Imprimir / Guardar como PDF</button>
    <button onclick="window.close()" style="background: #e2e8f0; color: #334155; border: none; padding: 8px 14px; border-radius: 6px; font-weight: 600; cursor: pointer;">Cerrar</button>
  </div>

  <div class="header-box">
    <div>
      <h1 class="title">INFORME EJECUTIVO DE AVANCE POR FASES</h1>
      <p class="subtitle"><strong>Programa / Ficha:</strong> ${escapeHtml(progNombreCompleto)}</p>
      <p class="subtitle" style="margin-top:3px"><strong>Fecha de emisión:</strong> ${new Date().toLocaleDateString('es-CO', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>
    </div>
    <div style="text-align: right;">
      <span class="badge-inst">SENA — GESTIÓN ACADÉMICA</span>
      <div style="font-size:11px;color:#64748b;margin-top:5px">Sistema de Seguimiento Curricular</div>
    </div>
  </div>

  <div class="grid-stats">
    <div class="stat-card">
      <div class="stat-val">${totalMatriculados}</div>
      <div class="stat-lbl">Iniciaron</div>
    </div>
    <div class="stat-card">
      <div class="stat-val" style="color:#15803d">${totalActivos}</div>
      <div class="stat-lbl">Activos</div>
    </div>
    <div class="stat-card">
      <div class="stat-val" style="color:#b91c1c">${totalDesertados}</div>
      <div class="stat-lbl">Desertados / Traslados</div>
    </div>
    <div class="stat-card">
      <div class="stat-val">${tasaDesercion}</div>
      <div class="stat-lbl">Tasa de Deserción</div>
    </div>
    <div class="stat-card">
      <div class="stat-val" style="color:#15803d">${pctGlobal}</div>
      <div class="stat-lbl">Cumplimiento Global</div>
    </div>
  </div>

  <div class="section-title">1. Resumen de Cumplimiento por Fases del Proyecto Formativo</div>
  <table>
    <thead>
      <tr>
        <th style="width:40px;text-align:center">N°</th>
        <th>Fase del Proyecto</th>
        <th style="text-align:center">Resultados Proyecto</th>
        <th style="text-align:center">Aprendices Activos</th>
        <th style="text-align:center">Pares Aprobados</th>
        <th style="text-align:center">Pares Pendientes</th>
        <th style="text-align:center;width:120px">% Cumplimiento</th>
      </tr>
    </thead>
    <tbody>
      ${fases.length === 0 ? '<tr><td colspan="7" style="text-align:center;color:#64748b">No se encontraron fases registradas.</td></tr>' :
        fases.map(f => {
          const pct = parseFloat(f.porcentaje_cumplimiento_fase) || 0;
          const badgeClass = pct >= 80 ? 'badge-green' : pct >= 50 ? 'badge-yellow' : 'badge-red';
          return `<tr>
            <td style="text-align:center;font-weight:700">${escapeHtml(f.orden || '')}</td>
            <td><strong>${escapeHtml(f.nombre_fase || '')}</strong></td>
            <td style="text-align:center">${escapeHtml(f.total_resultados_fase || 0)}</td>
            <td style="text-align:center">${escapeHtml(f.total_aprendices || 0)}</td>
            <td style="text-align:center;color:#15803d;font-weight:600">${escapeHtml(f.pares_aprobados || 0)}</td>
            <td style="text-align:center;color:#b91c1c;font-weight:600">${escapeHtml(f.pares_pendientes || 0)}</td>
            <td style="text-align:center"><span class="badge ${badgeClass}">${pct}%</span></td>
          </tr>`;
        }).join('')
      }
    </tbody>
  </table>

  <div class="section-title">2. Consolidado de Aprendices con Evaluaciones Pendientes (${listaPendientes.length})</div>
  <table>
    <thead>
      <tr>
        <th style="width:40px;text-align:center">N°</th>
        <th>Documento</th>
        <th>Nombre del Aprendiz</th>
        <th>Fases Involucradas</th>
        <th style="text-align:center;width:130px">Resultados Pendientes</th>
      </tr>
    </thead>
    <tbody>
      ${listaPendientes.length === 0 ? '<tr><td colspan="5" style="text-align:center;color:#15803d;font-weight:600">✓ ¡Excelente! No hay aprendices con juicios pendientes en los filtros seleccionados.</td></tr>' :
        listaPendientes.slice(0, 100).map((a, idx) => `<tr>
          <td style="text-align:center">${idx + 1}</td>
          <td>${escapeHtml(a.documento)}</td>
          <td><strong>${escapeHtml(a.aprendiz)}</strong></td>
          <td style="font-size:11px;color:#475569">${Array.from(a.fases).map(f => escapeHtml(f)).join(', ') || '—'}</td>
          <td style="text-align:center"><span class="badge badge-yellow">${a.pendientes} pendiente${a.pendientes > 1 ? 's' : ''}</span></td>
        </tr>`).join('')
      }
    </tbody>
  </table>
  ${listaPendientes.length > 100 ? `<p style="font-size:11px;color:#64748b;text-align:right">* Mostrando los primeros 100 aprendices de ${listaPendientes.length}. Exporte el archivo CSV para ver la totalidad de registros.</p>` : ''}

  <div class="footer-note">
    Documento generado automáticamente por el Sistema de Juicios Evaluativos SENA. Información obtenida directamente de la base de datos de gestión curricular.
  </div>
</body>
</html>`;

  printWindow.document.open();
  printWindow.document.write(printHtml);
  printWindow.document.close();
}

actualizarDashboardFases();
</script>
<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
