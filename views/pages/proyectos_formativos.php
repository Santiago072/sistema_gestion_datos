<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="card fade-in mb-24">
  <div class="section-header">
    <div class="section-title">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:20px;height:20px"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" /></svg>
      Proyectos Formativos
    </div>
    <div style="display:flex;gap:12px">
      <a href="fases_proyecto.php?tab=pdf" class="btn btn-primary btn-sm">+ Cargar Nuevo PDF</a>
      <button class="btn btn-secondary btn-sm" onclick="cargarProyectos()">↻ Actualizar</button>
    </div>
  </div>
  
  <div class="form-group" style="margin-top:16px;max-width:480px">
    <label style="margin-bottom:6px;display:block;font-weight:600">Seleccionar Proyecto / Ficha</label>
    <select id="selProyecto" onchange="seleccionarProyecto(this.value)" style="width:100%;padding:12px 14px;font-size:.9rem">
      <option value="">Cargando proyectos...</option>
    </select>
  </div>
</div>

<div id="proyectoContenedor" style="display:none">
  <!-- Info cabecera -->
  <div class="card fade-in mb-24" style="background: linear-gradient(135deg, rgba(57,169,0,0.1), rgba(0,0,0,0)); border-left: 4px solid #39A900">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <h2 id="pNombre" style="margin-bottom:4px;color:var(--text-light)"></h2>
        <div style="color:var(--text-muted);font-size:0.9rem" id="pSub"></div>
        <div style="margin-top:12px;display:flex;gap:16px;font-size:0.85rem">
          <div><strong style="color:var(--text-dim)">Centro:</strong> <span id="pCentro"></span></div>
          <div><strong style="color:var(--text-dim)">Regional:</strong> <span id="pRegional"></span></div>
          <div><strong style="color:var(--text-dim)">Duración:</strong> <span id="pTiempo"></span></div>
        </div>
        <div id="pTotalesGlobales"></div>
      </div>
      <button class="btn btn-danger btn-sm" id="btnEliminar">🗑 Eliminar Proyecto</button>
    </div>
  </div>

  <!-- Fases Cards -->
  <div class="section-title mb-16">Fases y Actividades</div>
  <div id="fasesContenedor"></div>
</div>

<div id="emptyState" class="empty-state card fade-in" style="display:none">
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:40px;height:40px;color:#4a5f78;margin:0 auto 12px"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
  <p>No hay proyectos formativos cargados.</p>
  <a href="fases_proyecto.php" class="btn btn-primary mt-16">Cargar desde PDF</a>
</div>

<script>
const API = '/sistema_gestion_datos/controllers/fases_crud.php';
let proyectosData = [];

function cargarProyectos(){
  fetch(`${API}?action=list_proyectos`).then(r=>r.json()).then(d=>{
    proyectosData = d;
    const sel = document.getElementById('selProyecto');
    if(d.length === 0){
      sel.innerHTML = '<option value="">No hay proyectos</option>';
      sel.disabled = true;
      document.getElementById('proyectoContenedor').style.display = 'none';
      document.getElementById('emptyState').style.display = 'block';
    } else {
      sel.disabled = false;
      document.getElementById('emptyState').style.display = 'none';
      sel.innerHTML = d.map(p => `<option value="${p.id_ficha}">${p.codigo_programa_sofia} - ${p.nombre_proyecto||p.nombre} (Ficha ${p.id_ficha})</option>`).join('');
      seleccionarProyecto(d[0].id_ficha);
    }
  });
}

function seleccionarProyecto(id_ficha){
  if(!id_ficha) return;
  const p = proyectosData.find(x => x.id_ficha == id_ficha);
  if(!p) return;
  
  document.getElementById('proyectoContenedor').style.display = 'block';
  document.getElementById('pNombre').textContent = p.nombre_proyecto || 'Proyecto Formativo';
  document.getElementById('pSub').textContent = `${p.nombre} (Código: ${p.codigo_programa_sofia||'—'})`;
  document.getElementById('pCentro').textContent = p.centro_formacion || '—';
  document.getElementById('pRegional').textContent = p.regional || '—';
  document.getElementById('pTiempo').textContent = p.tiempo_estimado_meses ? p.tiempo_estimado_meses + ' meses' : '—';
  
  document.getElementById('btnEliminar').onclick = () => eliminarProyecto(id_ficha);
  
  document.getElementById('fasesContenedor').innerHTML = '<div class="text-center text-muted">Cargando fases...</div>';
  
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
        <div><strong style="color:var(--text)">${globalCompetenciasSet.size}</strong> <span style="color:var(--text-dim)">Competencias Únicas en todo el proyecto</span></div>
        <div><strong style="color:var(--text)">${globalResultados}</strong> <span style="color:var(--text-dim)">Resultados Totales</span></div>
      </div>
    `;

    // Grid de 2 columnas para las 4 fases
    html += '<div class="grid-2">';
    
    fases.forEach((fase, i) => {
      let actividadesCount = fase.actividades.length;
      let resultadosCount = 0;
      // Usar un Set a nivel de FASE para no contar competencias repetidas entre actividades
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
    } else {
      alert('Error: '+d.error);
    }
  }).catch(e => alert('Error de conexión: '+e));
}

// Cargar al inicio
document.addEventListener('DOMContentLoaded', cargarProyectos);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
