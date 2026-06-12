# Registro de Cambios (Changelog)

Historial de mejoras y refactorizaciones aplicadas al sistema durante el desarrollo.

---

## 2026-06-12 — Sprint de Mejoras Arquitectónicas y UX

### 🏗 Arquitectura y Rendimiento

#### Punto 1 — Patrón Adapter para SimpleXLSX
- Creación de `SimpleXLSXAdapter` que envuelve la librería `SimpleXLSX`.
- El resto del sistema ya no depende directamente de la librería; si se cambia la librería, solo se modifica el adaptador.

#### Punto 2 — Procesamiento Asíncrono de Excel (Cola MySQL + Worker)
- Creación de la tabla `trabajos_importacion` como sistema de colas.
- `worker.php`: proceso PHP CLI que consume la cola en segundo plano.
- PHP invoca el worker automáticamente (`start /B`) tras guardar el archivo.
- El frontend hace polling al estado del job sin bloquear la UI.

#### Punto 3 — Auditoría de Errores y Transacciones
- Creación de la tabla `logs_importacion` para registrar exactamente qué filas del Excel fallaron y por qué.
- Implementación de transacciones PDO (`BEGIN` / `COMMIT` / `ROLLBACK`) en todas las inserciones masivas.

#### Punto 4 — Feedback en Tiempo Real (UX de Importación)
- Indicador de progreso en la UI durante la importación asíncrona.
- El usuario ve el avance del worker en tiempo real mediante polling.

#### Punto 5 — Micro-API Flask para Extracción de PDF
- **Eliminado `shell_exec()`**: la comunicación con Python ya no usa procesos del SO.
- Nuevo archivo `controllers/scripts/app.py`: servidor Flask que expone `POST /extract-pdf` y `GET /health`.
- `upload_pdf_fases.php` ahora se comunica con Python via `curl` HTTP.
- **Auto-inicio transparente**: PHP detecta si Flask está apagado y lo inicia automáticamente (`popen` + `start /B`). El usuario no ejecuta nada manualmente.
- Ventajas: portable (sin rutas hardcodeadas), timeout configurable, errores HTTP estándar.

---

### ♻ Refactorización Módulo Fases Formativas

#### Servicios
- Creación de `Services/Import/FasesImportService.php`: centraliza la lógica de inserción masiva de fases, actividades y relaciones desde PDF.
- **Bulk Insert en lotes de 500**: eliminados los INSERT uno por uno dentro de bucles `foreach`.

#### Modelos
- Creación de `DashboardFasesRepository.php`: separa las consultas estadísticas complejas del modelo transaccional `FasesModel`, evitando que este crezca descontroladamente.

#### JavaScript
- Extracción de todo el JS embebido de `fases_proyecto.php` hacia `/assets/js/fases.js` y `/assets/js/pdf_upload.js`.
- Separación de responsabilidades: HTML solo estructura, JS maneja la lógica.

---

### 🎨 UI/UX y Design System

#### Design System Centralizado (`assets/css/styles.css`)
- Reescritura total del CSS como **design system único** con:
  - Tokens CSS: colores, radios, sombras, transiciones (modo oscuro + modo claro).
  - Componentes semánticos reutilizables: `.kpi-card`, `.fase-item`, `.act-item`, `.card`, `.badge`, `.btn`, `.modal`, `.table-wrap`, `.search-box`, `.prog-bar`, `.filter-bar`, `.drop-zone`, `.empty-state`, `.empty-panel`, etc.
  - Sin duplicados: una sola fuente de verdad para el diseño.

#### Media Queries Completas (Responsive Design)
- Sistema de breakpoints de 6 niveles añadido al final de `styles.css`:
  - `xs` (≤ 479px): modales tipo bottom-sheet, 1 columna KPI, tablas min-width 300px.
  - `sm` (≤ 639px): 2 columnas KPI, charts reducidos, modal 98% ancho.
  - `md` (≤ 899px): sidebar oculto con overlay, todos los grids a 1 columna, tablas con scroll horizontal.
  - `lg` (≤ 1199px): sidebar 230px, grids ajustados, 3 KPIs por fila.
  - `2xl` (≥ 1400px): 6 KPIs por fila, padding mayor.
  - Landscape móvil (altura < 500px): sidebar scrolleable, charts compactos.
  - Print: oculta sidebar, botones y tabs, fondo blanco.

#### Vista Fases (`fases_proyecto.php`)
- Layout `grid-1-2` limpio: fases en columna izquierda, actividades en columna derecha.
- Barra de programa reutilizable (`.prog-bar`).
- Tabs con iconos SVG.
- Modales con `form-row` para campos agrupados.

#### Búsqueda y Filtros en Tiempo Real (Fases)
- `fases.js` implementa:
  - Cache local (`allFases[]`, `allActividades[]`): filtros instantáneos sin peticiones extra.
  - Resaltado con `<mark class="highlight">` del término buscado.
  - Botón ✕ para limpiar búsqueda.
  - Contadores `list-count` de resultados visibles.
  - Estados de carga en botones (disabled + texto "Guardando…").
  - Atajo `Escape` para cerrar modales.

#### Dashboard Principal — KPIs
- Eliminada tarjeta "No Aprobados" del dashboard.
- KPIs activos: Aprendices Activos, Juicios Aprobados, Por Evaluar, Programas, Retirados, Trasladados.
- Grid ajustado a 3 columnas base (2 filas de 3 = layout simétrico sin huecos).
- `DashboardModel.php` extendido con `total_competencias` y `total_resultados` (aunque no mostrados en KPIs actuales, disponibles para uso futuro).
