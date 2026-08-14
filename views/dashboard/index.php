<?php 
require_once dirname(__DIR__, 2) . '/config/url_config.php';
require_once dirname(__DIR__) . '/layouts/header.php'; 
require_once dirname(__DIR__, 2) . '/config/database.php';
$db = getDB();
$programas = $db->query("SELECT id_ficha, nombre FROM programas ORDER BY nombre")->fetchAll();
?>

<!-- Filtro Global -->
<div class="card mb-24 fade-in" style="display:flex;justify-content:space-between;align-items:center;padding:16px 24px;background:linear-gradient(90deg, rgba(57,169,0,0.05), transparent);border-left:4px solid #39A900">
  <div class="section-title mb-0" style="display:flex;align-items:center;gap:12px">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:24px;height:24px;color:#39A900"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
    Dashboard de Seguimiento
  </div>
  <div>
    <select id="filtroProgramaGlobal" onchange="cargarDashboard(); aplicarFiltro(1);" style="padding:8px 12px;border-radius:6px;border:1px solid var(--card-border);background:var(--bg);color:var(--text);min-width:300px;font-size:0.9rem">
      <option value="">Todos los programas</option>
      <?php foreach($programas as $p): ?>
        <option value="<?= htmlspecialchars($p['id_ficha']) ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['id_ficha']) ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="kpi-grid" id="kpiGrid">
  <div class="loading"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></div>
</div>

<!-- Row 1: Aprendices por formación + Comparativa -->
<div class="grid-2 mb-24">
  <div class="card fade-in stagger-1">
    <div class="section-header">
      <div class="section-title"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>Aprendices por Formación</div>
    </div>
    <div class="chart-box mb-16"><canvas id="chartFormacion"></canvas></div>
    <div class="table-wrap"><table id="tablaFormacion"><thead><tr><th>Programa</th><th>Total</th><th>En Formación</th><th>Retirados</th></tr></thead><tbody></tbody></table></div>
  </div>
  <div class="card fade-in stagger-2">
    <div class="section-header">
      <div class="section-title"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>Comparativa de Juicios</div>
    </div>
    <div class="chart-box chart-box-tall"><canvas id="chartComparativa"></canvas></div>
  </div>
</div>

<!-- Filtros avanzados -->
<div class="card fade-in stagger-3 mb-24">
  <div class="section-header">
    <div class="section-title"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>Filtro Avanzado de Juicios</div>
    <a id="btnExportarCSV" href="<?= BASE_URL ?>index.php?module=dashboard&action=filtro_avanzado?format=csv" class="btn btn-secondary btn-sm">⬇ Exportar CSV</a>
  </div>
  <style>
  .modern-search-wrapper { position:relative; width:100%; }
  .modern-search-wrapper svg.search-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:var(--text-dim); pointer-events:none; }
  .modern-search-wrapper input, .modern-search-wrapper select { padding-left:38px !important; width:100%; transition:all 0.2s ease; border:1px solid var(--card-border); background:var(--bg); color:var(--text); border-radius: 6px; padding-top: 8px; padding-bottom: 8px;}
  .modern-search-wrapper input:focus, .modern-search-wrapper select:focus { border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-glow); background:var(--bg); }
  .modern-search-wrapper .spinner { position:absolute; right:12px; top:50%; transform:translateY(-50%); width:18px; height:18px; border:2px solid var(--card-border); border-top-color:var(--primary); border-radius:50%; animation:spin 1s linear infinite; display:none; }
  @keyframes spin { to { transform:translateY(-50%) rotate(360deg); } }
  .filter-chip { display:inline-flex; align-items:center; gap:6px; background:var(--primary-glow); border:1px solid var(--primary-glow); padding:4px 10px; border-radius:16px; font-size:0.8rem; color:var(--primary); }
  .filter-chip button { background:none; border:none; color:inherit; cursor:pointer; padding:0; display:flex; align-items:center; }
  .filter-chip button:hover { color:var(--text); }
  .highlight { background:rgba(255,235,59,0.3); color:inherit; font-weight:bold; padding:0 2px; border-radius:2px; }
  .row-animate { animation: slideIn 0.3s ease-out forwards; opacity: 0; transform: translateY(10px); }
  @keyframes slideIn { to { opacity: 1; transform: translateY(0); } }
  </style>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;background:var(--bg2);padding:20px;border-radius:12px;margin-bottom:16px;border:1px solid var(--card-border);">
    <div class="form-group" style="margin:0"><label style="font-weight:600;margin-bottom:6px;display:block">Documento</label>
      <div class="modern-search-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" id="fDoc" placeholder="Ej: 1020304050">
        <div class="spinner" id="spinDoc"></div>
      </div>
    </div>
    <div class="form-group" style="margin:0"><label style="font-weight:600;margin-bottom:6px;display:block">Competencia</label>
      <div class="modern-search-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" id="fComp" placeholder="Buscar por código o nombre...">
        <div class="spinner" id="spinComp"></div>
      </div>
    </div>
    <div class="form-group" style="margin:0"><label style="font-weight:600;margin-bottom:6px;display:block">Resultado de Aprendizaje</label>
      <div class="modern-search-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" id="fRes" placeholder="Buscar por código o nombre...">
        <div class="spinner" id="spinRes"></div>
      </div>
    </div>
    <div class="form-group" style="margin:0"><label style="font-weight:600;margin-bottom:6px;display:block">Estado</label>
      <div class="modern-search-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        <select id="fEstado" onchange="aplicarFiltro()"><option value="">Todos los estados</option><option>En formación</option><option>Retirado</option><option>Trasladado</option></select>
      </div>
    </div>
    <div class="form-group" style="margin:0"><label style="font-weight:600;margin-bottom:6px;display:block">Tipo de Juicio</label>
      <div class="modern-search-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        <select id="fTipo" onchange="aplicarFiltro()"><option value="">Todos los juicios</option><option>Aprobado</option><option>Por evaluar</option></select>
      </div>
    </div>
    <div class="form-group" style="margin:0;display:flex;flex-direction:column;justify-content:flex-end">
      <div style="display:flex;gap:8px;">
        <button class="btn btn-secondary" onclick="limpiarFiltro()" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Limpiar Todo
        </button>
      </div>
    </div>
  </div>
  <div id="activeFilters" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px"></div>
  <div class="table-wrap">
    <table id="tablaFiltro" class="table-compact">
      <thead><tr><th>Doc.</th><th>Aprendiz</th><th>Estado</th><th>Programa</th><th style="width:20%">Competencia</th><th style="width:20%">Resultado</th><th>Juicio</th><th>Fecha</th><th>Instructor</th></tr></thead>
      <tbody><tr><td colspan="9" class="empty-state">Use los filtros para buscar juicios</td></tr></tbody>
    </table>
  </div>
  <div id="paginationControls" style="display:flex; justify-content:center; align-items:center; margin-top:16px;"></div>
</div>

<!-- Retiros por competencia -->
<div class="card fade-in stagger-4 mb-24">
  <div class="section-header">
    <div class="section-title"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>Retiros por Competencia / Programa</div>
  </div>
  <div class="chart-box chart-box-tall mb-16"><canvas id="chartRetirosCompetencia"></canvas></div>
  <div class="table-wrap"><table id="tablaRetirosCompetencia" class="table-compact">
    <thead><tr><th>Programa</th><th>Competencia (Punto de Salida)</th><th>Fase (SENA)</th><th>Retirados</th><th>Aprendices Retirados</th><th>Funcionario</th></tr></thead>
    <tbody><tr><td colspan="6" class="empty-state">Cargando datos…</td></tr></tbody>
  </table></div>
</div>

<!-- Auditoría funcionarios -->
<div class="card fade-in stagger-6 mb-24">
  <div class="section-title mb-16"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>Auditoría por Funcionario/Instructor</div>
  <div class="table-wrap"><table id="tablaAuditoria">
    <thead><tr><th>Funcionario</th><th>Total Registros</th><th>Aprobados</th><th>Por Evaluar</th><th>No Aprobados</th><th>Primer Registro</th><th>Último Registro</th></tr></thead>
    <tbody></tbody>
  </table></div>
</div>

<script>
const COLORS = { green:'#39A900', orange:'#FF6D00', red:'#EF4444', blue:'#3B82F6', cyan:'#00BCD4', yellow:'#F59E0B', purple:'#8B5CF6' };
let chartFormacion, chartComparativa, chartRetirosComp;

// Funciones badge removidas (ahora globales en footer.php)

// ── Cargar Dashboard ──
function cargarDashboard() {
  const prog = document.getElementById('filtroProgramaGlobal').value;
  const cb = '&_cb=' + new Date().getTime();
  const qs = prog ? '?programa=' + prog + cb : '?_cb=' + new Date().getTime();

  // Reset filtro avanzado when global filter changes
  limpiarFiltro();

  // ── KPIs ──
  fetch('<?= BASE_URL ?>index.php?module=dashboard&action=kpis' + qs)
    .then(r=>r.json()).then(d=>{
      const kpis = [
        {label:'Aprendices Activos',    val:d.total_aprendices_activos,  cls:'green',  icon:'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z'},
        {label:'Juicios Aprobados',     val:d.total_juicios_aprobados,   cls:'green',  icon:'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'},
        {label:'Por Evaluar',           val:d.total_juicios_por_evaluar, cls:'orange', icon:'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'},
        {label:'Programas',             val:d.total_programas,           cls:'purple', icon:'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'},
        {label:'Retirados',             val:d.total_retirados,           cls:'yellow', icon:'M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75'},
        {label:'Trasladados',           val:d.total_trasladados,         cls:'yellow', icon:'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'},
      ];
      document.getElementById('kpiGrid').innerHTML = kpis.map((k,i)=>`
        <div class="kpi-card ${k.cls} fade-in stagger-${i+1}">
          <div class="kpi-icon ${k.cls}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="${k.icon}"/></svg></div>
          <div class="kpi-value" data-target="${k.val}">0</div>
          <div class="kpi-label">${k.label}</div>
        </div>`).join('');
      document.querySelectorAll('.kpi-value[data-target]').forEach(el=>{
        const t=+el.dataset.target, s=performance.now();
        (function step(n){const p=Math.min((n-s)/1200,1),e=1-Math.pow(1-p,3);
          el.textContent=Math.round(t*e).toLocaleString('es-CO');if(p<1)requestAnimationFrame(step);})(s);
      });
    });
    // ── Formación chart ──
    fetch('<?= BASE_URL ?>index.php?module=dashboard&action=aprendices_formacion' + qs)
      .then(r=>r.json()).then(d=>{
        const labels = d.map(x => x.programa);
        const ctx=document.getElementById('chartFormacion').getContext('2d');
        if (chartFormacion) chartFormacion.destroy();
        chartFormacion=new Chart(ctx,{
          type:'bar',
          data:{
            labels,
            datasets:[
              {label:'En Formación',data:d.map(x=>+x.en_formacion),backgroundColor:'rgba(57,169,0,.7)'},
              {label:'Retirados',   data:d.map(x=>+x.retirados),   backgroundColor:'rgba(239,68,68,.7)'},
              {label:'Trasladados', data:d.map(x=>+x.trasladados), backgroundColor:'rgba(0,188,212,.7)'},
            ]
          },
          options:{
            responsive:true,
            maintainAspectRatio:false,
            plugins:{legend:{position:'top'}},
            scales:{
              x:{
                stacked:true,
                ticks: {
                  callback: function(val, index) {
                    let text = this.getLabelForValue(val);
                    return text.length > 25 ? text.substring(0, 25) + '…' : text;
                  }
                }
              },
              y:{stacked:true}
            }
          }
        });
        const tb=document.querySelector('#tablaFormacion tbody');
        tb.innerHTML=d.map(x=>`<tr><td>${x.programa}</td><td><strong>${x.total_aprendices}</strong></td>
          <td><span class="badge badge-cyan">${x.en_formacion}</span></td>
          <td><span class="badge badge-red">${x.retirados}</span></td></tr>`).join('');
      });

    // ── Comparativa chart ──
    fetch('<?= BASE_URL ?>index.php?module=dashboard&action=comparativa_juicios' + qs)
      .then(r=>r.json()).then(d=>{
        const labels=d.map(x=>{const n=x.nombre_completo.split(' ');return n[0]+' '+n[n.length-1];});
        const ctx=document.getElementById('chartComparativa').getContext('2d');
        if (chartComparativa) chartComparativa.destroy();
        chartComparativa=new Chart(ctx,{type:'bar',data:{labels,datasets:[
          {label:'Aprobados',   data:d.map(x=>+x.aprobados),   backgroundColor:'rgba(57,169,0,.8)'},
          {label:'Por Evaluar', data:d.map(x=>+x.por_evaluar), backgroundColor:'rgba(255,109,0,.8)'},
          {label:'No Aprobados',data:d.map(x=>+x.no_aprobados),backgroundColor:'rgba(239,68,68,.8)'},
        ]},options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',plugins:{legend:{position:'top'}}}});
      });

    // ── Retiros por competencia — Curva de Supervivencia ──
    fetch('<?= BASE_URL ?>index.php?module=dashboard&action=retirados_competencia' + qs)
      .then(r=>r.json()).then(data=>{
        const survival = data.survival || [];
        if(!survival.length) {
          document.querySelector('#tablaRetirosCompetencia tbody').innerHTML =
            '<tr><td colspan="6" class="empty-state">Sin datos de retiros</td></tr>';
          return;
        }

        // Build a global X axis: max competencias across all programs
        const maxComps = Math.max(...survival.map(s=>s.puntos.length), 1);
        const xLabels = Array.from({length:maxComps},(_,i)=>'C'+(i+1));

        const lineColors = ['#EF4444','#F59E0B','#3B82F6','#8B5CF6','#00BCD4','#EC4899','#10B981','#F97316'];
        const survivalMeta = {}; // programa -> puntos array for tooltip

        const datasets = survival.map((s,pi) => {
          const points = s.puntos.map(p=>p.aprendices);
          // Pad shorter programs
          while(points.length < maxComps) points.push(null);
          survivalMeta[s.programa] = s.puntos;

          return {
            label: s.programa,
            data: points,
            borderColor: lineColors[pi % lineColors.length],
            backgroundColor: lineColors[pi % lineColors.length] + '18',
            fill: true,
            stepped: 'after',
            pointRadius: 4,
            pointHoverRadius: 7,
            borderWidth: 2.5,
            spanGaps: false
          };
        });

        const progNames = survival.map(s=>s.programa);

        const ctx = document.getElementById('chartRetirosCompetencia').getContext('2d');
        if (chartRetirosComp) chartRetirosComp.destroy();
        chartRetirosComp = new Chart(ctx,{
          type:'line',
          data:{ labels:xLabels, datasets },
          options:{
            responsive:true,
            maintainAspectRatio:false,
            interaction:{ mode:'nearest', intersect:true },
            plugins:{
              legend:{ 
                position:'top', 
                labels:{ boxWidth:14, padding:12 },
                onClick: function(e, legendItem, legend) {
                  Chart.defaults.plugins.legend.onClick.call(this, e, legendItem, legend);
                  const ci = legend.chart;
                  const hiddenPrograms = ci.data.datasets.filter((d, i) => !ci.isDatasetVisible(i)).map(d => d.label);
                  const rows = document.querySelectorAll('#tablaRetirosCompetencia tbody tr:not(.empty-state)');
                  rows.forEach(row => {
                    const progCell = row.querySelector('td:first-child');
                    if (progCell) {
                      const prog = progCell.getAttribute('title');
                      if (hiddenPrograms.includes(prog)) {
                        row.style.display = 'none';
                      } else {
                        row.style.display = '';
                      }
                    }
                  });
                }
              },
              tooltip:{
                yAlign: 'bottom',
                caretPadding: 15,
                callbacks:{
                  title: function(items){
                    const idx = items[0].dataIndex;
                    for(const prog of progNames){
                      const pt = survivalMeta[prog]?.[idx];
                      if(pt) {
                        const compText = pt.competencia?.substring(0,80) || 'C'+(idx+1);
                        return pt.fase ? `[${pt.fase.toUpperCase()}] ${compText}` : compText;
                      }
                    }
                    return 'C'+(idx+1);
                  },
                  label: function(item){
                    return item.dataset.label + ': ' + (item.raw ?? '—') + ' aprendices';
                  },
                  afterBody: function(items){
                    const idx = items[0].dataIndex;
                    let txt = '';
                    items.forEach(item => {
                      const prog = progNames[item.datasetIndex];
                      const pt = survivalMeta[prog]?.[idx];
                      if(!pt || !pt.retirados || !pt.retirados.length) return;
                      const nombres = pt.retirados.map(r => r.nombre + (r.estado === 'Trasladado' ? ' (Trasladado)' : ''));
                      txt += '\n🚪 Salieron: ' + nombres.join(', ');
                      const funcs = (pt.funcionarios||[]).filter(f=>f&&f!=='Sin asignar');
                      if(funcs.length) txt += '\n👨‍🏫 Funcionario: ' + funcs.join(', ');
                    });
                    return txt;
                  }
                }
              }
            },
            scales:{
              x:{ title:{ display:true, text:'Competencias (hover para detalle)', color:'#9CA3AF' } },
              y:{ beginAtZero:true, title:{ display:true, text:'Aprendices', color:'#9CA3AF' }, ticks:{ stepSize:5 } }
            }
          }
        });

        // ── Table: only show competencias where retiros happened ──
        const tb = document.querySelector('#tablaRetirosCompetencia tbody');
        const tableRows = [];
        survival.forEach(s => {
          s.puntos.forEach(pt => {
            if(!pt.retirados || !pt.retirados.length) return;
            const aprs = pt.retirados.map(a => {
              const color = a.estado === 'Trasladado' ? 'badge-cyan' : 'badge-red';
              return `<span class="badge ${color}" style="font-size:.7rem;margin:1px">${a.nombre} (${a.estado})</span>`;
            }).join(' ');
            const funcs = (pt.funcionarios||[]).map(f => {
              if(f==='Sin asignar') return '<span class="badge badge-gray">Sin asignar</span>';
              return '<span class="badge badge-blue">'+f+'</span>';
            }).join(' ');
            tableRows.push(`<tr>
              <td class="text-muted" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${s.programa}">${s.programa}</td>
              <td class="text-muted col-long" title="${pt.competencia}">${pt.competencia.substring(0,50)}${pt.competencia.length>50?'…':''}</td>
              <td><span class="badge" style="background:rgba(139,92,246,0.15);color:#C4B5FD">${pt.fase || 'N/A'}</span></td>
              <td><span class="badge badge-red">${pt.retirados.length}</span></td>
              <td>${aprs}</td>
              <td>${funcs}</td>
            </tr>`);
          });
        });
        tb.innerHTML = tableRows.length ? tableRows.join('') :
          '<tr><td colspan="6" class="empty-state">Sin retiros registrados</td></tr>';
      });


    // ── Auditoría ──
    fetch('<?= BASE_URL ?>index.php?module=dashboard&action=auditoria_funcionarios' + qs)
      .then(r=>r.json()).then(d=>{
        const tb=document.querySelector('#tablaAuditoria tbody');
        tb.innerHTML=d.map(x=>`<tr>
          <td><strong>${x.funcionario}</strong></td>
          <td>${x.total_registros}</td>
          <td><span class="badge badge-green">${x.aprobados}</span></td>
          <td><span class="badge badge-orange">${x.por_evaluar}</span></td>
          <td><span class="badge badge-red">${x.no_aprobados}</span></td>
          <td class="text-muted">${x.primer_registro??'—'}</td>
          <td class="text-muted">${x.ultimo_registro??'—'}</td>
        </tr>`).join('');
      });
}
cargarDashboard();

// ── Filtro avanzado ──
function getHighlightRegex(term) {
  if (!term) return null;
  return new RegExp('('+term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')+')', 'gi');
}

function renderActiveFilters(paramsObj) {
  const container = document.getElementById('activeFilters');
  let chipsHtml = '';
  const labels = {
    documento: 'Doc: ', estado: 'Estado: ', competencia: 'Comp: ', resultado: 'Res: ', tipo_juicio: 'Juicio: '
  };
  for (const [key, val] of Object.entries(paramsObj)) {
    if (val && labels[key]) {
      chipsHtml += `<div class="filter-chip"><span>${labels[key]}<b>${val}</b></span><button type="button" onclick="document.getElementById('f${key==='tipo_juicio'?'Tipo':key.charAt(0).toUpperCase()+key.slice(1)}').value=''; aplicarFiltro(1)"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>`;
    }
  }
  container.innerHTML = chipsHtml;
}

let currentFilterPage = 1;

function aplicarFiltro(page = 1) {
  currentFilterPage = page;
  
  const prog = document.getElementById('filtroProgramaGlobal').value;
  const fDoc = document.getElementById('fDoc').value.trim();
  const fEstado = document.getElementById('fEstado').value;
  const fComp = document.getElementById('fComp').value.trim();
  const fRes = document.getElementById('fRes').value.trim();
  const fTipo = document.getElementById('fTipo').value;

  // Si todos los campos están vacíos, no hacemos una búsqueda masiva.
  if (!prog && !fDoc && !fEstado && !fComp && !fRes && !fTipo) {
    limpiarFiltro();
    return;
  }

  const paramsObj = { documento: fDoc, estado: fEstado, competencia: fComp, resultado: fRes, tipo_juicio: fTipo };
  const params = new URLSearchParams(paramsObj);
  if (prog) params.set('programa', prog);
  
  const exportParams = new URLSearchParams(paramsObj);
  if (prog) exportParams.set('programa', prog);
  
  params.set('page', currentFilterPage);

  // Show chips
  renderActiveFilters(paramsObj);

  // Spinners
  if(fDoc) document.getElementById('spinDoc').style.display='block';
  if(fComp) document.getElementById('spinComp').style.display='block';
  if(fRes) document.getElementById('spinRes').style.display='block';

  const exportBtn = document.getElementById('btnExportarCSV');
  if (exportBtn) {
    exportBtn.href = '<?= BASE_URL ?>index.php?module=dashboard&action=filtro_avanzado?format=csv&' + exportParams.toString();
  }

  fetch('<?= BASE_URL ?>index.php?module=dashboard&action=filtro_avanzado?'+params)
    .then(r=>r.json()).then(response=>{
      document.querySelectorAll('.modern-search-wrapper .spinner').forEach(el=>el.style.display='none');
      
      const d = response.data;
      const pag = response.pagination;
      
      const tb=document.querySelector('#tablaFiltro tbody');
      if(!d || !d.length){
        tb.innerHTML='<tr><td colspan="9" class="empty-state" style="padding:40px 20px"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:48px;height:48px;margin:0 auto 16px;color:#9CA3AF;opacity:0.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>No se encontraron resultados para los filtros aplicados.</td></tr>';
        document.getElementById('paginationControls').innerHTML = '';
        return;
      }

      const rxComp = getHighlightRegex(fComp);
      const rxRes = getHighlightRegex(fRes);

      tb.innerHTML=d.map((x, i)=>{
        let compHTML = x.competencia;
        let resHTML = x.resultado_aprendizaje;
        if (rxComp) compHTML = compHTML.replace(rxComp, '<span class="highlight">$1</span>');
        if (rxRes) resHTML = resHTML.replace(rxRes, '<span class="highlight">$1</span>');

        return `<tr class="row-animate" style="animation-delay:${i*20}ms">
        <td>${x.documento}</td><td><strong>${x.nombre_completo}</strong></td>
        <td>${badgeEstado(x.estado)}</td><td class="text-muted" style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${x.programa}">${x.programa}</td>
        <td class="text-muted col-long">${compHTML}</td><td class="text-muted col-long">${resHTML}</td>
        <td>${badgeJuicio(x.tipo_juicio)}</td><td class="text-muted">${x.fecha_juicio}</td>
        <td class="text-muted">${x.funcionario_registro}</td>
      </tr>`}).join('');
      
      renderPagination(pag);
    }).catch(() => {
      document.querySelectorAll('.modern-search-wrapper .spinner').forEach(el=>el.style.display='none');
    });
}

function renderPagination(pag) {
  const container = document.getElementById('paginationControls');
  if (!container) return;
  if (pag.totalPages <= 1) {
    container.innerHTML = `<span class="text-muted" style="font-size:0.85rem">Mostrando ${pag.total} resultados</span>`;
    return;
  }
  
  let html = `<div style="display:flex; align-items:center; gap:16px;">
    <span class="text-muted" style="font-size:0.85rem">Total: <strong>${pag.total}</strong></span>
    <div style="display:flex; gap:4px;">`;
    
  html += `<button class="btn btn-secondary btn-sm" style="padding:4px 8px" onclick="aplicarFiltro(${pag.page - 1})" ${pag.page === 1 ? 'disabled' : ''}>&laquo; Ant</button>`;
  
  let startPage = Math.max(1, pag.page - 2);
  let endPage = Math.min(pag.totalPages, pag.page + 2);
  
  for (let i = startPage; i <= endPage; i++) {
    const isCurrent = (i === pag.page);
    html += `<button class="btn ${isCurrent ? 'btn-primary' : 'btn-secondary'} btn-sm" style="padding:4px 10px" onclick="aplicarFiltro(${i})">${i}</button>`;
  }
  
  html += `<button class="btn btn-secondary btn-sm" style="padding:4px 8px" onclick="aplicarFiltro(${pag.page + 1})" ${pag.page === pag.totalPages ? 'disabled' : ''}>Sig &raquo;</button>`;
  
  html += `</div></div>`;
  container.innerHTML = html;
}

function limpiarFiltro() {
  document.getElementById('fDoc').value = '';
  document.getElementById('fEstado').value = '';
  document.getElementById('fComp').value = '';
  document.getElementById('fRes').value = '';
  document.getElementById('fTipo').value = '';
  document.getElementById('activeFilters').innerHTML = '';
  document.querySelectorAll('.modern-search-wrapper .spinner').forEach(el=>el.style.display='none');
  document.querySelector('#tablaFiltro tbody').innerHTML = '<tr><td colspan="9" class="empty-state">Use los filtros para buscar juicios</td></tr>';
  document.getElementById('paginationControls').innerHTML = '';
}
function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Debounced version of aplicarFiltro for live search (always starts at page 1)
const aplicarFiltroDebounced = debounce(() => aplicarFiltro(1), 300);

// Attach live-search listeners to the three inputs
['fDoc', 'fComp', 'fRes'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener('keyup', aplicarFiltroDebounced);
    }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
