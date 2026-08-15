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
    end

    subgraph DATABASE["💾 Base de Datos"]
        DB[("MariaDB / MySQL\nsena_juicios")]
    end

    CLIENT_LAYER --> INDEX
    INDEX --> CONTROLLERS
    CONTROLLERS --> SERVICES
    CONTROLLERS --> MODELS
    SERVICES --> DB
    MODELS --> DB
```

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
