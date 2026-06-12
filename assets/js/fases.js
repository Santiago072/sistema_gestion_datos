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
