# Flujo de Negocio

Este documento detalla el ciclo de vida de los datos dentro del sistema, desde su ingreso hasta su visualización.

---

## 1. Importación de Aprendices / Juicios desde Excel (Asíncrono)

```
Usuario → Selecciona archivo .xlsx
       → POST carga_masiva.php
       → Archivo guardado en disco (tmp/)
       → INSERT trabajos_importacion (estado='pendiente')
       → PHP lanza worker.php en segundo plano (start /B)
       → Frontend recibe job_id y hace polling al estado
       → worker.php procesa en lotes via SimpleXLSXAdapter
       → Motor de Validación (duplicados, campos obligatorios, FK)
       → Bulk INSERT en aprendices / juicios / competencias / resultados
       → logs_importacion registra errores por fila
       → Estado del job → 'completado' o 'error_parcial'
       → Frontend muestra resumen final
```

**Tablas involucradas:** `trabajos_importacion`, `logs_importacion`, `aprendices`, `programas`, `juicios`, `competencias`, `resultados`.

---

## 2. Importación de Fases Formativas desde PDF (Micro-API Python)

```
Usuario → Sube PDF del Proyecto Formativo (GFPI-F-016)
       → POST upload_pdf_fases.php
       → PHP verifica GET http://127.0.0.1:5000/health
       → (Si Flask no está activo) PHP lo inicia con start /B app.py
       → PHP espera hasta 5s a que Flask responda /health
       → PHP envía PDF vía curl POST → http://127.0.0.1:5000/extract-pdf
       → Flask (app.py) recibe el archivo y llama extract_table_from_pdf()
       → pdfplumber detecta tablas, fases canónicas SENA, fill_down de celdas combinadas
       → Flask devuelve JSON: { fases, actividades, registros, resumen }
       → PHP recibe el JSON y llama FasesImportService
       → Bulk INSERT de fases / actividades / relaciones (lotes de 500)
       → Frontend recibe { ok, datos_extraidos, datos_mapeados }
       → Vista muestra previsualización de datos antes de confirmar
```

**Archivos clave:** `upload_pdf_fases.php`, `controllers/scripts/app.py`, `controllers/scripts/extract_pdf.py`, `Services/Import/FasesImportService.php`.

---

## 3. Consulta y Dashboard

```
Usuario → Selecciona Programa/Ficha en el selector global
       → JS dispara cargarDashboard()
       → Fetch paralelo a múltiples endpoints:
           ├── /controllers/dashboard_kpis.php       → 6 KPIs (contadores)
           ├── /controllers/aprendices_formacion.php → Barra apilada
           ├── /controllers/comparativa_juicios.php  → Barra horizontal instructores
           └── /controllers/retirados_competencia.php → Curva de supervivencia
       → Gráficas se renderizan con Chart.js
       → KPIs se animan con counter easing (1.2s)
```

---

## 4. Filtro Avanzado de Juicios

```
Usuario → Escribe en los campos (Documento, Competencia, Resultado)
       → Debounce 300ms → aplicarFiltro()
       → Fetch → /controllers/filtro_avanzado.php?{params}&page=N
       → PHP ejecuta query paginada (LIMIT/OFFSET)
       → Resultado renderizado con resaltado de términos (<mark>)
       → Chips de filtros activos en la parte superior de la tabla
       → Botón "Exportar CSV" actualiza su href con los mismos params
```

---

## 5. Gestión de Fases (CRUD)

```
Usuario → Selecciona ficha en el selector
       → JS llama cargarFases() → GET fases_crud.php?action=list_fases
       → Cache local allFases[] → filtrarFases() opera sin peticiones extra
       → Click en fase → seleccionarFase() → cargarActividades()
       → Cache local allActividades[] → filtrarActividades() instantáneo
       → Modal "Nueva Fase" / "Editar Fase" → guardarFase() → POST
       → Modal "Nueva Actividad" → guardarActividad() → POST
       → Botón Eliminar → eliminarFase() / eliminarActividad() → POST
       → Cada mutación dispara recarga del cache correspondiente
```