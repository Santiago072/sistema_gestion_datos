<?php 
require_once dirname(__DIR__, 3) . '/config/url_config.php';
require_once dirname(__DIR__) . '/layouts/header.php'; 
require_once dirname(__DIR__, 3) . '/config/database.php';
$db = getDB();

// Programas con conteo de aprendices
$programas = $db->query("
    SELECT p.id_ficha, p.nombre, COUNT(a.documento) AS total_aprendices
    FROM programas p
    LEFT JOIN aprendices a ON p.id_ficha = a.id_ficha
    GROUP BY p.id_ficha, p.nombre
    ORDER BY p.nombre
")->fetchAll();
?>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECCIÓN 1: Eliminar ficha completa                     -->
<!-- ══════════════════════════════════════════════════════ -->
<div class="card fade-in mb-24">
  <div class="section-header">
    <div class="section-title">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
      Eliminar Ficha Completa
    </div>
  </div>

  <p style="color:var(--text-muted);margin-bottom:20px;">
    Elimina un programa formativo completo junto con <strong>todos</strong> sus aprendices, competencias y juicios.
    <strong style="color:#ef4444;"> Esta acción es irreversible.</strong>
  </p>

  <div class="table-wrap">
    <table class="table-compact">
      <thead>
        <tr>
          <th style="width:15%">Ficha</th>
          <th style="width:50%">Programa</th>
          <th style="width:20%">Aprendices</th>
          <th style="width:15%;text-align:right;">Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($programas)): ?>
          <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted);">No hay programas registrados en el sistema.</td></tr>
        <?php else: ?>
          <?php foreach($programas as $p): ?>
            <tr>
              <td><strong><?= htmlspecialchars($p['id_ficha']) ?></strong></td>
              <td><?= htmlspecialchars($p['nombre']) ?></td>
              <td><span class="badge badge-cyan"><?= $p['total_aprendices'] ?></span></td>
              <td style="text-align:right;">
                <button onclick="eliminarFicha('<?= htmlspecialchars($p['id_ficha']) ?>', '<?= htmlspecialchars(addslashes($p['nombre'])) ?>')"
                        class="btn" style="background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.3);padding:6px 14px;font-size:0.85rem;">
                  🗑 Eliminar Ficha
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SECCIÓN 2: Eliminar aprendiz específico               -->
<!-- ══════════════════════════════════════════════════════ -->
<div class="card fade-in mb-24">
  <div class="section-header">
    <div class="section-title">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/></svg>
      Eliminar Aprendiz Específico
    </div>
  </div>

  <p style="color:var(--text-muted);margin-bottom:20px;">
    Busca un aprendiz por su número de documento y elimínalo de una ficha específica, junto con todos sus juicios y competencias registradas.
    <strong style="color:#ef4444;"> Esta acción es irreversible.</strong>
  </p>

  <!-- Estilos del buscador avanzado -->
  <style>
  .modern-search-wrapper { position:relative; width:100%; }
  .modern-search-wrapper svg.search-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:var(--text-dim); pointer-events:none; }
  .modern-search-wrapper input, .modern-search-wrapper select { padding-left:38px !important; width:100%; transition:all 0.2s ease; border:1px solid var(--card-border); border-radius:8px; background:var(--bg); color:var(--text); padding-top:10px; padding-bottom:10px; font-size:.95rem; }
  .modern-search-wrapper input:focus, .modern-search-wrapper select:focus { border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-glow); outline:none; }
  .modern-search-wrapper .spinner { position:absolute; right:12px; top:50%; transform:translateY(-50%); width:18px; height:18px; border:2px solid var(--card-border); border-top-color:var(--primary); border-radius:50%; animation:spin 1s linear infinite; display:none; }
  @keyframes spin { to { transform:translateY(-50%) rotate(360deg); } }
  </style>

  <!-- Buscador -->
  <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:16px;background:var(--bg2);padding:20px;border-radius:12px;margin-bottom:20px;border:1px solid var(--card-border);">
    
    <div class="form-group" style="margin:0;">
      <label style="font-weight:600;margin-bottom:6px;display:block;color:var(--text);font-size:.9rem;">Filtrar por ficha (opcional)</label>
      <div class="modern-search-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        <select id="filtroFicha" onchange="buscarAprendiz()">
          <option value="">Todos los programas</option>
          <?php foreach($programas as $p): ?>
            <option value="<?= htmlspecialchars($p['id_ficha']) ?>">
              <?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['id_ficha']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-group" style="margin:0;">
      <label style="font-weight:600;margin-bottom:6px;display:block;color:var(--text);font-size:.9rem;">Número de documento</label>
      <div class="modern-search-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" id="buscarDoc" placeholder="Ej: 1075789456" oninput="buscarAprendiz()">
        <div class="spinner" id="spinDocSearch"></div>
      </div>
    </div>

    <div class="form-group" style="margin:0;display:flex;flex-direction:column;justify-content:flex-end">
      <div style="display:flex;gap:8px;height:42px;">
        <button class="btn btn-secondary" onclick="limpiarBusqueda()" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;height:100%;margin:0;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Limpiar
        </button>
      </div>
    </div>

  </div>

  <!-- Resultados de búsqueda -->
  <div id="resultadosAprendiz"></div>
</div>

<script>
// ─── Eliminar ficha completa ───────────────────────────────
function eliminarFicha(id_ficha, nombre) {
    if (!confirm('¿Estás SEGURO de eliminar permanentemente el programa "' + nombre + '" (Ficha: ' + id_ficha + ')?\n\nSe borrarán TODOS sus aprendices, competencias y resultados. ESTO NO SE PUEDE DESHACER.')) return;

    const fd = new FormData();
    fd.append('id_ficha', id_ficha);

    fetch((window.BASE_URL || '') + 'index.php?module=eliminacion&action=eliminar_programa', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert('Programa eliminado correctamente.');
                window.location.reload();
            } else {
                alert('Error: ' + (d.message || 'Error desconocido.'));
            }
        })
        .catch(() => alert('Error de conexión al servidor.'));
}

// ─── Buscar aprendiz ───────────────────────────────────────
let searchTimer = null;
function buscarAprendiz() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(_ejecutarBusqueda, 350);
}

function limpiarBusqueda() {
    document.getElementById('buscarDoc').value = '';
    document.getElementById('filtroFicha').value = '';
    document.getElementById('resultadosAprendiz').innerHTML = '';
    document.getElementById('spinDocSearch').style.display = 'none';
}

function _ejecutarBusqueda() {
    const doc     = document.getElementById('buscarDoc').value.trim();
    const ficha   = document.getElementById('filtroFicha').value;
    const cont    = document.getElementById('resultadosAprendiz');
    const spinner = document.getElementById('spinDocSearch');

    if (doc.length < 3 && !ficha) {
        cont.innerHTML = '';
        spinner.style.display = 'none';
        return;
    }

    if(doc.length >= 3) spinner.style.display = 'block';
    cont.innerHTML = '<p style="color:var(--text-muted);padding:12px 0;">Buscando...</p>';

    const params = new URLSearchParams({ documento: doc, id_ficha: ficha });
    fetch((window.BASE_URL || '') + 'index.php?module=consulta&action=buscar&' + params)
        .then(r => r.json())
        .then(data => {
            spinner.style.display = 'none';
            if (!data.length) {
                cont.innerHTML = '<p style="color:var(--text-muted);padding:12px 0;">No se encontraron aprendices con esos criterios.</p>';
                return;
            }

            let html = '<div class="table-wrap"><table class="table-compact"><thead><tr>'
                + '<th>Documento</th><th>Nombre</th><th>Ficha</th><th>Programa</th><th style="text-align:right">Acción</th>'
                + '</tr></thead><tbody>';

            data.forEach(a => {
                html += `<tr>
                    <td><code style="color:var(--primary)">${a.documento}</code></td>
                    <td>${a.nombres} ${a.apellidos}</td>
                    <td><span class="badge badge-cyan">${a.id_ficha}</span></td>
                    <td style="font-size:.82rem">${(a.programa||'—').substring(0,55)}${(a.programa||'').length>55?'...':''}</td>
                    <td style="text-align:right;">
                        <button onclick="eliminarAprendiz('${a.documento}','${a.id_ficha}','${(a.nombres+' '+a.apellidos).replace(/'/g,"\\'")}' )"
                                class="btn" style="background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.3);padding:5px 12px;font-size:.82rem;">
                            🗑 Eliminar
                        </button>
                    </td>
                </tr>`;
            });

            html += '</tbody></table></div>';
            cont.innerHTML = html;
        })
        .catch(() => { cont.innerHTML = '<p style="color:#ef4444">Error al conectar con el servidor.</p>'; });
}

// ─── Eliminar aprendiz específico ─────────────────────────
function eliminarAprendiz(documento, id_ficha, nombre) {
    if (!confirm(`¿Eliminar al aprendiz "${nombre}" (Doc: ${documento}) de la ficha ${id_ficha}?\n\nSe borrarán TODOS sus juicios y competencias registradas en esta ficha. ESTO NO SE PUEDE DESHACER.`)) return;

    const fd = new FormData();
    fd.append('documento', documento);
    fd.append('id_ficha', id_ficha);

    fetch((window.BASE_URL || '') + 'index.php?module=eliminacion&action=eliminar_aprendiz', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert('Aprendiz eliminado correctamente.');
                _ejecutarBusqueda(); // Refrescar resultados
            } else {
                alert('Error: ' + (d.message || 'Error desconocido.'));
            }
        })
        .catch(() => alert('Error de conexión al servidor.'));
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
