# 🏗️ Arquitectura y Seguridad del Sistema

Este documento describe detalladamente la arquitectura de software, los patrones de diseño, el flujo de datos del procesamiento dual (Excel + PDF) y las políticas de seguridad implementadas en el **Sistema de Gestión de Datos**.

---

## 🏛️ Patrón Arquitectónico: MVC Extendido

El sistema implementa una arquitectura **Modelo-Vista-Controlador (MVC)** con Front Controller centralizado y Servicios especializados para tareas de alto procesamiento:

```mermaid
graph TB
    subgraph CLIENT_LAYER["🌐 Capa de Presentación (Frontend)"]
        UI_DASH["Dashboard & KPIs\n(Chart.js, Tablas)"]
        UI_FASES["Tablero de Fases\n(Línea de Tiempo, Detalle)"]
        UI_CARGA["Módulo de Carga\n(Excel / PDF GFPI-F-016)"]
        UI_FILTRO["Filtro Avanzado & Búsqueda"]
    end

    subgraph ROUTER["🔀 Front Controller"]
        INDEX["index.php\n(Enrutador, Middlewares, Rate Limit)"]
    end

    subgraph CONTROLLERS["🎮 Controladores (app/controllers/)"]
        C_DASH["DashboardController"]
        C_FASES["FasesController"]
        C_CARGA["CargaController"]
        C_APPR["AprendicesController"]
    end

    subgraph SERVICES["🔌 Servicios Especializados (app/services/)"]
        S_EXCEL["ExcelAdapter / CsvAdapter\n(Procesamiento Batch 500)"]
        S_PDF["FasesImportService\n(Mapeo Multicriterio GFPI-F-016)"]
        S_PY["Microservicio Python (extract_pdf.py)\n(pdfplumber Extractor)"]
    end

    subgraph MODELS["🗄️ Modelos de Datos (app/models/)"]
        M_BASE["BaseModel (PDO Connection)"]
        M_DASH["DashboardModel"]
        M_RET["RetiradosModel (Curva Supervivencia)"]
        M_JUI["JuiciosModel (Auditoría)"]
        M_FAS["FasesModel / DashboardFasesRepository"]
        M_PROY["ProyectoModel (CRUD Proyectos)"]
    end

    subgraph DATABASE["💾 Base de Datos (3 Capas Funcionales)"]
        subgraph DB_CURR["🏛️ Capa Curricular (PDF GFPI-F-016)"]
            T_PROY["proyectos_formativos"]
            T_FAS["fases_proyecto"]
            T_ACT["actividades_fase"]
            T_FCR["fase_competencia_resultado"]
        end

        subgraph DB_BASE["👥 Capa Base Operativa (Sofia Plus)"]
            T_PROG["programas (Fichas)"]
            T_APR["aprendices"]
            T_FUNC["funcionarios"]
        end

        subgraph DB_EVAL["📊 Las 3 Tablas Centrales de Consultas"]
            T_COMP["competencias"]
            T_RES["resultados"]
            T_JUI["juicios"]
        end
    end

    CLIENT_LAYER --> INDEX
    INDEX --> CONTROLLERS
    CONTROLLERS --> SERVICES
    CONTROLLERS --> MODELS
    SERVICES --> DATABASE
    MODELS --> DATABASE
```

---

## 📊 Arquitectura de Datos: División por Capas y las 3 Tablas de Consultas

La persistencia de datos está estructurada para optimizar tanto la integridad referencial como la velocidad de consulta analítica en dashboards de gran volumen:

1. **Capa Curricular (Estructura Pedagógica - PDF GFPI-F-016):**
   - Conformada por `proyectos_formativos`, `fases_proyecto`, `actividades_fase` y `fase_competencia_resultado`.
   - Modela la **teoría formativa**: qué fases existen, qué actividades se ejecutan y qué resultados deben alcanzarse.
2. **Capa Base Operativa (Entidades Maestras - Sofia Plus):**
   - Conformada por `programas` (fichas matriculadas), `aprendices` (estudiantes y sus estados) y `funcionarios` (instructores).
   - Es la base relacional sobre la cual se registran las matrículas y los responsables pedagógicos.
3. **Las 3 Tablas Centrales de Consultas Analíticas (`competencias`, `resultados`, `juicios`):**
   - Es el **motor analítico del sistema**, donde se registra el desempeño evaluativo real de cada aprendiz.
   - **`competencias`**: Agrupa y clasifica las normas cursadas por ficha y aprendiz.
   - **`resultados`**: Desglosa cada resultado individual con su código normalizado.
   - **`juicios`**: Almacena el estado evaluativo (`APROBADO`, `POR EVALUAR`), la fecha exacta y el instructor que emitió el juicio.
   - **Impacto en Consultas:** Los modelos `DashboardModel`, `RetiradosModel` y `JuiciosModel` ejecutan `JOINs` indexados sobre este trío para generar en milisegundos los KPIs globales, la Curva de Supervivencia 2025, la auditoría docente y el cruce con las fases curriculares.

---

## 🔄 Flujo de Datos del Procesamiento Dual

El sistema integra información proveniente de dos fuentes oficiales:

```mermaid
sequenceDiagram
    autonumber
    actor Usuario
    participant FC as Front Controller (index.php)
    participant CC as Carga / Fases Controller
    participant Service as Import Service (PHP/Python)
    participant DB as Base de Datos (sena_juicios)
    participant Dash as Tablero & Curva de Supervivencia

    Note over Usuario,DB: 1. Carga de Juicios desde Sofia Plus (Excel)
    Usuario->>FC: Subida de archivo .xlsx/.xls/.csv
    FC->>CC: Delegar a CargaController
    CC->>Service: Parsear en lotes de 500 filas
    Service->>DB: Inserción transaccional (aprendices, juicios, resultados, competencias)

    Note over Usuario,DB: 2. Carga de Proyecto Formativo (PDF GFPI-F-016)
    Usuario->>FC: Subida de archivo PDF
    FC->>CC: Delegar a FasesController
    CC->>Service: Extraer fases, actividades y resultados (extract_pdf.py)
    Service->>DB: Almacenar en fases_proyecto, actividades_fase, fase_competencia_resultado

    Note over Usuario,Dash: 3. Cruce Automático y Visualización
    Usuario->>FC: Consultar Dashboard / Curva de Retiros
    FC->>CC: DashboardController::ajaxRetiradosCompetencia
    CC->>DB: Cruce jerárquico (r.codigo = fcr.codigo_resultado + orden fase)
    DB-->>Dash: Datos consolidados (supervivencia, fase real, fechas 2025, instructor)
```

---

## 🛡️ Políticas y Medidas de Seguridad Implementadas

1. **Cabeceras de Seguridad HTTP:**
   - `X-Frame-Options: SAMEORIGIN` (Protección contra Clickjacking).
   - `X-Content-Type-Options: nosniff` (Prevención de MIME Confusion Attacks).
   - `X-XSS-Protection: 1; mode=block` (Mitigación de Cross-Site Scripting reflejado).
   - `Referrer-Policy: strict-origin-when-cross-origin`.

2. **Control de Frecuencia (Rate Limiting):**
   - Sistema de limitación de tasa por IP en endpoints AJAX (`verificar_rate_limit`) para mitigar ataques de denegación de servicio (DoS) o fuerza bruta.

3. **Prevención de Inyecciones SQL:**
   - 100% de las consultas a la base de datos se ejecutan a través de **PDO con Sentencias Preparadas** y parámetros tipados, desactivando emulaciones vulnerables (`PDO::ATTR_EMULATE_PREPARES => false`).

4. **Integridad Transaccional:**
   - La importación masiva de datos (Excel y PDF) se ejecuta bajo bloques `beginTransaction()`, `commit()` y `rollBack()`, garantizando que ningún error parcial deje la base de datos en estado inconsistente.

5. **Aislamiento en Docker:**
   - El contenedor de base de datos (`gestion_datos_db`) no expone puertos públicos a internet; la comunicación se realiza exclusivamente en la red bridge privada con el contenedor de la aplicación (`gestion_datos_app`).
