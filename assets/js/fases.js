/* ===================================================================
   fases.js — Módulo Gestión de Fases Formativas
   Depende del HTML de fases/index.php y del diseño en styles.css
   =================================================================== */
const API = (window.BASE_URL || '') + 'index.php?module=fases&action=crud';

let currentFaseId   = null;
let allFases        = [];     // cache de fases para filtros
let allActividades  = [];     // cache de actividades para filtros
let proyectosData   = [];     // cache de proyectos formativos

/* ── Modales ── */
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function openModal(id)  { document.getElementById(id).classList.add('open'); }

/* ── Helpers ── */
function getProyectoId() {
  const sel = document.getElementById('globalProyecto');
  return sel ? sel.value : '';
}

function getProgramaId() {
  const sel = document.getElementById('globalPrograma');
  return sel ? sel.value : '';
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

/* ── Cambio de Proyecto Formativo Global ── */
function onProyectoChange() {
  const idProyecto = getProyectoId();

  currentFaseId = null;
  const btnNuevaFase = document.getElementById('btnNuevaFase');
  if (btnNuevaFase) btnNuevaFase.style.display = idProyecto ? 'inline-flex' : 'none';

  const btnNuevaAct = document.getElementById('btnNuevaActividad');
  if (btnNuevaAct) btnNuevaAct.style.display = 'none';

  const filtroBar = document.getElementById('filtroActividadesBar');
  if (filtroBar) filtroBar.style.display = 'none';

  const countActs = document.getElementById('countActividades');
  if (countActs) countActs.style.display = 'none';

  const titActs = document.getElementById('tituloActividades');
  if (titActs) {
    titActs.innerHTML = `
      <i class="bi bi-list-task"></i>
      Actividades`;
  }

  const listaActs = document.getElementById('listaActividades');
  if (listaActs) {
    listaActs.innerHTML = `
      <div class="empty-panel">
        <i class="bi bi-cursor text-muted" style="font-size:2rem;display:block;margin-bottom:8px"></i>
        <p>Selecciona una fase para<br>ver sus actividades</p>
      </div>`;
  }

  // Reset búsqueda de fases
  const searchFases = document.getElementById('searchFases');
  if (searchFases) { 
    searchFases.value = ''; 
    const cf = document.getElementById('clearFases');
    if (cf) cf.classList.remove('visible'); 
  }

  if (idProyecto) {
    seleccionarProyecto(idProyecto);
    cargarFases();
  } else {
    mostrarMensajeVacio("Selecciona un proyecto formativo en la parte superior para consultar sus fases y fichas vinculadas.");
    const lf = document.getElementById('listaFases');
    if (lf) {
      lf.innerHTML = '<div class="empty-state"><p>Selecciona un proyecto formativo para ver sus fases.</p></div>';
    }
    const cf = document.getElementById('countFases');
    if (cf) cf.textContent = '0';
  }
}

// Mantener por compatibilidad si algún componente invoca onProgramaChange
function onProgramaChange() {
  onProyectoChange();
}

/* ── CARGAR PROYECTOS (ESTRUCTURA + DROPDOWN) ── */
function cargarProyectos() {
  fetch(`${API}&subaction=list_proyectos`)
    .then(r => r.json())
    .then(d => {
      proyectosData = d || [];
      const select = document.getElementById('globalProyecto');

      // Si el select no tiene opciones dinámicas o necesita refresco
      if (select && select.options.length <= 1 && proyectosData.length > 0) {
        select.innerHTML = '<option value="">— Seleccionar Proyecto Formativo —</option>' +
          proyectosData.map(p => {
            const nom = p.nombre || p.nombre_proyecto || 'Proyecto Formativo';
            const cod = p.codigo_programa_sofia ? ` (Código: ${escapeHtml(p.codigo_programa_sofia)})` : '';
            return `<option value="${p.id_proyecto}">${escapeHtml(nom)}${cod}</option>`;
          }).join('');
      }

      let activeId = select ? select.value : '';
      if (activeId) {
        seleccionarProyecto(activeId);
        cargarFases();
      } else {
        mostrarMensajeVacio("Selecciona un proyecto formativo en la parte superior para consultar sus fases y fichas vinculadas.");
        const lf = document.getElementById('listaFases');
        if (lf) {
          lf.innerHTML = '<div class="empty-state"><p>Selecciona un proyecto formativo para ver sus fases.</p></div>';
        }
        const cf = document.getElementById('countFases');
        if (cf) cf.textContent = '0';
      }
    })
    .catch(err => {
      console.error("Error al cargar proyectos:", err);
    });
}

function mostrarMensajeVacio(mensaje) {
  const container = document.getElementById('proyectoContenedor');
  const emptyState = document.getElementById('emptyState');
  if (container) container.style.display = 'none';
  if (emptyState) {
    emptyState.style.display = 'block';
    const p = emptyState.querySelector('p');
    if (p) p.innerHTML = mensaje;
  }
}

/* ── SELECCIONAR PROYECTO FORMATIVO ── */
function seleccionarProyecto(idProyecto) {
  if (!document.getElementById('proyectoContenedor')) return;

  if (!idProyecto) {
    mostrarMensajeVacio("Selecciona un proyecto formativo en la parte superior para consultar sus fases y fichas vinculadas.");
    return;
  }

  // Si proyectosData aún no carga, intentarlo brevemente
  if (proyectosData.length === 0) {
    setTimeout(() => seleccionarProyecto(idProyecto), 300);
    return;
  }

  const p = proyectosData.find(x => x.id_proyecto == idProyecto || x.id_ficha == idProyecto);

  if (!p) {
    mostrarMensajeVacio("El proyecto formativo seleccionado no existe o no tiene datos cargados.");
    return;
  }

  const emptyState = document.getElementById('emptyState');
  if (emptyState) emptyState.style.display = 'none';

  const container = document.getElementById('proyectoContenedor');
  if (container) container.style.display = 'block';

  // Info cabecera
  const pNombre = document.getElementById('pNombre');
  if (pNombre) pNombre.textContent = p.nombre_proyecto || 'Proyecto Formativo';

  const pSub = document.getElementById('pSub');
  if (pSub) {
    const cod = p.codigo_programa_sofia ? ` (Código: ${p.codigo_programa_sofia})` : '';
    pSub.textContent = `${p.nombre || 'Programa Curricular'}${cod}`;
  }

  const pCentro = document.getElementById('pCentro');
  if (pCentro) pCentro.textContent = p.centro_formacion || '—';

  const pRegional = document.getElementById('pRegional');
  if (pRegional) pRegional.textContent = p.regional || '—';

  const pTiempo = document.getElementById('pTiempo');
  if (pTiempo) pTiempo.textContent = p.tiempo_estimado_meses ? p.tiempo_estimado_meses + ' meses' : '—';

  const btnEliminar = document.getElementById('btnEliminarProyecto');
  if (btnEliminar) {
    btnEliminar.onclick = () => eliminarProyecto(p.id_proyecto);
  }

  // Cargar fichas vinculadas y selector para vincular nuevas fichas
  cargarFichasProyecto(p.id_proyecto);

  // Cargar fases y actividades detalladas para el tablero
  const fasesCont = document.getElementById('fasesContenedor');
  if (fasesCont) {
    fasesCont.innerHTML = '<div class="text-center text-muted" style="padding: 20px;">Cargando resumen de fases...</div>';
  }

  fetch(`${API}&subaction=get_proyecto_detalle&id_proyecto=${p.id_proyecto}`)
    .then(r => r.json())
    .then(fases => {
      let html = '';

      if (!fases || fases.length === 0) {
        if (fasesCont) fasesCont.innerHTML = '<div class="empty-state"><p>No hay fases registradas en este proyecto.</p></div>';
        return;
      }

      let globalActividades = 0;
      let globalResultados = 0;
      let globalCompetenciasSet = new Set();

      fases.forEach(fase => {
        globalActividades += (fase.actividades || []).length;
        (fase.actividades || []).forEach(act => {
          (act.relaciones || []).forEach(r => {
            globalResultados++;
            let keyGlobal = r.codigo_competencia || (r.nombre_competencia ? r.nombre_competencia.trim().toUpperCase() : null);
            if (keyGlobal) globalCompetenciasSet.add(keyGlobal);
          });
        });
      });

      const totalesDiv = document.getElementById('pTotalesGlobales');
      if (totalesDiv) {
        totalesDiv.innerHTML = `
          <div style="margin-top:12px;display:flex;gap:16px;font-size:0.85rem;background:var(--bg2);padding:8px 12px;border-radius:6px;width:fit-content;flex-wrap:wrap">
            <div><strong style="color:var(--text)">${globalActividades}</strong> <span style="color:var(--text-dim)">Actividades Totales</span></div>
            <div><strong style="color:var(--text)">${globalCompetenciasSet.size}</strong> <span style="color:var(--text-dim)">Competencias Únicas</span></div>
            <div><strong style="color:var(--text)">${globalResultados}</strong> <span style="color:var(--text-dim)">Resultados Totales</span></div>
          </div>
        `;
      }

      html += '<div class="grid-2">';

      fases.forEach((fase, i) => {
        let actividadesCount = (fase.actividades || []).length;
        let resultadosCount = 0;
        let compUnicasFase = new Set();
        let actividadesHtml = '';

        if (actividadesCount === 0) {
          actividadesHtml = '<div class="text-muted" style="font-size:0.85rem;padding:10px 0">No hay actividades.</div>';
        } else {
          fase.actividades.forEach(act => {
            (act.relaciones || []).forEach(r => {
              resultadosCount++;
              let keyFase = r.codigo_competencia || (r.nombre_competencia ? r.nombre_competencia.trim().toUpperCase() : null);
              if (keyFase) compUnicasFase.add(keyFase);
            });

            actividadesHtml += `
              <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--card-border)">
                <div style="color:var(--text);font-size:0.9rem;font-weight:600;margin-bottom:6px;display:flex;align-items:center;gap:6px">
                  <i class="bi bi-card-checklist text-primary"></i> ${escapeHtml(act.nombre)}
                </div>
                <div style="display:flex;flex-direction:column;gap:6px">
                  ${(act.relaciones || []).map(r => `
                    <div style="background:var(--card);padding:8px 12px;border-radius:6px;border-left:3px solid var(--primary);font-size:0.8rem">
                      <div style="color:var(--text-muted);margin-bottom:2px"><strong>Competencia:</strong> ${escapeHtml(r.nombre_competencia || '—')}</div>
                      <div style="color:var(--text)"><strong>Resultado:</strong> ${escapeHtml(r.nombre_resultado || '—')}</div>
                    </div>
                  `).join('')}
                </div>
              </div>
            `;
          });
        }

        const competenciasCount = compUnicasFase.size;

        html += `
          <div class="card fade-in" style="animation-delay:${i * 0.08}s">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;border-bottom:1px solid var(--card-border);padding-bottom:12px">
              <div style="width:36px;height:36px;background:var(--primary-glow);border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);font-size:1.1rem">${fase.orden}</div>
              <div style="flex:1">
                <div style="font-weight:600;color:var(--text);font-size:1.05rem">${escapeHtml(fase.nombre_fase)}</div>
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
      if (fasesCont) fasesCont.innerHTML = html;
    })
    .catch(err => {
      if (fasesCont) fasesCont.innerHTML = '<div class="empty-state"><p>Error al cargar el detalle del proyecto.</p></div>';
    });
}

/* ── GESTIÓN DE FICHAS ASOCIADAS A ESTE PROYECTO (OPCIÓN C) ── */
function cargarFichasProyecto(idProyecto) {
  const container = document.getElementById('listaFichasVinculadas');
  const badge = document.getElementById('badgeTotalFichas');
  const selectVinculable = document.getElementById('selectFichaParaVincular');

  if (container) {
    container.innerHTML = '<div class="text-muted" style="font-size:0.85rem">Cargando fichas asociadas...</div>';
  }

  // 1. Obtener fichas asociadas al proyecto
  fetch(`${API}&subaction=get_fichas_proyecto&id_proyecto=${idProyecto}`)
    .then(r => r.json())
    .then(fichas => {
      const lista = fichas || [];
      if (badge) badge.textContent = `${lista.length} vinculada${lista.length === 1 ? '' : 's'}`;

      if (lista.length === 0) {
        if (container) {
          container.innerHTML = `
            <div style="width:100%;background:rgba(255,109,0,0.08);border:1px dashed rgba(255,109,0,0.3);border-radius:8px;padding:12px 16px;color:var(--text-dim);font-size:0.85rem;display:flex;align-items:center;gap:8px">
              <i class="bi bi-info-circle text-accent" style="font-size:1.1rem"></i>
              <span>No hay fichas ni grupos vinculados a este proyecto formativo aún. Si importas juicios evaluativos de una ficha de este programa, selecciónala abajo para vincularla.</span>
            </div>`;
        }
      } else {
        if (container) {
          container.innerHTML = lista.map(f => `
            <div style="display:inline-flex;align-items:center;gap:10px;background:var(--bg);border:1px solid var(--card-border);border-left:3px solid var(--primary);padding:8px 14px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.05)">
              <div>
                <div style="font-weight:700;color:var(--text);font-size:0.9rem">Ficha ${escapeHtml(f.id_ficha)}</div>
                <div style="font-size:0.75rem;color:var(--text-muted);max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${escapeHtml(f.nombre)}">
                  ${escapeHtml(f.nombre)}
                </div>
                <div style="font-size:0.75rem;color:var(--primary);font-weight:600;margin-top:2px;display:flex;align-items:center;gap:5px">
                  <i class="bi bi-people-fill"></i> ${f.total_aprendices || 0} aprendices
                </div>
              </div>
              <button class="btn btn-xs btn-ghost btn-icon" title="Desvincular ficha de este proyecto" style="color:var(--danger);margin-left:6px"
                onclick="desvincularFicha(${f.id_ficha}, '${escapeHtml(f.id_ficha)}')">
                <i class="bi bi-x-lg" style="font-size:0.85rem"></i>
              </button>
            </div>
          `).join('');
        }
      }

      // 2. Cargar todas las fichas disponibles en el selector para vincular
      if (selectVinculable) {
        fetch(`${API}&subaction=list_fichas_disponibles`)
          .then(r => r.json())
          .then(allFichas => {
            selectVinculable.innerHTML = '<option value="">-- Seleccionar ficha del sistema --</option>';
            (allFichas || []).forEach(af => {
              const estaEnEste = af.id_proyecto == idProyecto;
              const tieneOtro = af.id_proyecto && af.id_proyecto != idProyecto;
              let suffix = '';
              if (estaEnEste) suffix = ' (Ya vinculada aquí)';
              else if (tieneOtro) suffix = ' (Asignada a otro proyecto)';

              const opt = document.createElement('option');
              opt.value = af.id_ficha;
              opt.disabled = estaEnEste;
              opt.textContent = `Ficha ${af.id_ficha} - ${af.nombre} [${af.total_aprendices || 0} aprendices]${suffix}`;
              selectVinculable.appendChild(opt);
            });
          });
      }
    })
    .catch(err => {
      if (container) container.innerHTML = '<div class="text-danger">Error al cargar fichas asociadas.</div>';
    });
}

function vincularFichaAProyecto() {
  const idProyecto = getProyectoId();
  const select = document.getElementById('selectFichaParaVincular');
  const idFicha = select ? select.value : '';

  if (!idProyecto) {
    alert('Primero selecciona un proyecto formativo.');
    return;
  }
  if (!idFicha) {
    alert('Selecciona una ficha disponible para vincular.');
    return;
  }

  fetch(`${API}&subaction=asociar_ficha`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id_ficha: idFicha, id_proyecto: idProyecto })
  })
    .then(r => r.json())
    .then(res => {
      if (res.ok) {
        cargarFichasProyecto(idProyecto);
      } else {
        alert('Error al vincular: ' + (res.error || 'No se pudo vincular la ficha.'));
      }
    })
    .catch(e => alert('Error de conexión: ' + e));
}

function desvincularFicha(idFicha, numeroFicha) {
  if (!confirm(`¿Desvincular la Ficha ${numeroFicha} de este proyecto formativo?\n\n- No se eliminarán los aprendices ni los juicios evaluativos de la ficha.\n- La ficha quedará libre para asignarse a otro proyecto o re-vincularse cuando desees.`)) {
    return;
  }

  fetch(`${API}&subaction=desasociar_ficha`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id_ficha: idFicha })
  })
    .then(r => r.json())
    .then(res => {
      if (res.ok) {
        const idProyecto = getProyectoId();
        cargarFichasProyecto(idProyecto);
      } else {
        alert('Error al desvincular: ' + (res.error || 'No se pudo desvincular la ficha.'));
      }
    })
    .catch(e => alert('Error de conexión: ' + e));
}

/* ── ELIMINAR PROYECTO FORMATIVO COMPLETO ── */
function eliminarProyecto(idProyecto) {
  if (!confirm('¿Estás seguro de eliminar este proyecto formativo?\n\n- Se eliminarán sus fases, actividades y resultados de aprendizaje curriculares.\n- NO se eliminarán los aprendices ni los juicios evaluativos de las fichas.\n- Todas las fichas asociadas quedarán desvinculadas sin perder ningún dato.')) {
    return;
  }

  fetch(`${API}&subaction=delete_proyecto`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id_proyecto: idProyecto })
  })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        // 1. Actualizar estado local
        proyectosData = (proyectosData || []).filter(x => x.id_proyecto != idProyecto);

        // 2. Limpiar estado de fases/actividades
        currentFaseId = null;
        allFases = [];
        allActividades = [];

        // 3. Resetear selectores
        const selProy = document.getElementById('globalProyecto');
        if (selProy) {
          const opt = selProy.querySelector(`option[value="${idProyecto}"]`);
          if (opt) opt.remove();
          selProy.value = '';
        }

        const globalProg = document.getElementById('globalPrograma');
        if (globalProg) globalProg.value = '';

        const pdfSel = document.getElementById('pdfPrograma');
        if (pdfSel) pdfSel.value = '';

        // 4. Ocultar contenedor del proyecto
        const container = document.getElementById('proyectoContenedor');
        if (container) container.style.display = 'none';

        const fasesCont = document.getElementById('fasesContenedor');
        if (fasesCont) fasesCont.innerHTML = '';

        // 5. Limpiar panel de fases y actividades
        const listaFases = document.getElementById('listaFases');
        if (listaFases) {
          listaFases.innerHTML = `
            <div class="empty-state">
              <i class="bi bi-diagram-3 text-muted" style="font-size:2.2rem;display:block;margin-bottom:8px"></i>
              <p>No hay fases configuradas.<br>Carga un PDF o crea una fase manualmente.</p>
            </div>`;
        }

        const countFases = document.getElementById('countFases');
        if (countFases) countFases.textContent = '0';

        const listaActs = document.getElementById('listaActividades');
        if (listaActs) {
          listaActs.innerHTML = `
            <div class="empty-panel">
              <i class="bi bi-cursor text-muted" style="font-size:2rem;display:block;margin-bottom:8px"></i>
              <p>Selecciona una fase para<br>ver sus actividades</p>
            </div>`;
        }

        const countActs = document.getElementById('countActividades');
        if (countActs) countActs.style.display = 'none';

        const filtroBar = document.getElementById('filtroActividadesBar');
        if (filtroBar) filtroBar.style.display = 'none';

        const btnAct = document.getElementById('btnNuevaActividad');
        if (btnAct) btnAct.style.display = 'none';

        const btnFase = document.getElementById('btnNuevaFase');
        if (btnFase) btnFase.style.display = 'none';

        mostrarMensajeVacio("El proyecto formativo ha sido eliminado. Puedes seleccionar otro o subir un nuevo PDF.");
        cargarProyectos();
      } else {
        alert('Error: ' + d.error);
      }
    })
    .catch(e => alert('Error de conexión: ' + e));
}

/* ── CARGAR FASES (TAB FASES Y ACTIVIDADES) ── */
function cargarFases() {
  const idProyecto = getProyectoId();
  const idFicha = getProgramaId();

  if (!idProyecto && !idFicha) {
    allFases = [];
    currentFaseId = null;
    const lf = document.getElementById('listaFases');
    if (lf) lf.innerHTML = '<div class="empty-state"><p>Selecciona un proyecto formativo para ver sus fases.</p></div>';
    const cf = document.getElementById('countFases');
    if (cf) cf.textContent = '0';
    const la = document.getElementById('listaActividades');
    if (la) la.innerHTML = '<div class="empty-panel"><i class="bi bi-cursor text-muted" style="font-size:2rem;display:block;margin-bottom:8px"></i><p>Selecciona una fase para<br>ver sus actividades</p></div>';
    const ta = document.getElementById('tituloActividades');
    if (ta) ta.innerHTML = '<i class="bi bi-list-task"></i> Actividades';
    const ca = document.getElementById('countActividades');
    if (ca) ca.style.display = 'none';
    const bna = document.getElementById('btnNuevaActividad');
    if (bna) bna.style.display = 'none';
    const fab = document.getElementById('filtroActividadesBar');
    if (fab) fab.style.display = 'none';
    return;
  }

  let query = '';
  if (idProyecto) query = `&id_proyecto=${idProyecto}`;
  else if (idFicha) query = `&id_ficha=${idFicha}`;

  const url = `${API}&subaction=list_fases${query}`;
  const listaFases = document.getElementById('listaFases');
  if (listaFases) {
    listaFases.innerHTML = '<div class="loading"><i class="bi bi-arrow-repeat spin" style="font-size:1.5rem"></i></div>';
  }

  fetch(url)
    .then(r => r.json())
    .then(fases => {
      allFases = fases || [];
      renderFases(allFases, '');
      if (allFases.length > 0) {
        // Auto-seleccionar la primera fase o mantener la activa
        const targetFase = allFases.find(f => f.id_fase == currentFaseId) || allFases[0];
        seleccionarFase(targetFase.id_fase, targetFase.nombre_fase);
      } else {
        currentFaseId = null;
        const la = document.getElementById('listaActividades');
        if (la) la.innerHTML = '<div class="empty-panel"><i class="bi bi-cursor text-muted" style="font-size:2rem;display:block;margin-bottom:8px"></i><p>No hay actividades</p></div>';
        const ta = document.getElementById('tituloActividades');
        if (ta) ta.innerHTML = '<i class="bi bi-list-task"></i> Actividades';
        const ca = document.getElementById('countActividades');
        if (ca) ca.style.display = 'none';
      }
    })
    .catch(() => {
      if (listaFases) {
        listaFases.innerHTML = '<div class="empty-state"><p>Error al cargar fases. Revisa la conexión.</p></div>';
      }
    });
}

function renderFases(fases, query) {
  const idProyecto = getProyectoId();
  const counter = document.getElementById('countFases');
  if (counter) counter.textContent = fases.length;

  if (!fases.length) {
    const lf = document.getElementById('listaFases');
    if (lf) {
      lf.innerHTML = `
        <div class="empty-state">
          <i class="bi bi-diagram-3 text-muted" style="font-size:2.2rem;display:block;margin-bottom:8px"></i>
          <p>${query ? `Sin resultados para "<strong>${escapeHtml(query)}</strong>"` : 'No hay fases configuradas.<br>Carga un PDF o crea una fase manualmente.'}</p>
        </div>`;
    }
    return;
  }

  const lf = document.getElementById('listaFases');
  if (lf) {
    lf.innerHTML = fases.map(f => {
      const isActive = f.id_fase == currentFaseId;
      return `
      <div class="fase-item${isActive ? ' selected' : ''}" id="fase-row-${f.id_fase}" onclick="seleccionarFase(${f.id_fase},'${escapeHtml(f.nombre_fase).replace(/'/g, "\\'")}')">
        <div class="fase-order-badge">${f.orden}</div>
        <div class="fase-info">
          <strong title="${escapeHtml(f.nombre_fase)}">${highlightText(f.nombre_fase, query)}</strong>
          ${f.descripcion ? `<small title="${escapeHtml(f.descripcion)}">${highlightText(f.descripcion, query)}</small>` : ''}
        </div>
        ${idProyecto ? `
        <div class="fase-actions">
          <button class="btn btn-xs btn-ghost btn-icon" title="Editar"
            onclick="event.stopPropagation();editarFase(${f.id_fase},'${escapeHtml(f.nombre_fase).replace(/'/g,"\\'")}',${f.orden},'${escapeHtml(f.descripcion||'').replace(/'/g,"\\'")}')" >
            <i class="bi bi-pencil" style="font-size:0.85rem"></i>
          </button>
          <button class="btn btn-xs btn-danger btn-icon" title="Eliminar"
            onclick="event.stopPropagation();eliminarFase(${f.id_fase},'${escapeHtml(f.nombre_fase).replace(/'/g,"\\'")}')" >
            <i class="bi bi-trash3" style="font-size:0.85rem"></i>
          </button>
        </div>` : ''}
      </div>`;
    }).join('');
  }
}

/* Filtrar fases por texto */
function filtrarFases(query) {
  const btn = document.getElementById('clearFases');
  if (btn) btn.classList.toggle('visible', query.length > 0);
  const q = query.toLowerCase().trim();
  const filtradas = !q ? allFases : allFases.filter(f =>
    f.nombre_fase.toLowerCase().includes(q) || (f.descripcion || '').toLowerCase().includes(q)
  );
  renderFases(filtradas, query);
}

function limpiarBusquedaFases() {
  const input = document.getElementById('searchFases');
  if (input) input.value = '';
  const cf = document.getElementById('clearFases');
  if (cf) cf.classList.remove('visible');
  renderFases(allFases, '');
}

/* ── SELECCIONAR FASE ── */
function seleccionarFase(id, nombre) {
  currentFaseId = id;
  const idProyecto = getProyectoId();

  document.querySelectorAll('.fase-item').forEach(el => el.classList.remove('selected'));
  const row = document.getElementById(`fase-row-${id}`);
  if (row) row.classList.add('selected');

  const titActs = document.getElementById('tituloActividades');
  if (titActs) {
    titActs.innerHTML = `
      <i class="bi bi-list-task"></i>
      ${escapeHtml(nombre)}`;
  }

  const btnNuevaAct = document.getElementById('btnNuevaActividad');
  if (btnNuevaAct) btnNuevaAct.style.display = idProyecto ? 'inline-flex' : 'none';

  const filtroBar = document.getElementById('filtroActividadesBar');
  if (filtroBar) filtroBar.style.display = 'block';

  const countActs = document.getElementById('countActividades');
  if (countActs) countActs.style.display = 'inline-flex';

  const sa = document.getElementById('searchActividades');
  if (sa) { 
    sa.value = ''; 
    const ca = document.getElementById('clearActividades');
    if (ca) ca.classList.remove('visible'); 
  }

  cargarActividades(id, nombre);
}

/* ── CARGAR ACTIVIDADES ── */
function cargarActividades(id, nombre) {
  const idProyecto = getProyectoId();
  const idFicha = getProgramaId();
  let query = '';
  if (idProyecto) query = `&id_proyecto=${idProyecto}`;
  else if (idFicha) query = `&id_ficha=${idFicha}`;

  const url = `${API}&subaction=list_actividades&id_fase=${id}&nombre_fase=${encodeURIComponent(nombre || '')}${query}`;
  const listaActs = document.getElementById('listaActividades');
  if (listaActs) {
    listaActs.innerHTML = '<div class="loading"><i class="bi bi-arrow-repeat spin" style="font-size:1.5rem"></i></div>';
  }

  fetch(url)
    .then(r => r.json())
    .then(acts => {
      allActividades = acts || [];
      renderActividades(allActividades, '');
    })
    .catch(() => {
      if (listaActs) {
        listaActs.innerHTML = '<div class="empty-state"><p>Error al cargar actividades.</p></div>';
      }
    });
}

function renderActividades(acts, query) {
  const counter = document.getElementById('countActividades');
  if (counter) counter.textContent = acts.length;
  const idProyecto = getProyectoId();

  const listaActs = document.getElementById('listaActividades');
  if (!listaActs) return;

  if (!acts.length) {
    listaActs.innerHTML = `
      <div class="empty-state">
        <i class="bi bi-card-checklist text-muted" style="font-size:2.2rem;display:block;margin-bottom:8px"></i>
        <p>${query ? `Sin resultados para "<strong>${escapeHtml(query)}</strong>"` : 'No hay actividades registradas en esta fase.'}</p>
      </div>`;
    return;
  }

  listaActs.innerHTML = acts.map(a => `
    <div class="act-item">
      <div class="act-info">
        <strong>${highlightText(a.nombre, query)}</strong>
        ${a.descripcion ? `<small>${highlightText(a.descripcion, query)}</small>` : ''}
      </div>
      ${idProyecto ? `
      <div class="act-del">
        <button class="btn btn-xs btn-danger btn-icon" title="Eliminar actividad"
          onclick="eliminarActividad(${a.id_actividad},'${escapeHtml(a.nombre).replace(/'/g,"\\'")}')" >
          <i class="bi bi-trash3" style="font-size:0.85rem"></i>
        </button>
      </div>` : ''}
    </div>`).join('');
}

/* Filtrar actividades */
function filtrarActividades(query) {
  const btn = document.getElementById('clearActividades');
  if (btn) btn.classList.toggle('visible', query.length > 0);
  const q = query.toLowerCase().trim();
  const filtradas = !q ? allActividades : allActividades.filter(a =>
    a.nombre.toLowerCase().includes(q) || (a.descripcion || '').toLowerCase().includes(q)
  );
  renderActividades(filtradas, query);
}

function limpiarBusquedaActividades() {
  const input = document.getElementById('searchActividades');
  if (input) input.value = '';
  const ca = document.getElementById('clearActividades');
  if (ca) ca.classList.remove('visible');
  renderActividades(allActividades, '');
}

/* ── CRUD FASES ── */
function openModalFase(id = null, nombre = '', orden = 1, desc = '') {
  document.getElementById('faseId').value = id || '';
  document.getElementById('faseNombre').value = nombre;
  document.getElementById('faseOrden').value = orden;
  document.getElementById('faseDesc').value = desc;
  document.getElementById('modalFaseTitulo').textContent = id ? 'Editar Fase' : 'Nueva Fase';
  openModal('modalFase');
}

function editarFase(id, nombre, orden, desc) {
  openModalFase(id, nombre, orden, desc);
}

function guardarFase() {
  const nombre = document.getElementById('faseNombre').value.trim();
  if (!nombre) { document.getElementById('faseNombre').focus(); return; }

  const id = document.getElementById('faseId').value;
  const idProyecto = getProyectoId();
  const idFicha = getProgramaId();
  const payload = {
    nombre_fase: nombre,
    orden:       +document.getElementById('faseOrden').value || 1,
    descripcion: document.getElementById('faseDesc').value.trim(),
    id_proyecto: idProyecto ? +idProyecto : null,
    id_ficha:    idFicha ? +idFicha : null,
  };
  if (id) payload.id_fase = +id;

  const btnGuardar = document.querySelector('#modalFase .btn-primary');
  if (btnGuardar) { btnGuardar.disabled = true; btnGuardar.textContent = 'Guardando…'; }

  fetch(`${API}&subaction=${id ? 'update_fase' : 'create_fase'}`, {
    method: 'POST', body: JSON.stringify(payload)
  })
    .then(r => r.json())
    .then(() => { closeModal('modalFase'); cargarFases(); })
    .finally(() => { 
      if (btnGuardar) { 
        btnGuardar.disabled = false; 
        btnGuardar.innerHTML = '<i class="bi bi-check-lg"></i> Guardar'; 
      } 
    });
}

function eliminarFase(id, nombre) {
  if (!confirm(`¿Eliminar la fase "${nombre}" y todas sus actividades?\nEsta acción no se puede deshacer.`)) return;
  fetch(`${API}&subaction=delete_fase`, { method: 'POST', body: JSON.stringify({ id_fase: id }) })
    .then(() => {
      if (currentFaseId == id) {
        currentFaseId = null;
        const la = document.getElementById('listaActividades');
        if (la) la.innerHTML = '<div class="empty-panel"><i class="bi bi-cursor text-muted" style="font-size:2rem;display:block;margin-bottom:8px"></i><p>Selecciona una fase para ver sus actividades</p></div>';
        const bna = document.getElementById('btnNuevaActividad');
        if (bna) bna.style.display = 'none';
        const fab = document.getElementById('filtroActividadesBar');
        if (fab) fab.style.display = 'none';
        const ca = document.getElementById('countActividades');
        if (ca) ca.style.display = 'none';
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

  const idProyecto = getProyectoId();
  const idFicha = getProgramaId();
  const payload = {
    nombre,
    descripcion: document.getElementById('actDesc').value.trim(),
    id_fase:     currentFaseId,
    id_proyecto: idProyecto ? +idProyecto : null,
    id_ficha:    idFicha ? +idFicha : null,
  };

  const btnGuardar = document.querySelector('#modalActividad .btn-primary');
  if (btnGuardar) { btnGuardar.disabled = true; btnGuardar.textContent = 'Guardando…'; }

  fetch(`${API}&subaction=create_actividad`, { method: 'POST', body: JSON.stringify(payload) })
    .then(r => r.json())
    .then(() => { closeModal('modalActividad'); cargarActividades(currentFaseId); })
    .finally(() => { 
      if (btnGuardar) { 
        btnGuardar.disabled = false; 
        btnGuardar.innerHTML = '<i class="bi bi-check-lg"></i> Guardar'; 
      } 
    });
}

function eliminarActividad(id, nombre) {
  if (!confirm(`¿Eliminar la actividad "${nombre}"?`)) return;
  fetch(`${API}&subaction=delete_actividad`, { method: 'POST', body: JSON.stringify({ id_actividad: id }) })
    .then(() => cargarActividades(currentFaseId));
}

/* ── Sincronizar selectores y pestañas ── */
document.addEventListener('DOMContentLoaded', () => {
  // Pestaña de Fases y Actividades
  const tabBtnFases = document.getElementById('tabBtnFases');
  if (tabBtnFases) {
    tabBtnFases.addEventListener('click', () => {
      const idProy = getProyectoId();
      if (!idProy) {
        cargarFases();
      } else if (!allFases.length) {
        cargarFases();
      } else if (!currentFaseId && allFases.length > 0) {
        seleccionarFase(allFases[0].id_fase, allFases[0].nombre_fase);
      }
    });
  }

  // Pestaña de Proyectos
  const tabBtnProyectos = document.getElementById('tabBtnProyectos');
  if (tabBtnProyectos) {
    tabBtnProyectos.addEventListener('click', () => {
      const idProyecto = getProyectoId();
      if (idProyecto) {
        seleccionarProyecto(idProyecto);
      }
    });
  }
});

/* ── Atajos de teclado en modales ── */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-bg.open').forEach(m => m.classList.remove('open'));
  }
});

/* ── Inicializar ── */
cargarProyectos();
