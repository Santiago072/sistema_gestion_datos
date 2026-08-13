<?php
require_once __DIR__ . '/../../config/url_config.php';
?>
  </main><!-- /.main-content -->
</div><!-- /.layout -->

<script>
// Inyectar BASE_URL desde PHP
window.BASE_URL = '<?= BASE_URL ?? "/" ?>';

// Global utility functions for badges
window.badgeJuicio = function(t) {
  if (t==='Aprobado')    return `<span class="badge badge-green">✓ Aprobado</span>`;
  if (t==='Por evaluar') return `<span class="badge badge-orange">⏳ Por evaluar</span>`;
  return `<span class="badge badge-red">✗ No aprobado</span>`;
};

window.badgeEstado = function(e) {
  if (e==='En formación') return `<span class="badge badge-cyan">${e}</span>`;
  if (e==='Retirado')     return `<span class="badge badge-red">${e}</span>`;
  if (e==='Egresado')     return `<span class="badge badge-green">${e}</span>`;
  return `<span class="badge badge-gray">${e}</span>`;
};


// Count-up animation for KPI values
function animateCount(el) {
  const target = parseFloat(el.dataset.target || el.textContent) || 0;
  const duration = 1200;
  const isFloat = el.dataset.float === '1';
  const start = performance.now();
  function step(now) {
    const progress = Math.min((now - start) / duration, 1);
    const ease = 1 - Math.pow(1 - progress, 3);
    const value = target * ease;
    el.textContent = isFloat ? value.toFixed(1) + '%' : Math.round(value).toLocaleString('es-CO');
    if (progress < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}
document.querySelectorAll('.kpi-value[data-target]').forEach(animateCount);

// Global Chart.js defaults (dark theme)
if (typeof Chart !== 'undefined') {
  Chart.defaults.color = '#7a8fa6';
  Chart.defaults.font.family = "'Inter', sans-serif";
  Chart.defaults.font.size = 12;
  Chart.defaults.plugins.legend.labels.boxWidth = 12;
  Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(13,21,38,0.95)';
  Chart.defaults.plugins.tooltip.borderColor = 'rgba(57,169,0,0.3)';
  Chart.defaults.plugins.tooltip.borderWidth = 1;
  Chart.defaults.plugins.tooltip.titleColor = '#e8f0fe';
  Chart.defaults.plugins.tooltip.bodyColor = '#7a8fa6';
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.scale.grid.color = 'rgba(255,255,255,0.05)';
  Chart.defaults.scale.ticks.color = '#7a8fa6';
}

// Tabs
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const group = btn.closest('[data-tabs]') || btn.closest('.card');
    group.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    const pane = document.getElementById(btn.dataset.tab);
    if (pane) pane.classList.add('active');
  });
});
</script>
</body>
</html>
