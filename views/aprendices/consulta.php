<?php 
require_once dirname(__DIR__, 2) . '/config/url_config.php';
require_once dirname(__DIR__) . '/layouts/header.php'; 
require_once dirname(__DIR__, 2) . '/config/database.php';
$db = getDB();
$aprendices = $db->query("SELECT a.documento, CONCAT(a.nombres,' ',a.apellidos) AS nombre, a.estado, a.id_ficha, CONCAT(p.nombre, ' (', p.id_ficha, ')') AS programa
FROM aprendices a JOIN programas p ON a.id_ficha = p.id_ficha ORDER BY a.apellidos")->fetchAll();
$programas = $db->query("SELECT id_ficha, CONCAT(nombre, ' (', id_ficha, ')') AS nombre FROM programas ORDER BY nombre")->fetchAll();
?>

<!-- Search Section -->
<div class="card fade-in mb-24" style="padding:28px 32px;overflow:visible;position:relative;z-index:10">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div class="section-title mb-0">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
      Buscar Aprendiz
    </div>
    <span id="searchCount" style="font-size:.8rem;color:var(--text-muted)"></span>
  </div>
  <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap">
    <div style="flex:0 0 280px">
      <label style="margin-bottom:6px;display:block">Filtrar por Programa</label>
      <select id="filtroPrograma" style="width:100%;padding:12px 14px;font-size:.9rem">
        <option value="">Todos los programas</option>
        <?php foreach($programas as $p): ?>
          <option value="<?= htmlspecialchars($p['id_ficha']) ?>"><?= htmlspecialchars($p['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="flex:1;min-width:300px">
      <label style="margin-bottom:6px;display:block">Buscar por nombre o documento</label>
      <div style="display:flex;gap:12px;align-items:center;">
        <div class="search-box" style="position:relative;flex:1;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:20px;height:20px;color:var(--text-muted);"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
          <input type="text" id="buscarAprendiz" placeholder="Escribe nombre o documento..." autocomplete="off" style="width:100%;font-size:.95rem;padding:12px 14px 12px 40px">
          <div class="autocomplete-list" id="autocompleteList"></div>
        </div>
        <button id="btnLimpiar" style="display:none;padding:11px 20px;border-radius:8px;background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.3);cursor:pointer;font-weight:500;transition:all 0.2s;">
          Limpiar
        </button>
      </div>
    </div>
  </div>
</div>

<div id="perfilSection" style="display:none">
  <!-- Profile Card -->
  <div class="profile-card fade-in mb-24" id="perfilCard"></div>

  <!-- Stats Row -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px" id="miniKpis"></div>

  <!-- Charts Row -->
  <div class="grid-2 mb-24">
    <div class="card fade-in">
      <div class="section-title mb-16">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
        Avance por Competencia
      </div>
      <div id="avanceBarras"></div>
    </div>
    <div class="card fade-in" style="display:flex;flex-direction:column;align-items:center;justify-content:center;">
      <div class="section-title mb-16">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
        Avance Global
      </div>
      <div class="donut-wrap" style="width:220px;height:220px">
        <canvas id="chartGlobal"></canvas>
        <div class="donut-center"><span id="pctGlobal" style="color:#39A900;font-size:2rem">0%</span><small>Completado</small></div>
      </div>
    </div>
  </div>

  <!-- Results Table -->
  <div class="card fade-in mb-24">
    <div class="section-header">
      <div class="section-title">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V19.5a2.25 2.25 0 002.25 2.25h.75"/></svg>
        Seguimiento por Resultado de Aprendizaje
      </div>
      <span id="totalResultados" class="topbar-badge"></span>
    </div>
    <div class="table-wrap"><table id="tablaResultados" class="table-compact">
      <thead><tr><th style="width:25%">Competencia</th><th style="width:25%">Resultado</th><th>Estado</th><th>Fecha</th><th>Instructor</th><th>Cumplimiento</th></tr></thead>
      <tbody></tbody>
    </table></div>
  </div>
</div>

<div id="emptySection" class="card fade-in" style="text-align:center;padding:60px 24px">
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:64px;height:64px;color:var(--text-dim);margin-bottom:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
  <p style="font-size:1rem;color:var(--text-muted);margin-bottom:8px">Selecciona un aprendiz para ver su avance detallado</p>
  <p style="font-size:.8rem;color:var(--text-dim)">Puedes buscar por nombre o número de documento. Usa las flechas ↑↓ para navegar</p>
</div>

<script>
const aprendices = <?= json_encode($aprendices, JSON_UNESCAPED_UNICODE) ?>;
let chartGlobal;
let acIndex = -1;

const input = document.getElementById('buscarAprendiz');
const acList = document.getElementById('autocompleteList');
const searchCount = document.getElementById('searchCount');
const filtroPrograma = document.getElementById('filtroPrograma');
const btnLimpiar = document.getElementById('btnLimpiar');

function getFilteredList() {
  const prog = filtroPrograma.value;
  return prog ? aprendices.filter(a => a.id_ficha == prog) : aprendices;
}

function renderAutocomplete(matches) {
  acIndex = -1;
  acList.innerHTML = matches.map(a => `<div class="autocomplete-item" data-doc="${a.documento}">
    <div><strong>${a.nombre}</strong><br><small style="color:var(--text-dim)">${a.programa}</small></div>
    <small style="color:var(--text-muted);white-space:nowrap">${a.documento}</small>
  </div>`).join('');
  acList.classList.toggle('open', matches.length > 0);
  searchCount.textContent = matches.length ? matches.length + ' resultado' + (matches.length>1?'s':'') : '';
}

function setActiveItem(idx) {
  const items = acList.querySelectorAll('.autocomplete-item');
  items.forEach(el => el.classList.remove('active'));
  if (idx >= 0 && idx < items.length) {
    items[idx].classList.add('active');
    items[idx].scrollIntoView({ block: 'nearest' });
  }
}

function selectItem(item) {
  const ap = aprendices.find(a => a.documento === item.dataset.doc);
  input.value = ap ? ap.nombre : item.textContent.trim();
  acList.classList.remove('open');
  searchCount.textContent = '';
  acIndex = -1;
  cargarAprendiz(item.dataset.doc);
}

function doSearch() {
  const q = input.value.toLowerCase().trim();
  btnLimpiar.style.display = q ? 'block' : 'none';
  const list = getFilteredList();
  if (!q) { acList.classList.remove('open'); searchCount.textContent = ''; return; }
  const matches = list.filter(a => a.nombre.toLowerCase().includes(q) || a.documento.includes(q)).slice(0,10);
  renderAutocomplete(matches);
}

input.addEventListener('input', doSearch);

btnLimpiar.addEventListener('click', () => {
  input.value = '';
  btnLimpiar.style.display = 'none';
  acList.classList.remove('open');
  searchCount.textContent = '';
  document.getElementById('perfilSection').style.display = 'none';
  document.getElementById('emptySection').style.display = 'block';
  input.focus();
});

filtroPrograma.addEventListener('change', () => {
  input.value = '';
  acList.classList.remove('open');
  searchCount.textContent = '';
  const prog = filtroPrograma.value;
  if (prog) {
    const list = getFilteredList();
    searchCount.textContent = list.length + ' aprendices en este programa';
  }
});

input.addEventListener('keydown', e => {
  const items = acList.querySelectorAll('.autocomplete-item');
  if (!items.length) return;

  if (e.key === 'ArrowDown') {
    e.preventDefault();
    acIndex = Math.min(acIndex + 1, items.length - 1);
    setActiveItem(acIndex);
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    acIndex = Math.max(acIndex - 1, 0);
    setActiveItem(acIndex);
  } else if (e.key === 'Enter') {
    e.preventDefault();
    if (acIndex >= 0 && acIndex < items.length) {
      selectItem(items[acIndex]);
    } else if (items.length === 1) {
      selectItem(items[0]);
    }
  } else if (e.key === 'Escape') {
    acList.classList.remove('open');
    acIndex = -1;
  }
});

acList.addEventListener('click', e => {
  const item = e.target.closest('.autocomplete-item');
  if (!item) return;
  selectItem(item);
});

document.addEventListener('click', e => { if (!e.target.closest('.search-box')) { acList.classList.remove('open'); acIndex = -1; } });

// Funciones badge removidas (ahora globales en footer.php)

function cargarAprendiz(doc) {
  document.getElementById('emptySection').style.display = 'none';
  document.getElementById('perfilSection').style.display = 'block';

  const ap = aprendices.find(a => a.documento === doc);
  document.getElementById('perfilCard').innerHTML = `
    <div class="profile-avatar">${ap.nombre.charAt(0)}</div>
    <div class="profile-info" style="flex:1">
      <h3>${ap.nombre}</h3>
      <p>Documento: <strong>${doc}</strong> · Programa: <strong>${ap.programa}</strong></p>
      <p>${badgeEstado(ap.estado)}</p>
    </div>`;

  // Avance por competencia
  fetch(`${window.BASE_URL}index.php?module=consulta&action=avance?documento=${doc}`)
    .then(r=>r.json()).then(d=>{
      document.getElementById('avanceBarras').innerHTML = d.map(x => {
        const pct = +x.porcentaje_avance||0;
        const cls = pct>=80?'':pct>=50?'orange':'red';
        return `<div class="mb-16">
          <div class="progress-label"><span>${x.competencia.substring(0,50)}</span><strong>${pct}%</strong></div>
          <div class="progress-wrap"><div class="progress-bar ${cls}" style="width:${pct}%"></div></div>
          <div style="font-size:.72rem;color:#7a8fa6;margin-top:3px">✓ ${x.aprobados} aprobados · ⏳ ${x.por_evaluar} por evaluar · ✗ ${x.no_aprobados} no aprobados</div>
        </div>`;
      }).join('') || '<p class="text-muted">Sin competencias registradas</p>';

      // Mini KPIs
      const totalR = d.reduce((s,x)=>s + +x.total_resultados,0);
      const aprobR = d.reduce((s,x)=>s + +x.aprobados,0);
      const porEval = d.reduce((s,x)=>s + +x.por_evaluar,0);
      const noAprob = d.reduce((s,x)=>s + +x.no_aprobados,0);
      const pctG = totalR ? Math.round(aprobR*100/totalR) : 0;

      document.getElementById('miniKpis').innerHTML = [
        {label:'Competencias',val:d.length,cls:'cyan'},
        {label:'Aprobados',val:aprobR,cls:'green'},
        {label:'Por Evaluar',val:porEval,cls:'orange'},
        {label:'No Aprobados',val:noAprob,cls:'red'}
      ].map(k=>`<div class="kpi-card ${k.cls}" style="padding:16px;min-height:auto">
        <div class="kpi-value" style="font-size:1.6rem">${k.val}</div>
        <div class="kpi-label">${k.label}</div>
      </div>`).join('');

      // Global donut
      document.getElementById('pctGlobal').textContent = pctG+'%';
      if (chartGlobal) chartGlobal.destroy();
      chartGlobal = new Chart(document.getElementById('chartGlobal').getContext('2d'), {
        type:'doughnut',
        data:{datasets:[{data:[pctG,100-pctG],backgroundColor:['#39A900','rgba(255,255,255,0.05)'],borderWidth:0,cutout:'80%'}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{enabled:false}}}
      });
    });

  // Seguimiento resultados
  fetch(`${window.BASE_URL}index.php?module=consulta&action=seguimiento?documento=${doc}`)
    .then(r=>r.json()).then(d=>{
      document.getElementById('totalResultados').textContent = d.length + ' resultados';
      const tb = document.querySelector('#tablaResultados tbody');
      tb.innerHTML = d.map(x=>`<tr>
        <td class="text-muted col-long">${x.competencia}</td>
        <td class="col-long"><strong>${x.resultado_aprendizaje}</strong></td>
        <td>${badgeJuicio(x.tipo_juicio)}</td>
        <td class="text-muted">${x.fecha_juicio}</td>
        <td class="text-muted">${x.instructor}</td>
        <td><div class="progress-wrap" style="width:80px"><div class="progress-bar ${x.cumplimiento_pct==100?'':'red'}" style="width:${x.cumplimiento_pct}%"></div></div></td>
      </tr>`).join('') || '<tr><td colspan="6" class="empty-state">Sin resultados</td></tr>';
    });
}
</script>
<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
