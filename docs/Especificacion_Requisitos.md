# 📋 Especificación de Requisitos de Software (SRS)

## 📌 1. Introducción y Propósito

El **Sistema de Gestión de Datos** es una solución integral diseñada para optimizar el seguimiento académico, análisis de supervivencia estudiantil, auditoría docente y monitoreo del avance formativo de aprendices en el marco institucional del SENA.

El sistema unifica dos fuentes fundamentales de información:
1. **Reportes de Juicios Evaluativos de Sofia Plus (Excel/CSV):** Estados de formación, competencias evaluadas, juicios (Aprobado, No aprobado, Por evaluar) e instructores.
2. **Proyectos Formativos GFPI-F-016 (PDF):** Estructura curricular oficial del programa dividida en Fases (Análisis, Planeación, Ejecución, Evaluación) y sus Actividades de Proyecto.

---

## 🎯 2. Requisitos Funcionales (RF)

### 📊 Módulo 1: Dashboard y Curva de Supervivencia
* **RF-01 (KPIs Globales):** El sistema debe calcular en tiempo real el total de aprendices activos, juicios aprobados, juicios por evaluar, programas registrados, aprendices retirados y trasladados.
* **RF-02 (Curva de Supervivencia Escalonada):** El sistema debe generar un gráfico de líneas escalonado (`stepped: 'before'`) que visualice el descenso de aprendices activos a lo largo del eje cronológico de competencias evaluadas.
* **RF-03 (Determinación Fiel del Punto de Retiro):** Los aprendices retirados o trasladados deben ubicarse en la competencia y fecha real donde cesó su actividad formativa.
* **RF-04 (Tabla de Detalle de Retiros):** Debe presentarse una tabla interactiva que liste el programa, la competencia, la fase del proyecto, la fecha de salida, la cantidad de retirados, los nombres/estados y el instructor evaluador.

### 🗺️ Módulo 2: Gestión y Tablero de Fases Formativas
* **RF-05 (Extracción de Proyectos desde PDF):** El sistema debe permitir cargar el formato GFPI-F-016 y extraer automáticamente la información básica del programa, sus fases, actividades y códigos de competencias/resultados.
* **RF-06 (Línea de Tiempo de Fases):** Debe presentarse una línea de tiempo con tarjetas dinámicas que comparen los resultados requeridos por el PDF con los aprobados en el Excel.
* **RF-07 (Cruce Automático sin N/A):** Las competencias evaluadas en Sofia Plus deben asociarse automáticamente a su fase pedagógica mediante el cruce jerárquico por código de resultado (`codigo_resultado`) y orden de fase.

### 📥 Módulo 3: Importación Masiva y Procesamiento de Datos
* **RF-08 (Carga por Lotes de Sofia Plus):** El importador debe procesar archivos `.xlsx`, `.xls` y `.csv` de hasta 50MB mediante lotes de 500 registros (`batch processing`) para evitar timeouts de memoria.
* **RF-09 (Normalización y Detección Inteligente):** El sistema debe mapear automáticamente las columnas del reporte de Sofia Plus sin importar el orden o mayúsculas/minúsculas.

### 🔍 Módulo 4: Filtros Avanzados, Auditoría y Eliminación
* **RF-10 (Búsqueda Multicriterio):** Permitir filtrar juicios por programa, número de documento, estado del aprendiz, competencia, resultado y tipo de juicio.
* **RF-11 (Auditoría por Instructor):** El sistema debe totalizar los registros evaluados por cada docente y mostrar en orden cronológico real la fecha del primer y último juicio evaluado.
* **RF-12 (Exportación a CSV):** Permitir la descarga de los datos filtrados en formato CSV estructurado con codificación UTF-8 con BOM para visualización correcta en Excel.
* **RF-13 (Búsqueda y Eliminación Controlada):** Facilitar la búsqueda de aprendices por documento o nombre/apellido y permitir su eliminación segura con confirmación modal.

---

## ⚙️ 3. Requisitos No Funcionales (RNF)

* **RNF-01 (Rendimiento):** El procesamiento de reportes de más de 10.000 registros debe completarse en menos de 10 segundos en el servidor.
* **RNF-02 (Seguridad):** 100% de las consultas a base de datos deben utilizar Sentencias Preparadas (PDO) con parámetros tipados para neutralizar ataques SQL Injection.
* **RNF-03 (Disponibilidad y Contenerización):** El aplicativo debe operar en contenedores Docker independientes comunicados por red interna y con soporte para reinicio automático (`restart: always`).
* **RNF-04 (Diseño y Responsividad):** La interfaz debe ser intuitiva, moderna (paleta de colores SENA `#39A900` y tema oscuro), accesible y responsiva en monitores, tablets y móviles.
