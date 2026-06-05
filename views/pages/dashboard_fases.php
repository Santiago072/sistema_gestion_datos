<?php 
require_once __DIR__ . '/../layouts/header.php'; 
require_once __DIR__ . '/../../config/database.php';
$db = getDB();
$programas = $db->query("SELECT id_ficha, nombre FROM programas ORDER BY nombre")->fetchAll();
?>

<!-- Controles de Filtro Global -->
<div class="card mb-24" style="background:var(--card-bg);border:1px solid rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:space-between">
  <div style="display:flex;align-items:center;gap:12px;width:100%">
    <label style="font-weight:600;white-space:nowrap;color:var(--text-muted)">Filtrar por Programa:</label>
    <select id="filtroProgramaGlobal" onchange="actualizarDashboardFases()" style="flex:1;max-width:500px;padding:10px 14px;border-radius:var(--radius-sm);background:rgba(0,0,0,0.2);border:1px solid rgba(255,255,255,0.1);color:#fff">
      <option value="">-- Todos los Programas --</option>
      <?php foreach($programas as $p): ?>
        <option value="<?= htmlspecialchars($p['id_ficha']) ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['id_ficha']) ?>)</option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-secondary btn-sm" onclick="document.getElementById('filtroProgramaGlobal').value=''; actualizarDashboardFases()">Limpiar</button>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     LÍNEA DE TIEMPO MEJORADA — Proyecto Formativo vs Juicios
     ══════════════════════════════════════════════════════════ -->
<div class="card fade-in mb-24" style="padding-bottom:40px">
  <div class="section-title" style="margin-bottom:8px">🗺 Línea de Tiempo — Avance por Fase</div>
  <p style="color:var(--text-muted);font-size:0.83rem;margin-bottom:32px">
    Compara los resultados de aprendizaje definidos en el <strong>Proyecto Formativo (PDF)</strong> con los juicios evaluativos registrados en el <strong>Excel</strong>. Cada barra de progreso muestra cuántos pares (aprendiz × resultado) ya están aprobados.
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
  <div class="section-header mb-16">
    <div class="section-title">📋 Detalle de Aprendices por Fase</div>
    <select id="filtroFase" onchange="cargarDetalle(this.value)">
      <option value="">Todas las fases</option>
    </select>
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

// Función badgeJuicio removida (ahora global en footer.php)

function actualizarDashboardFases() {
  const idFicha = document.getElementById('filtroProgramaGlobal').value;
  const urlCumplimiento = '/sistema_gestion_datos/controllers/cumplimiento_fases.php' + (idFicha ? `?id_ficha=${idFicha}` : '');

  fetch(urlCumplimiento).then(r=>r.json()).then(fases=>{

    // ── LÍNEA DE TIEMPO MEJORADA ──────────────────────────────
    const timeline = document.getElementById('phaseTimeline');

    if (!fases.length) {
      timeline.innerHTML = '<p class="text-muted">Sin fases configuradas o sin datos para el programa seleccionado. Suba el PDF del Proyecto Formativo.</p>';
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
                <div class="fase-stat-lbl">Aprendices</div>
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
    sel.innerHTML = '<option value="">Todas las fases</option>';
    fases.forEach(f => {
      const o = document.createElement('option');
      o.value = f.id_fase;
      o.textContent = f.nombre_fase;
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

    cargarDetalle(sel.value);
  });
}

function cargarDetalle(idFase){
  const idFicha = document.getElementById('filtroProgramaGlobal').value;
  let url = '/sistema_gestion_datos/controllers/detalle_fases.php?';
  const params = new URLSearchParams();
  if (idFase)  params.append('id_fase',  idFase);
  if (idFicha) params.append('id_ficha', idFicha);
  url += params.toString();

  fetch(url).then(r=>r.json()).then(rows=>{
    const byFase = {};
    rows.forEach(r=>{
      const k = r.nombre_fase||'Sin fase';
      if(!byFase[k]) byFase[k]=[];
      byFase[k].push(r);
    });
    const container = document.getElementById('accordionFases');
    container.innerHTML = Object.entries(byFase).map(([fase,items])=>{
      const aprobados  = items.filter(x=>x.estado_en_fase==='Aprobado').length;
      const pendientes = items.filter(x=>x.estado_en_fase!=='Aprobado').length;
      const id = 'acc_'+fase.replace(/\s/g,'_');
      return `<div style="margin-bottom:12px;border:1px solid rgba(57,169,0,0.15);border-radius:10px;overflow:hidden">
        <div style="padding:14px 16px;background:rgba(57,169,0,0.07);display:flex;align-items:center;gap:12px;cursor:pointer" onclick="toggleAcc('${id}')">
          <strong style="flex:1">📁 ${fase}</strong>
          <span class="badge badge-green">✓ ${aprobados}</span>
          <span class="badge badge-orange">⏳ ${pendientes}</span>
          <span style="color:#7a8fa6;font-size:.8rem">▼</span>
        </div>
        <div id="${id}" style="display:none">
          <div class="table-wrap"><table>
            <thead><tr><th>Aprendiz</th><th>Estado</th><th>Competencia</th><th>Resultado (Proyecto)</th><th>Juicio Evaluativo</th></tr></thead>
            <tbody>${items.map(x=>`<tr>
              <td><strong>${x.aprendiz||'—'}</strong></td>
              <td>${x.estado_aprendiz||'—'}</td>
              <td class="text-muted" style="font-size:0.8rem">${x.competencia||'—'}</td>
              <td class="text-muted" style="font-size:0.8rem">${x.resultado_aprendizaje||'—'}</td>
              <td>${badgeJuicio(x.estado_en_fase||'Por evaluar')}</td>
            </tr>`).join('')}</tbody>
          </table></div>
        </div>
      </div>`;
    }).join('') || '<p class="text-muted" style="padding:16px">Sin datos. Configure relaciones en el módulo de Fases del Proyecto.</p>';
  });
}

function toggleAcc(id){ const el=document.getElementById(id); el.style.display=el.style.display==='none'?'block':'none'; }

actualizarDashboardFases();
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
