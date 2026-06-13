/* ===================================================================
   fases.js — Módulo Gestión de Fases Formativas
   Depende del HTML de fases_proyecto.php y del diseño en styles.css
   =================================================================== */
const API = '/sistema_gestion_datos/controllers/fases_crud.php';

let currentFaseId   = null;
let allFases        = [];     // cache de fases para filtros
let allActividades  = [];     // cache de actividades para filtros

/* ── Modales ── */
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function openModal(id)  { document.getElementById(id).classList.add('open'); }

/* ── Helpers ── */
function getProgramaId() {
  return document.getElementById('globalPrograma')?.value || '';
}

function escapeHtml(str) {
  return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

function highlightText(text, query) {
  if (!query) return escapeHtml(text);
  const safe = escapeHtml(text);
  const safeQ = escapeHtml(query).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return safe.replace(new RegExp(`(${safeQ})`, 'gi'), '<mark class="highlight">$1</mark>');
}

/* ── Cambio de programa global ── */
function onProgramaChange() {
  const idFicha = getProgramaId();

  // Sincronizar selector PDF
  const pdfSel = document.getElementById('pdfPrograma');
  if (pdfSel) pdfSel.value = idFicha;

  currentFaseId = null;
  document.getElementById('btnNuevaFase').style.display = idFicha ? 'inline-flex' : 'none';
  document.getElementById('btnNuevaActividad').style.display = 'none';
  document.getElementById('filtroActividadesBar').style.display = 'none';
  document.getElementById('countActividades').style.display = 'none';
  document.getElementById('tituloActividades').innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
    </svg>
    Actividades`;

  document.getElementById('listaActividades').innerHTML = `
    <div class="empty-panel">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59"/>
      </svg>
      <p>Selecciona una fase para<br>ver sus actividades</p>
    </div>`;

  // Reset búsqueda de fases
  const searchFases = document.getElementById('searchFases');
  if (searchFases) { searchFases.value = ''; document.getElementById('clearFases').classList.remove('visible'); }

  if (searchFases) { searchFases.value = ''; document.getElementById('clearFases').classList.remove('visible'); }

  cargarFases();
  
  // Also sync the "Proyectos" tab if we're changing global program
  if(idFicha) {
    seleccionarProyecto(idFicha);
  }
}

/* ── CARGAR PROYECTOS (NUEVO TAB) ── */
let proyectosData = [];

function cargarProyectos(){
  fetch(`${API}?action=list_proyectos`).then(r=>r.json()).then(d=>{
    proyectosData = d;
    const idFicha = getProgramaId();
    if(idFicha) {
      seleccionarProyecto(idFicha);
    } else {
      mostrarMensajeVacio("Selecciona un programa de formación en la parte superior para ver su proyecto formativo.");
    }
  }).catch(() => {
    console.error("Error al cargar proyectos");
  });
}

function mostrarMensajeVacio(mensaje) {
  const container = document.getElementById('proyectoContenedor');
  const emptyState = document.getElementById('emptyState');
  if(container) container.style.display = 'none';
  if(emptyState) {
    emptyState.style.display = 'block';
    const p = emptyState.querySelector('p');
    if(p) p.innerHTML = mensaje;
  }
}

function seleccionarProyecto(id_ficha){
  if(!document.getElementById('proyectoContenedor')) return;
  
  if(!id_ficha) {
    mostrarMensajeVacio("Selecciona un programa de formación en la parte superior para ver su proyecto formativo.");
    return;
  }
  
  // Si proyectosData aún no carga, intentarlo luego
  if(proyectosData.length === 0) {
    setTimeout(() => seleccionarProyecto(id_ficha), 500);
    return;
  }
  
  const p = proyectosData.find(x => x.id_ficha == id_ficha);
  
  if(!p) {
    mostrarMensajeVacio("Este programa no tiene un proyecto formativo cargado.");
    return;
  }
  
  document.getElementById('emptyState').style.display = 'none';
  document.getElementById('proyectoContenedor').style.display = 'block';
  document.getElementById('pNombre').textContent = p.nombre_proyecto || 'Proyecto Formativo';
  document.getElementById('pSub').textContent = `${p.nombre} (Código: ${p.codigo_programa_sofia||'—'})`;
  document.getElementById('pCentro').textContent = p.centro_formacion || '—';
  document.getElementById('pRegional').textContent = p.regional || '—';
  document.getElementById('pTiempo').textContent = p.tiempo_estimado_meses ? p.tiempo_estimado_meses + ' meses' : '—';
  
  document.getElementById('btnEliminarProyecto').onclick = () => eliminarProyecto(id_ficha);
  
  document.getElementById('fasesContenedor').innerHTML = '<div class="text-center text-muted" style="padding: 20px;">Cargando resumen de fases...</div>';
  
  fetch(`${API}?action=get_proyecto_detalle&id_ficha=${id_ficha}`).then(r=>r.json()).then(fases => {
    let html = '';
    
    if(fases.length === 0) {
      document.getElementById('fasesContenedor').innerHTML = '<div class="empty-state">No hay fases registradas en este proyecto.</div>';
      return;
    }
    let globalActividades = 0;
    let globalResultados = 0;
    let globalCompetenciasSet = new Set();
    
    fases.forEach(fase => {
      globalActividades += fase.actividades.length;
      fase.actividades.forEach(act => {
        act.relaciones.forEach(r => {
          globalResultados++;
          let keyGlobal = r.codigo_competencia || (r.nombre_competencia ? r.nombre_competencia.trim().toUpperCase() : null);
          if(keyGlobal) globalCompetenciasSet.add(keyGlobal);
        });
      });
    });
    
    document.getElementById('pTotalesGlobales').innerHTML = `
      <div style="margin-top:12px;display:flex;gap:16px;font-size:0.85rem;background:var(--bg2);padding:8px 12px;border-radius:6px;width:fit-content">
        <div><strong style="color:var(--text)">${globalActividades}</strong> <span style="color:var(--text-dim)">Actividades Totales</span></div>
        <div><strong style="color:var(--text)">${globalCompetenciasSet.size}</strong> <span style="color:var(--text-dim)">Competencias Únicas</span></div>
        <div><strong style="color:var(--text)">${globalResultados}</strong> <span style="color:var(--text-dim)">Resultados Totales</span></div>
      </div>
    `;

    html += '<div class="grid-2">';
    
    fases.forEach((fase, i) => {
      let actividadesCount = fase.actividades.length;
      let resultadosCount = 0;
      let compUnicasFase = new Set();
      let actividadesHtml = '';
      
      if(actividadesCount === 0) {
        actividadesHtml = '<div class="text-muted" style="font-size:0.85rem;padding:10px 0">No hay actividades.</div>';
      } else {
        fase.actividades.forEach(act => {
          act.relaciones.forEach(r => {
             resultadosCount++;
             let keyFase = r.codigo_competencia || (r.nombre_competencia ? r.nombre_competencia.trim().toUpperCase() : null);
             if(keyFase) compUnicasFase.add(keyFase);
          });
          
          actividadesHtml += `
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--card-border)">
              <div style="color:var(--text);font-size:0.9rem;font-weight:600;margin-bottom:6px">📋 ${act.nombre}</div>
              <div style="display:flex;flex-direction:column;gap:6px">
                ${act.relaciones.map(r => `
                  <div style="background:var(--card);padding:8px 12px;border-radius:6px;border-left:3px solid var(--primary);font-size:0.8rem">
                    <div style="color:var(--text-muted);margin-bottom:2px"><strong>Competencia:</strong> ${r.nombre_competencia||'—'}</div>
                    <div style="color:var(--text)"><strong>Resultado:</strong> ${r.nombre_resultado||'—'}</div>
                  </div>
                `).join('')}
              </div>
            </div>
          `;
        });
      }
      
      const competenciasCount = compUnicasFase.size;
      
      html += `
        <div class="card fade-in" style="animation-delay:${i*0.1}s">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;border-bottom:1px solid var(--card-border);padding-bottom:12px">
            <div style="width:36px;height:36px;background:var(--primary-glow);border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);font-size:1.1rem">${fase.orden}</div>
            <div style="flex:1">
              <div style="font-weight:600;color:var(--text);font-size:1.05rem">${fase.nombre_fase}</div>
            </div>
          </div>
          
          <div style="display:flex;gap:8px;margin-bottom:16px">
            <div style="flex:1;background:var(--bg);border:1px solid var(--card-border);padding:8px;border-radius:6px;text-align:center">
              <div style="font-size:1.2rem;font-weight:700;color:var(--text);opacity:0.9">${actividadesCount}</div>
              <div style="font-size:0.75rem;color:var(--text-dim)">Actividades</div>
            </div>
            <div style="flex:1;background:var(--bg);border:1px solid var(--card-border);padding:8px;border-radius:6px;text-align:center">
              <div style="font-size:1.2rem;font-weight:700;color:var(--text);opacity:0.9">${competenciasCount}</div>
              <div style="font-size:0.75rem;color:var(--text-dim)">Competencias</div>
            </div>
            <div style="flex:1;background:var(--bg);border:1px solid var(--card-border);padding:8px;border-radius:6px;text-align:center">
              <div style="font-size:1.2rem;font-weight:700;color:var(--text);opacity:0.9">${resultadosCount}</div>
              <div style="font-size:0.75rem;color:var(--text-dim)">Resultados</div>
            </div>
          </div>
          
          <div style="background:var(--bg);border:1px solid var(--card-border);border-radius:8px;padding:12px">
            <div style="font-size:0.8rem;text-transform:uppercase;letter-spacing:1px;color:var(--text-dim);font-weight:600">Desglose de Actividades</div>
            ${actividadesHtml}
          </div>
        </div>
      `;
    });
    
    html += '</div>';
    document.getElementById('fasesContenedor').innerHTML = html;
  });
}

function eliminarProyecto(id_ficha){
  if(!confirm('¿Estás seguro de eliminar TODOS los datos del proyecto formativo de la ficha '+id_ficha+'?\n\n- Se eliminarán las fases, actividades y resultados importados.\n- NO se eliminarán los aprendices ni los juicios evaluativos.\n- Podrás volver a subir el PDF luego.')) return;
  
  fetch(`${API}?action=delete_proyecto`,{method:'POST',body:JSON.stringify({id_ficha})}).then(r=>r.json()).then(d=>{
    if(d.ok){
      cargarProyectos();
      cargarFases(); // Recargar el tab de CRUD
    } else {
      alert('Error: '+d.error);
    }
  }).catch(e => alert('Error de conexión: '+e));
}

/* ── CARGAR FASES ── */
function cargarFases() {
  const idFicha = getProgramaId();
  const url = `${API}?action=list_fases` + (idFicha ? `&id_ficha=${idFicha}` : '');
  document.getElementById('listaFases').innerHTML = '<div class="loading"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></div>';

  fetch(url)
    .then(r => r.json())
    .then(fases => {
      allFases = fases;
      renderFases(fases, '');
    })
    .catch(() => {
      document.getElementById('listaFases').innerHTML = '<div class="empty-state"><p>Error al cargar fases. Revisa la conexión.</p></div>';
    });
}

function renderFases(fases, query) {
  const idFicha = getProgramaId();
  const counter = document.getElementById('countFases');
  counter.textContent = fases.length;

  if (!fases.length) {
    document.getElementById('listaFases').innerHTML = `
      <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125z"/></svg>
        <p>${query ? `Sin resultados para "<strong>${escapeHtml(query)}</strong>"` : 'No hay fases configuradas.<br>Carga un PDF o crea una fase manualmente.'}</p>
      </div>`;
    return;
  }

  document.getElementById('listaFases').innerHTML = fases.map(f => {
    const isActive = f.id_fase == currentFaseId;
    return `
    <div class="fase-item${isActive ? ' selected' : ''}" id="fase-row-${f.id_fase}" onclick="seleccionarFase(${f.id_fase},'${f.nombre_fase.replace(/'/g, "\\'")}')">
      <div class="fase-order-badge">${f.orden}</div>
      <div class="fase-info">
        <strong title="${escapeHtml(f.nombre_fase)}">${highlightText(f.nombre_fase, query)}</strong>
        ${f.descripcion ? `<small title="${escapeHtml(f.descripcion)}">${highlightText(f.descripcion, query)}</small>` : ''}
      </div>
      ${idFicha ? `
      <div class="fase-actions">
        <button class="btn btn-xs btn-ghost btn-icon" title="Editar"
          onclick="event.stopPropagation();editarFase(${f.id_fase},'${escapeHtml(f.nombre_fase).replace(/'/g,"\\'")}',${f.orden},'${(f.descripcion||'').replace(/'/g,"\\'")}')" >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
        </button>
        <button class="btn btn-xs btn-danger btn-icon" title="Eliminar"
          onclick="event.stopPropagation();eliminarFase(${f.id_fase},'${escapeHtml(f.nombre_fase)}')" >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
        </button>
      </div>` : ''}
    </div>`;
  }).join('');
}

/* Filtrar fases por texto */
function filtrarFases(query) {
  const btn = document.getElementById('clearFases');
  btn.classList.toggle('visible', query.length > 0);
  const q = query.toLowerCase().trim();
  const filtradas = !q ? allFases : allFases.filter(f =>
    f.nombre_fase.toLowerCase().includes(q) || (f.descripcion || '').toLowerCase().includes(q)
  );
  renderFases(filtradas, query);
}

function limpiarBusquedaFases() {
  const input = document.getElementById('searchFases');
  input.value = '';
  document.getElementById('clearFases').classList.remove('visible');
  renderFases(allFases, '');
}

/* ── SELECCIONAR FASE ── */
function seleccionarFase(id, nombre) {
  currentFaseId = id;
  const idFicha = getProgramaId();

  // Resaltar selección
  document.querySelectorAll('.fase-item').forEach(el => el.classList.remove('selected'));
  const row = document.getElementById(`fase-row-${id}`);
  if (row) row.classList.add('selected');

  document.getElementById('tituloActividades').innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
    </svg>
    ${escapeHtml(nombre)}`;

  document.getElementById('btnNuevaActividad').style.display = idFicha ? 'inline-flex' : 'none';
  document.getElementById('filtroActividadesBar').style.display = 'block';
  document.getElementById('countActividades').style.display = 'inline-flex';

  // Reset búsqueda actividades
  const sa = document.getElementById('searchActividades');
  if (sa) { sa.value = ''; document.getElementById('clearActividades').classList.remove('visible'); }

  cargarActividades(id, nombre);
}

/* ── CARGAR ACTIVIDADES ── */
function cargarActividades(id, nombre) {
  const idFicha = getProgramaId();
  const url = `${API}?action=list_actividades&id_fase=${id}&nombre_fase=${encodeURIComponent(nombre||'')}` + (idFicha ? `&id_ficha=${idFicha}` : '');
  document.getElementById('listaActividades').innerHTML = '<div class="loading"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></div>';

  fetch(url)
    .then(r => r.json())
    .then(acts => {
      allActividades = acts;
      renderActividades(acts, '');
    });
}

function renderActividades(acts, query) {
  const counter = document.getElementById('countActividades');
  counter.textContent = acts.length;
  const idFicha = getProgramaId();

  if (!acts.length) {
    document.getElementById('listaActividades').innerHTML = `
      <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12"/></svg>
        <p>${query ? `Sin resultados para "<strong>${escapeHtml(query)}</strong>"` : 'No hay actividades registradas en esta fase.'}</p>
      </div>`;
    return;
  }

  document.getElementById('listaActividades').innerHTML = acts.map(a => `
    <div class="act-item">
      <div class="act-info">
        <strong>${highlightText(a.nombre, query)}</strong>
        ${a.descripcion ? `<small>${highlightText(a.descripcion, query)}</small>` : ''}
      </div>
      ${idFicha ? `
      <div class="act-del">
        <button class="btn btn-xs btn-danger btn-icon" title="Eliminar actividad"
          onclick="eliminarActividad(${a.id_actividad},'${escapeHtml(a.nombre).replace(/'/g,"\\'")}')" >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
        </button>
      </div>` : ''}
    </div>`).join('');
}

/* Filtrar actividades */
function filtrarActividades(query) {
  const btn = document.getElementById('clearActividades');
  btn.classList.toggle('visible', query.length > 0);
  const q = query.toLowerCase().trim();
  const filtradas = !q ? allActividades : allActividades.filter(a =>
    a.nombre.toLowerCase().includes(q) || (a.descripcion || '').toLowerCase().includes(q)
  );
  renderActividades(filtradas, query);
}

function limpiarBusquedaActividades() {
  const input = document.getElementById('searchActividades');
  input.value = '';
  document.getElementById('clearActividades').classList.remove('visible');
  renderActividades(allActividades, '');
}

/* ── CRUD FASES ── */
function openModalFase() {
  document.getElementById('faseId').value = '';
  document.getElementById('faseNombre').value = '';
  document.getElementById('faseOrden').value = (allFases.length + 1);
  document.getElementById('faseDesc').value = '';
  document.getElementById('modalFaseTitulo').textContent = 'Nueva Fase';
  openModal('modalFase');
}

function editarFase(id, nombre, orden, desc) {
  document.getElementById('faseId').value = id;
  document.getElementById('faseNombre').value = nombre;
  document.getElementById('faseOrden').value = orden;
  document.getElementById('faseDesc').value = desc;
  document.getElementById('modalFaseTitulo').textContent = 'Editar Fase';
  openModal('modalFase');
}

function guardarFase() {
  const nombre = document.getElementById('faseNombre').value.trim();
  if (!nombre) { document.getElementById('faseNombre').focus(); return; }

  const id = document.getElementById('faseId').value;
  const idFicha = getProgramaId();
  const payload = {
    nombre_fase: nombre,
    orden:       +document.getElementById('faseOrden').value,
    descripcion: document.getElementById('faseDesc').value.trim(),
    id_ficha:    idFicha ? +idFicha : null,
  };
  if (id) payload.id_fase = +id;

  const btnGuardar = document.querySelector('#modalFase .btn-primary');
  if (btnGuardar) { btnGuardar.disabled = true; btnGuardar.textContent = 'Guardando…'; }

  fetch(`${API}?action=${id ? 'update_fase' : 'create_fase'}`, {
    method: 'POST', body: JSON.stringify(payload)
  })
    .then(r => r.json())
    .then(() => { closeModal('modalFase'); cargarFases(); })
    .finally(() => { if (btnGuardar) { btnGuardar.disabled = false; btnGuardar.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Guardar'; } });
}

function eliminarFase(id, nombre) {
  if (!confirm(`¿Eliminar la fase "${nombre}" y todas sus actividades?\nEsta acción no se puede deshacer.`)) return;
  fetch(`${API}?action=delete_fase`, { method: 'POST', body: JSON.stringify({ id_fase: id }) })
    .then(() => {
      if (currentFaseId == id) {
        currentFaseId = null;
        document.getElementById('listaActividades').innerHTML = '<div class="empty-panel"><p>Selecciona una fase para ver sus actividades</p></div>';
        document.getElementById('btnNuevaActividad').style.display = 'none';
        document.getElementById('filtroActividadesBar').style.display = 'none';
        document.getElementById('countActividades').style.display = 'none';
      }
      cargarFases();
    });
}

/* ── CRUD ACTIVIDADES ── */
function openModalActividad() {
  document.getElementById('actNombre').value = '';
  document.getElementById('actDesc').value = '';
  openModal('modalActividad');
}

function guardarActividad() {
  const nombre = document.getElementById('actNombre').value.trim();
  if (!nombre) { document.getElementById('actNombre').focus(); return; }

  const idFicha = getProgramaId();
  const payload = {
    nombre,
    descripcion: document.getElementById('actDesc').value.trim(),
    id_fase:     currentFaseId,
    id_ficha:    idFicha ? +idFicha : null,
  };

  const btnGuardar = document.querySelector('#modalActividad .btn-primary');
  if (btnGuardar) { btnGuardar.disabled = true; btnGuardar.textContent = 'Guardando…'; }

  fetch(`${API}?action=create_actividad`, { method: 'POST', body: JSON.stringify(payload) })
    .then(r => r.json())
    .then(() => { closeModal('modalActividad'); cargarActividades(currentFaseId); })
    .finally(() => { if (btnGuardar) { btnGuardar.disabled = false; btnGuardar.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Guardar'; } });
}

function eliminarActividad(id, nombre) {
  if (!confirm(`¿Eliminar la actividad "${nombre}"?`)) return;
  fetch(`${API}?action=delete_actividad`, { method: 'POST', body: JSON.stringify({ id_actividad: id }) })
    .then(() => cargarActividades(currentFaseId));
}

/* ── Sincronizar selectores ── */
document.addEventListener('DOMContentLoaded', () => {
  const pdfSel    = document.getElementById('pdfPrograma');
  const globalSel = document.getElementById('globalPrograma');
  if (pdfSel && globalSel) {
    pdfSel.addEventListener('change', () => { globalSel.value = pdfSel.value; });
  }
});

/* ── Atajos de teclado en modales ── */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-bg.open').forEach(m => m.classList.remove('open'));
  }
});

/* ── Init ── */
cargarProyectos();
cargarFases();
onProgramaChange();
