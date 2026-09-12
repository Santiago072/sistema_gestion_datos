# 📋 Especificación de Requisitos de Software (SRS)

## 📌 1. Introducción y Propósito

El **Sistema de Gestión de Datos** es una solución integral diseñada para optimizar el seguimiento académico, análisis de supervivencia estudiantil, auditoría docente y monitoreo del avance formativo de aprendices en el marco institucional del SENA.

El sistema unifica dos fuentes fundamentales de información:
1. **Reportes de Juicios Evaluativos de Sofia Plus (Excel/CSV):** Estados de formación, competencias evaluadas, juicios (Aprobado, No aprobado, Por evaluar) e instructores.
2. **Proyectos Formativos GFPI-F-016 (PDF):** Estructura curricular oficial del programa dividida en Fases (`ANÁLISIS`, `PLANEACIÓN`, `EJECUCIÓN`, `EVALUACIÓN`) y sus Actividades de Proyecto.

---

## 🎯 2. Requisitos Funcionales (RF)

### 📊 Módulo 1: Dashboard y Curva de Supervivencia
* **RF-01 (KPIs Globales):** El sistema debe calcular en tiempo real el total de aprendices activos, juicios aprobados, juicios por evaluar, programas registrados, aprendices retirados y trasladados.
* **RF-02 (Curva de Supervivencia Escalonada):** El sistema debe generar un gráfico de líneas escalonado (`stepped: 'before'`) que visualice el descenso de aprendices activos a lo largo del eje cronológico de competencias evaluadas.
* **RF-03 (Determinación Fiel del Punto de Retiro):** Los aprendices retirados o trasladados deben ubicarse en la competencia y fecha real donde cesó su actividad formativa según su último registro evaluado.
* **RF-04 (Tabla de Detalle de Retiros):** Debe presentarse una tabla interactiva de 7 columnas que liste el programa, la competencia, la fase del proyecto, la fecha de salida, la cantidad de retirados, los nombres/estados y el instructor evaluador.

### 🗺️ Módulo 2: Gestión Curricular y Proyectos Formativos (GFPI-F-016)
* **RF-05 (Carga de Proyecto Formativo Desacoplada):** El sistema debe permitir cargar el PDF curricular oficial GFPI-F-016 de manera independiente, sin requerir una ficha previamente registrada en el sistema.
* **RF-06 (Extracción Automatizada de Metadatos y Estructura):** El motor en Python (`pdfplumber`) debe extraer automáticamente el nombre del proyecto, código del programa, código del proyecto SOFIA, centro de formación, regional, duración estimada en meses y total de resultados proyectados.
* **RF-07 (Detección Dinámica de Tablas y Columnas):** El parser debe identificar dinámicamente las columnas de Fase, Actividad de Proyecto, Resultado de Aprendizaje y Competencia, adaptándose a variaciones visuales y de formato entre regionales.
* **RF-08 (Manejo de Celdas Combinadas y Multilínea):** El extractor debe propagar valores de fases y actividades combinadas (`fill_down`) y concatenar textos explicativos que ocupen múltiples renglones sin fragmentar la descripción formativa.
* **RF-09 (Deduplicación Estricta de Resultados y Actividades):** El sistema debe garantizar la unicidad de los resultados de aprendizaje mediante claves compuestas normalizadas `(fase, act_id, res_ident, comp_ident)`, descartando celdas de relleno visual (`–`, `—`, guiones) y omitiendo filas fantasma sin datos formativos.
* **RF-10 (Vista Previa Interactiva de Extracción):** Antes de persistir en base de datos, el usuario debe visualizar un modal/resumen con los contadores de fases, actividades, competencias y resultados detectados, junto con la tabla completa para verificación humana.
* **RF-11 (Vinculación Flexible de Fichas a Proyectos):** El sistema debe permitir asociar una o múltiples fichas de formación a un mismo Proyecto Formativo matriz, permitiendo la reutilización curricular completa sin duplicar tablas.
* **RF-12 (Línea de Tiempo y Cumplimiento de Fases):** Debe presentarse una línea de tiempo con tarjetas interactivas que comparen los resultados requeridos por el diseño curricular vs los juicios aprobados en Sofia Plus, calculando el porcentaje de cumplimiento por fase.
* **RF-13 (Cruce Jerárquico sin N/A):** Las competencias evaluadas en Sofia Plus deben asociarse automáticamente a su fase pedagógica mediante el cruce jerárquico por código de resultado (`codigo_resultado`) y orden cronológico de fase.

### 📥 Módulo 3: Importación Masiva y Procesamiento de Datos
* **RF-14 (Carga por Lotes de Sofia Plus):** El importador debe procesar archivos `.xlsx`, `.xls` y `.csv` de gran volumen mediante bloques de 500 registros (`batch processing`) para evitar saturación de memoria y timeouts.
* **RF-15 (Normalización y Mapeo Inteligente de Columnas):** El sistema debe reconocer y mapear automáticamente los encabezados del reporte de Sofia Plus sin importar variaciones de orden o mayúsculas/minúsculas.

### 🔍 Módulo 4: Filtros Avanzados, Auditoría y Eliminación
* **RF-16 (Búsqueda Multicriterio Paginada):** Permitir filtrar juicios por programa, número de documento, estado del aprendiz, competencia, resultado y tipo de juicio en tiempo real.
* **RF-17 (Auditoría por Instructor):** El sistema debe totalizar los registros evaluados por cada docente y mostrar en orden cronológico real la fecha del primer y último juicio evaluado.
* **RF-18 (Exportación a CSV Estructurado):** Permitir la descarga de los datos filtrados en formato CSV con codificación UTF-8 BOM para visualización correcta en hojas de cálculo.
* **RF-19 (Búsqueda y Eliminación Controlada):** Facilitar la búsqueda de aprendices por documento o nombre/apellido y permitir su eliminación segura mediante confirmación modal con eliminación referencial en cascada.

---

## ⚙️ 3. Requisitos No Funcionales (RNF)

* **RNF-01 (Rendimiento):** El procesamiento de reportes de más de 10.000 registros debe completarse en menos de 10 segundos en el servidor.
* **RNF-02 (Seguridad y Sentencias Preparadas):** El 100% de las consultas a base de datos deben utilizar sentencias preparadas PDO con parámetros tipados para neutralizar ataques de inyección SQL (SQL Injection).
* **RNF-03 (Resiliencia en Extracción de Documentos):** El script de extracción en Python debe manejar excepciones de formato, estructuras de tablas irregulares y textos rotos sin abortar inesperadamente el proceso.
* **RNF-04 (Integridad Referencial y Transaccionalidad):** Las inserciones masivas de proyectos curriculares y reportes deben ejecutarse dentro de transacciones ACID (`BEGIN TRANSACTION / COMMIT / ROLLBACK`), garantizando que no queden datos huérfanos o inconsistentes en caso de error.
* **RNF-05 (Disponibilidad y Contenerización):** El aplicativo debe operar en contenedores Docker independientes comunicados por red interna y con soporte para reinicio automático (`restart: always`).
* **RNF-06 (Diseño y Responsividad):** La interfaz debe ser intuitiva, moderna (paleta de colores institucional SENA `#39A900` y tema oscuro), accesible y responsiva en monitores, tablets y móviles.

