# Especificación de Requisitos — Sistema de Gestión de Juicios Evaluativos SENA

> **Institución:** Centro de Formación SENA · **Plataforma:** Web (PHP 8 + MySQL, Patrón MVC)

---

## 1. Resumen de la Problemática

El Centro de Formación SENA lleva el seguimiento académico de sus aprendices de manera manual o dispersa: los juicios evaluativos se registran en archivos Excel independientes por instructor, las fases del Proyecto Formativo (GFPI-F-016) se consolidan manualmente desde documentos PDF, y no existe una vista centralizada que permita identificar rápidamente quiénes están en riesgo de retiro o qué competencias tienen mayor índice de reprobación.

Se requiere un sistema que **centralice, automatice e integre** el registro de aprendices, juicios evaluativos y fases del proyecto formativo, entregando indicadores de gestión en tiempo real a coordinadores e instructores.

---

## 2. Requisitos Funcionales (RF)

Estos requisitos definen las funciones específicas que el sistema debe ejecutar:

### Módulo de Aprendices
- **RF01 — Gestión de Aprendices (CRUD):** El sistema debe permitir registrar, consultar, editar y eliminar aprendices, asociándolos a un programa de formación mediante ficha.
- **RF02 — Importación Masiva desde Excel:** El sistema debe permitir cargar archivos `.xls` / `.xlsx` con información de aprendices. Los registros duplicados deben detectarse y reportarse. La importación debe procesarse en segundo plano (asíncrona) para no bloquear la interfaz.
- **RF03 — Eliminación Masiva:** El sistema debe permitir eliminar grupos de aprendices de forma controlada, con confirmación previa.
- **RF04 — Consulta con Filtros:** El sistema debe permitir buscar aprendices por nombre, documento, programa, ficha y estado (En formación / Retirado / Trasladado / Egresado).

### Módulo de Programas y Fichas
- **RF05 — Gestión de Programas:** El sistema debe mantener un catálogo de programas de formación SENA, incluyendo código SOFIA, nombre y regional.
- **RF06 — Filtro Global por Ficha:** Todo el sistema (Dashboard, Fases, Juicios) debe poder filtrarse por ficha/programa desde un selector global.

### Módulo de Fases Formativas
- **RF07 — Gestión de Fases (CRUD):** El sistema debe permitir crear, editar y eliminar fases del Proyecto Formativo (Análisis, Planeación, Ejecución, Evaluación) y sus actividades.
- **RF08 — Importación de Fases desde PDF:** El sistema debe procesar automáticamente el PDF del Proyecto Formativo SENA (GFPI-F-016) extrayendo fases, actividades, competencias y resultados de aprendizaje. El procesamiento se realiza mediante un microservicio Python (Flask + pdfplumber), que PHP inicia automáticamente si no está activo.
- **RF09 — Búsqueda en Tiempo Real (Fases):** El sistema debe permitir buscar fases y actividades en tiempo real con resaltado del término buscado, sin recargar la página.
- **RF10 — Previsualización antes de Importar:** Antes de confirmar la importación del PDF, el sistema debe mostrar un resumen de los datos extraídos (fases, actividades, competencias, resultados).

### Módulo de Juicios Evaluativos
- **RF11 — Importación de Juicios desde Excel:** El sistema debe permitir cargar archivos Excel con los juicios evaluativos (Aprobado / No aprobado / Por evaluar) de forma asíncrona (cola MySQL + Worker PHP).
- **RF12 — Auditoría de Importación:** El sistema debe registrar en una tabla de logs exactamente qué filas del Excel fallaron y el motivo del error, para facilitar la corrección.

### Módulo de Dashboard
- **RF13 — KPIs en Tiempo Real:** El Dashboard debe mostrar tarjetas con: Aprendices Activos, Juicios Aprobados, Por Evaluar, Programas, Retirados y Trasladados; animados con counter al cargar.
- **RF14 — Gráfica de Aprendices por Formación:** Barra apilada por programa mostrando aprendices en formación, retirados y trasladados.
- **RF15 — Comparativa de Juicios por Instructor:** Barra horizontal que compara el volumen de juicios registrados por cada instructor/funcionario.
- **RF16 — Curva de Supervivencia por Competencia:** Gráfica de líneas que muestra cuántos aprendices permanecen activos competencia a competencia por programa, identificando puntos críticos de retiro.
- **RF17 — Filtro Avanzado de Juicios:** El sistema debe permitir buscar juicios combinando filtros de: documento, estado del aprendiz, competencia, resultado de aprendizaje y tipo de juicio. Los resultados deben paginarse del lado del servidor.
- **RF18 — Exportación a CSV:** El filtro avanzado debe permitir exportar los resultados actuales a un archivo CSV descargable.
- **RF19 — Auditoría por Funcionario:** El Dashboard debe mostrar una tabla con la actividad de cada instructor: total de juicios registrados, desglose por tipo y fechas de primer/último registro.

---

## 3. Requisitos No Funcionales (RNF)

Estos requisitos definen los atributos de calidad, rendimiento y seguridad del sistema:

- **RNF01 — Rendimiento en Importación:** Las importaciones masivas de Excel y PDF deben usar Bulk Insert (lotes de 500 registros) para minimizar el tiempo de procesamiento y la carga sobre MySQL.
- **RNF02 — Procesamiento Asíncrono:** La importación de Excel no debe bloquear la interfaz del usuario. El proceso se encola en MySQL y un Worker PHP lo ejecuta en segundo plano.
- **RNF03 — Micro-API Python Auto-gestionada:** El microservicio Flask para extracción de PDF debe iniciarse automáticamente desde PHP si no está activo. El usuario no debe ejecutar ningún comando manual.
- **RNF04 — Protección contra SQL Injection:** Todas las consultas a la base de datos deben usar sentencias preparadas (PDO con prepared statements).
- **RNF05 — Responsividad (Diseño Adaptable):** La interfaz debe adaptarse correctamente a pantallas de 6 tamaños (xs ≤ 479px, sm ≤ 639px, md ≤ 899px, lg ≤ 1199px, xl, 2xl ≥ 1400px) y modo landscape móvil, sin desbordamiento de contenido.
- **RNF06 — Design System Único:** Todo el sistema de estilos debe estar centralizado en un único archivo `assets/css/styles.css` con tokens CSS (colores, radios, sombras) reutilizables por todas las vistas.
- **RNF07 — Separación de Responsabilidades (MVC + Servicios):** La lógica de negocio compleja (importaciones, estadísticas del dashboard) debe residir en clases de Servicio o Repositorios dedicados, no en controladores ni vistas.
- **RNF08 — Sin Bloqueo en Búsquedas:** Los filtros en tiempo real deben usar debounce (300ms) y operar sobre caché local cuando sea posible, minimizando las peticiones al servidor.
- **RNF09 — Integridad de Datos:** Las inserciones masivas deben ejecutarse dentro de transacciones PDO (`BEGIN` / `COMMIT` / `ROLLBACK`) para evitar estados inconsistentes ante fallos parciales.
- **RNF10 — Documentación Actualizada:** El sistema debe mantener documentación técnica viva en `docs/` (arquitectura, flujos, reglas de negocio, módulos, changelog) y un grafo de conocimiento Graphify actualizado tras cada sprint.
- **RNF11 — Seguridad de Archivos Temporales:** Los archivos PDF cargados por usuarios deben eliminarse del servidor inmediatamente tras ser procesados. El tamaño máximo aceptado es 10 MB.
- **RNF12 — Trazabilidad de Cambios:** Todos los cambios de código deben documentarse en `docs/changelog.md` y versionarse mediante commits descriptivos en Git (formato `feat:`, `fix:`, `docs:`, `perf:`, `UI:`).

---

## 4. Módulos del Sistema

| Módulo | RF Asociados | Archivo(s) Principal(es) |
|---|---|---|
| Aprendices | RF01–RF04 | `views/pages/carga_masiva.php`, `models/AprendizModel.php` |
| Programas / Fichas | RF05–RF06 | `views/pages/proyectos_formativos.php`, `models/ProgramaModel.php` |
| Fases Formativas | RF07–RF10 | `views/pages/fases_proyecto.php`, `assets/js/fases.js`, `controllers/upload_pdf_fases.php`, `controllers/scripts/app.py` |
| Juicios Evaluativos | RF11–RF12 | `views/pages/carga_masiva.php`, `models/JuiciosModel.php`, `services/Import/FasesImportService.php` |
| Dashboard Principal | RF13–RF19 | `views/pages/dashboard.php`, `models/DashboardModel.php`, `controllers/dashboard_kpis.php` |
| Dashboard de Fases | — | `views/pages/dashboard_fases.php`, `models/DashboardFasesRepository.php` |

---

## 5. Modelo de Datos — Tablas Principales

| Tabla | Descripción |
|---|---|
| `aprendices` | Información de cada aprendiz (documento, nombre, estado, ficha) |
| `programas` | Catálogo de programas de formación SENA |
| `juicios` | Juicios evaluativos (Aprobado / No aprobado / Por evaluar) |
| `fases_proyecto` | Fases del proyecto formativo por ficha |
| `actividades_fase` | Actividades detalladas por fase |
| `competencias` | Competencias de aprendizaje |
| `resultados` | Resultados de aprendizaje asociados a competencias |
| `funcionarios` | Instructores y funcionarios del centro |
| `trabajos_importacion` | Cola de trabajos de importación asíncrona |
| `logs_importacion` | Auditoría de errores por fila durante importaciones |

---

## 6. Restricciones y Supuestos

- El sistema opera exclusivamente en red local (XAMPP / Apache). No está diseñado para exposición pública sin medidas de seguridad adicionales.
- Los archivos Excel deben seguir la estructura de plantilla SENA para que la importación funcione correctamente.
- Los PDFs deben ser el formulario oficial GFPI-F-016 generado por SOFIA Plus.
- Python 3.10+ con `flask` y `pdfplumber` instalados son requisito previo para el módulo de importación de PDF.
