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
Administra las fases del Proyecto Formativo SENA (GFPI-F-016): Análisis, Planeación, Ejecución, Evaluación.

### Funcionalidades clave
- **CRUD manual** de Fases y Actividades con modales.
- **Carga desde PDF:** El usuario sube el PDF del Proyecto Formativo. El sistema lo procesa automáticamente mediante la micro-API Flask (Python + pdfplumber).
- **Búsqueda en tiempo real** en la lista de fases y actividades, con resaltado del término buscado.
- **Filtro por programa:** El selector de ficha filtra tanto fases como actividades.
- **Bulk Insert:** Las importaciones masivas desde PDF usan `FasesImportService` con lotes de 500 registros para máxima eficiencia.

### Arquitectura interna
```
fases_proyecto.php  (Vista HTML)
    └── assets/js/fases.js         (Lógica JS: filtros, CRUD, modales)
    └── assets/js/pdf_upload.js    (Lógica JS: drag & drop, previsualización)

upload_pdf_fases.php  (Controlador)
    └── checkFlaskApi() / startFlaskApi()   (Auto-inicia Flask si está apagado)
    └── HTTP POST → Flask app.py             (Delega extracción a Python)
    └── FasesImportService.php               (Bulk Insert a MySQL)

controllers/scripts/app.py       (Flask Micro-API)
    └── extract_pdf.py            (pdfplumber: extrae tablas del PDF)
```

## 4. Juicios Evaluativos
Gestiona los resultados y juicios que reciben los aprendices en cada competencia y resultado de aprendizaje.

- **Tipos de juicio:** `Aprobado`, `Por evaluar`, `No aprobado`.
- **Importación desde Excel** de forma asíncrona (cola + worker).
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

- **Procesamiento asíncrono:** el archivo se encola en MySQL (`trabajos_importacion`) y un Auto-Worker PHP lo procesa en segundo plano.
- **Patrón Adapter:** `SimpleXLSXAdapter` abstrae la dependencia de `SimpleXLSX`.
- **Auditoría de errores:** La tabla `logs_importacion` guarda un historial exacto de cada fila que falló y el motivo.
- **Bulk Insert:** Inserciones en lotes para máxima eficiencia.