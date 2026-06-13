# Módulos del Sistema

Este documento lista y describe los módulos principales que componen el sistema de gestión de datos SENA — Juicios Evaluativos.

---

## 1. Aprendices
Módulo central para la gestión de aprendices. Permite CRUD (registro, consulta, actualización, eliminación) y asociar aprendices a sus programas de formación.

- **Carga masiva:** Los aprendices se importan desde archivos Excel mediante el módulo de Carga Masiva (procesamiento asíncrono vía cola MySQL + Worker PHP).
- **Eliminación masiva:** Permite eliminar grupos de aprendices de forma controlada.

## 2. Programas
Gestiona el catálogo de programas de formación SENA. Define los programas, fichas y su asociación con aprendices.

- Un programa puede tener varias **fichas**.
- La ficha es el filtro global principal del dashboard.

## 3. Fases Formativas (`fases_proyecto.php`)
Administra los Proyectos Formativos (GFPI-F-016) y sus fases: Análisis, Planeación, Ejecución, Evaluación.

### Funcionalidades clave
- **Unificación de Proyectos:** Una pestaña de resumen global por programa que integra totales y desgloses.
- **CRUD manual** de Fases y Actividades con modales.
- **Carga desde PDF:** El usuario sube el PDF del Proyecto Formativo. El sistema lo procesa automáticamente mediante la micro-API Flask (Python + pdfplumber).
- **Búsqueda en tiempo real** en la lista de fases y actividades, con resaltado del término buscado.
- **Filtro por programa:** El selector de ficha filtra fases, actividades y proyectos.

### Arquitectura interna
```
fases_proyecto.php  (Vista HTML con tabs)
    └── assets/js/fases.js         (Lógica JS: filtros, CRUD, visualización del proyecto)
    └── assets/js/pdf_upload.js    (Lógica JS: drag & drop, previsualización)

upload_pdf_fases.php  (Controlador)
    └── checkFlaskApi() / startFlaskApi()   (Auto-inicia Flask si está apagado)
    └── HTTP POST → Flask app.py             (Delega extracción a Python)
    └── FasesImportService.php               (Bulk Insert a MySQL)
```

## 4. Juicios Evaluativos
Gestiona los resultados y juicios que reciben los aprendices en cada competencia y resultado de aprendizaje.

- **Tipos de juicio:** `Aprobado`, `Por evaluar`, `No aprobado`.
- **Importación desde Excel** de forma síncrona.
- Cada juicio se asocia a: Aprendiz → Competencia → Resultado de Aprendizaje → Funcionario Evaluador.

## 5. Dashboard Principal (`dashboard.php`)

Dashboard analítico con los siguientes componentes:

### KPIs (tarjetas superiores)
| Tarjeta | Dato | Color |
|---------|------|-------|
| Aprendices Activos | `estado = 'En formación'` | Verde |
| Juicios Aprobados | `tipo_juicio = 'Aprobado'` | Verde |
| Por Evaluar | `tipo_juicio = 'Por evaluar'` | Naranja |
| Programas | Total de programas | Morado |
| Retirados | `estado = 'Retirado'` | Amarillo |
| Trasladados | `estado = 'Trasladado'` | Amarillo |

### Gráficas
- **Aprendices por Formación:** Barra apilada (En formación / Retirados / Trasladados) por programa.
- **Comparativa de Juicios:** Barra horizontal por instructor (Aprobados / Por evaluar / No aprobados).
- **Curva de Supervivencia:** Línea multi-programa que muestra cuántos aprendices quedan activos competencia a competencia.

### Filtro Avanzado
- Filtro en tiempo real por: Documento, Competencia, Resultado de Aprendizaje, Estado del aprendiz, Tipo de juicio.
- Resaltado de términos buscados en los resultados.
- Paginación del lado del servidor (controlador `filtro_avanzado.php`).
- Exportación a CSV.
- Chips de filtros activos con opción de eliminar individualmente.

### Auditoría por Funcionario
Tabla que muestra la actividad de cada instructor: total de juicios registrados, desglose por tipo, y fechas de primer/último registro.

## 6. Dashboard de Fases (`dashboard_fases.php`)
Vista de cumplimiento de fases por programa. Muestra el porcentaje de avance de cada fase formativa.

- Consume `DashboardFasesRepository` (separado del `FasesModel` para no sobrecargar el modelo transaccional).

## 7. Carga Masiva (`carga_masiva.php`)
Módulo para la importación de aprendices y/o juicios evaluativos desde archivos Excel (.xlsx).

- **Procesamiento síncrono:** el archivo se procesa inmediatamente y se inserta en base de datos.
- **SimpleXLSX:** Abstracción para leer archivos Excel de forma sencilla.