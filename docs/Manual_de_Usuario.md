# 📖 Manual de Usuario del Sistema de Gestión de Datos

Bienvenido a la guía oficial de usuario para el **Sistema de Gestión de Datos**. Este manual describe de forma práctica cómo navegar, operar y aprovechar las herramientas del sistema.

---

## 🧭 1. Navegación Principal

La barra superior permite acceder rápidamente a todos los módulos del sistema:
* **📊 Dashboard:** Métricas generales, curva de retiros y auditoría docente.
* **🗺️ Fases Formativas:** Proyectos formativos, línea de tiempo por fase y carga de PDF GFPI-F-016.
* **📥 Carga de Datos:** Importador masivo de reportes de Sofia Plus en Excel o CSV.
* **🔎 Seguimiento:** Consulta individualizada por aprendiz y avance porcentual.
* **🗑️ Eliminación:** Búsqueda rápida y eliminación segura de registros.

---

## 📊 2. Tablero de Control y Curva de Supervivencia

1. **Selector de Programa:** En la parte superior del Dashboard, puedes seleccionar una ficha o programa específico para filtrar todas las métricas en tiempo real, o seleccionar *Todos los Programas* para una vista consolidada.
2. **KPIs:** Muestra el número de aprendices activos, juicios aprobados, juicios pendientes por evaluar, aprendices retirados y trasladados.
3. **Curva de Retiros por Competencia:**
   - La gráfica muestra la supervivencia de aprendices a lo largo de las competencias ordenadas cronológicamente.
   - Al pasar el cursor por encima de un punto, verás la **Fase del proyecto**, el **Nombre de la competencia**, la **Fecha de evaluación**, la **Cantidad de aprendices activos** y la lista de **Aprendices que salieron** en esa fecha junto con su **Instructor evaluador**.
4. **Tabla de Retiros:** Ubicada debajo del gráfico, detalla en 7 columnas cada punto de salida registrado.
5. **Auditoría de Instructores:** Consulta el consolidado de juicios evaluados por cada docente y la fecha exacta de su primer y último registro.

---

## 🗺️ 3. Módulo de Proyectos Formativos y Carga de PDF (GFPI-F-016)

1. **Pestaña Proyecto Formativo:**
   - Visualiza la ficha técnica completa del proyecto (Centro de Formación, Regional, Código de Proyecto SOFIA, Código de Programa, Duración en meses y total de resultados proyectados).
   - Gestiona la **Vinculación de Fichas**: asocia una o más fichas de formación al proyecto matriz para heredar de inmediato toda su malla curricular.
2. **Pestaña Fases y Actividades:**
   - Explora el árbol curricular completo desglosado en sus cuatro fases canónicas (**Análisis, Planeación, Ejecución y Evaluación**).
   - Consulta el avance de cumplimiento porcentual por fase y la relación de actividades con sus normas de competencia y resultados de aprendizaje.
3. **Pestaña Carga PDF:**
   - **Subida Independiente:** Ya no necesitas tener una ficha creada de antemano. Arrastra o selecciona directamente el archivo PDF del formato oficial **GFPI-F-016**.
   - Haz clic en **Procesar PDF**: el sistema ejecutará el motor en Python para analizar la estructura, deduplicar resultados y generar una vista previa con el resumen de fases, competencias y resultados detectados.
   - Revisa la vista previa y haz clic en **Guardar en Base de Datos**. El proyecto quedará almacenado de forma global y listo para ser vinculado a las fichas que desees.

---

## 📥 4. Importación de Reportes Sofia Plus (Excel/CSV)

1. Ingresa a la sección **Carga de Datos**.
2. Arrastra tu archivo `.xlsx`, `.xls` o `.csv` descargado de Sofia Plus.
3. El sistema procesará las filas en lotes automáticos (`batch de 500 registros`), actualizando aprendices, fichas, competencias, resultados y funcionarios sin duplicar registros.

---

## 🔍 5. Filtro Avanzado y Búsqueda de Aprendices

1. En el Dashboard, expande la sección **Filtro Avanzado**.
2. Ingresa los criterios deseados (Documento, Competencia, Resultado, Estado o Tipo de Juicio).
3. Haz clic en **Filtrar** para ver los resultados paginados en tiempo real.
4. Si requieres descargar la información, haz clic en **Exportar a CSV**.
