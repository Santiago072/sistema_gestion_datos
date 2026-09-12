# 📜 Historial de Cambios (Changelog)

Todos los cambios notables realizados en el **Sistema de Gestión de Datos** serán documentados en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/) y este proyecto se adhiere a [Semantic Versioning](https://semver.org/lang/es/).

---

## [1.4.0] — 2026-09-12

### ✨ Añadido
- **Gestión Independiente de Proyectos Formativos (Modelo v4):** Los proyectos curriculares ahora se almacenan de forma desacoplada y global, permitiendo que existan sin depender de una ficha creada de antemano.
- **Vinculación Flexible de Fichas:** Módulo para asociar una o múltiples fichas de formación a un mismo proyecto formativo matriz, reutilizando la malla curricular sin redundancia de datos.
- **Detección Dinámica de Columnas en PDF:** Adaptación automática a variaciones de formato y orden de columnas en las tablas del formato oficial SENA GFPI-F-016.
- **Diccionario Exhaustivo de Datos:** Creación de `docs/DICCIONARIO_TABLAS.md` documentando todas las tablas, relaciones, campos y ciclos de vida de la información.

### ⚡ Optimizado
- **Deduplicación Estricta en `extract_pdf.py`:** Generación de clave única compuesta `(fase, act_id, res_ident, comp_ident)` que elimina al 100% registros duplicados en proyectos formativos complejos (como Producción Ganadera).
- **Filtrado de Celdas y Filas Fantasma:** Detección de celdas con placeholders visuales (`–`, `—`, `.`, `_`) y omisión automática de renglones vacíos generados por celdas combinadas de `pdfplumber`.
- **Propagación de Celdas (`fill_down`):** Mejora en el arrastre de fases y actividades para garantizar que ningún resultado quede desasociado.
- **Concatenación Multilínea:** Unión de nombres largos y descripciones formativas que abarcan múltiples líneas en el PDF original.

### 🔒 Seguridad y Configuración
- **Exclusión de Archivos Temporales:** Actualización de `.gitignore` para omitir caché de compilación de Python (`__pycache__/`, `*.pyc`) y carpetas temporales de subida (`tmp_uploads/`).

---

## [1.3.0] — 2026-08-25

### ✨ Añadido
- **Curva de Retiros Escalonada (`stepped: 'before'`):** Gráfico interactivo con Chart.js que refleja la retención estudiantil acumulada a lo largo del tiempo.
- **Cálculo de Fechas Reales de Retiro:** Identificación precisa del momento de deserción o traslado según el último juicio evaluativo registrado en Sofia Plus.
- **Tabla Detallada de 7 Columnas:** Relación entre programa, competencia, fase curricular, fecha de salida, cantidad de aprendices retirados e instructor evaluador.

### ⚡ Optimizado
- **Aislamiento de Docentes por Ficha:** Cada competencia muestra únicamente los instructores que efectivamente evaluaron aprendices en esa ficha específica.

---

## [1.2.0] — 2026-08-10

### ✨ Añadido
- **Línea de Tiempo de Fases Formativas:** Visualización del porcentaje de cumplimiento por fase (`Análisis`, `Planeación`, `Ejecución`, `Evaluación`).
- **Cruce Automático sin N/A:** Asociación directa entre competencias evaluadas en Sofia Plus y resultados de aprendizaje del proyecto formativo mediante `codigo_resultado`.

### ⚡ Optimizado
- **Carga Masiva por Lotes (Batching de 500 filas):** Procesamiento de archivos `.xlsx`, `.xls` y `.csv` de gran tamaño sin superar los límites de memoria de PHP.

---

## [1.1.0] — 2026-07-28

### ✨ Añadido
- **Auditoría de Instructores:** Consulta de juicios emitidos, primer y último registro evaluativo por funcionario.
- **Filtro Avanzado Multicriterio:** Búsqueda combinada por documento, competencia, resultado y estado.
- **Exportación a CSV Estructurado:** Generación de reportes tabulares con codificación UTF-8 BOM para apertura nativa en Microsoft Excel.
- **Módulo de Eliminación Segura:** Búsqueda rápida de aprendices con confirmación modal y borrado en cascada.

---

## [1.0.0] — 2026-07-01

### ✨ Lanzamiento Inicial
- Arquitectura MVC profesional en PHP 8.2 nativo.
- Conexión segura a base de datos MariaDB con PDO y sentencias preparadas.
- Importación inicial de reportes de juicios evaluativos de Sofia Plus.
- Dashboard de métricas globales (Activos, Aprobados, Pendientes, Retirados).
