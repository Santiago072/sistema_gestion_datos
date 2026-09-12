# 📖 Documentación Técnica del Sistema de Gestión de Datos

Esta guía técnica proporciona una visión profunda de los componentes internos, patrones de arquitectura, diccionario exhaustivo de tablas en la base de datos, motor de extracción de PDFs curriculares (formato SENA GFPI-F-016) y catálogo de endpoints del **Sistema de Gestión de Datos**.

---

## 🏛️ 1. Estructura de Directorios del Proyecto

```
sistema_gestion_datos/
├── app/
│   ├── controllers/
│   │   ├── AprendicesController.php   ← Búsqueda, seguimiento individual y eliminación
│   │   ├── CargaController.php        ← Importador por lotes de Sofia Plus (Excel/CSV en bloques)
│   │   ├── DashboardController.php    ← Endpoints AJAX de KPIs, curva de retiro y auditoría
│   │   ├── FasesController.php        ← Gestión de proyectos, fases, actividades y carga PDF
│   │   └── LandingController.php      ← Vista principal y Landing Page Institucional
│   ├── models/
│   │   ├── AprendizModel.php          ← Acceso a datos de aprendices y estados de matrícula
│   │   ├── BaseModel.php              ← Conexión base PDO con transacciones y reconexión
│   │   ├── DashboardFasesRepository.php ← Métricas de cumplimiento formativo por fase
│   │   ├── DashboardModel.php         ← KPIs globales, conteos y auditoría evaluativa
│   │   ├── FasesModel.php             ← Persistencia y consulta de proyectos formativos
│   │   ├── JuiciosModel.php           ← Auditoría y comparativas evaluativas (Aprobado/Por Evaluar)
│   │   ├── ProgramaModel.php          ← Consulta de programas y fichas formativas
│   │   ├── ProyectoModel.php          ← Gestión CRUD de Proyectos Formativos independientes
│   │   └── RetiradosModel.php         ← Análisis de retiros, supervivencia y deserción
│   ├── services/
│   │   └── import/
│   │       ├── CsvAdapter.php         ← Adaptador optimizado para lectura de archivos CSV
│   │       ├── ExcelAdapter.php       ← Adaptador para hojas de cálculo Excel (.xlsx/.xls)
│   │       ├── FasesImportService.php ← Orquestador de validación y persistencia de PDF
│   │       └── ImportAdapterInterface.php ← Contrato estándar de adaptadores de importación
│   ├── views/
│   │   ├── aprendices/                ← Vistas de seguimiento curricular individual por aprendiz
│   │   ├── carga/                     ← Vista de importación masiva de reportes Sofia Plus
│   │   ├── dashboard/                 ← Dashboard interactivo con Chart.js y tablas dinámicas
│   │   ├── eliminacion/               ← Gestión de depuración y borrado seguro de registros
│   │   ├── fases/                     ← Gestión de Proyectos Formativos, vinculación y visor PDF
│   │   ├── landing/                   ← Presentación ejecutiva del sistema y métricas
│   │   └── layouts/                   ← Componentes compartidos (header, footer, nav)
│   └── controllers/scripts/
│       └── extract_pdf.py             ← Motor de extracción inteligente con pdfplumber
├── assets/
│   ├── css/                           ← Estilos modulares (variables CSS, temas y dashboards)
│   └── js/                            ← Controladores frontend (fases.js, pdf_upload.js, etc.)
├── config/
│   ├── database.php                   ← Conexión PDO optimizada a MariaDB/MySQL (`sena_juicios`)
│   ├── seguridad.php                  ← Rate limiting, CORS, CSRF y sanitización de inputs
│   └── url_config.php                 ← Enrutamiento centralizado y resolución de rutas base
├── docs/                              ← Documentación técnica, manuales, requisitos y arquitectura
├── docker-compose.yml                 ← Infraestructura lista para despliegue contenerizado
├── Dockerfile                         ← Imagen Docker PHP 8.2-FPM + Nginx + Python 3.11 + pdfplumber
├── deploy.sh                          ← Script de despliegue automático en servidores VPS
└── index.php                          ← Front Controller centralizado con enrutamiento semántico
```

---

## 💾 2. Esquema Relacional de la Base de Datos (`sena_juicios`)

La base de datos cuenta con una arquitectura desacoplada donde el **Proyecto Formativo** existe de manera independiente y puede ser reutilizado por múltiples fichas o programas de formación:

```mermaid
erDiagram
    PROYECTOS_FORMATIVOS ||--o{ PROGRAMAS : "asocia a fichas"
    PROYECTOS_FORMATIVOS ||--o{ FASES_PROYECTO : "contiene"
    FASES_PROYECTO ||--o{ ACTIVIDADES_FASE : "agrupa"
    ACTIVIDADES_FASE ||--o{ FASE_COMPETENCIA_RESULTADO : "vincula"

    PROGRAMAS ||--o{ APRENDICES : "matricula"
    APRENDICES ||--o{ COMPETENCIAS : "cursa"
    COMPETENCIAS ||--o{ RESULTADOS : "asocia"
    RESULTADOS ||--o{ JUICIOS : "evalua"
    FUNCIONARIOS ||--o{ JUICIOS : "asienta"

    PROYECTOS_FORMATIVOS {
        int id_proyecto PK
        string codigo_programa_sofia
        string nombre_programa
        text nombre_proyecto
        string centro_formacion
        string regional
        int tiempo_estimado_meses
        int total_resultados
    }

    PROGRAMAS {
        int id_ficha PK
        string nombre
        int id_proyecto FK
    }

    FASES_PROYECTO {
        int id_fase PK
        int id_proyecto FK
        string nombre_fase
        int orden
        text descripcion
        int id_ficha FK
    }

    ACTIVIDADES_FASE {
        int id_actividad PK
        int id_proyecto FK
        string nombre
        int id_fase FK
        int id_ficha FK
    }

    FASE_COMPETENCIA_RESULTADO {
        int id PK
        int id_proyecto FK
        int id_actividad FK
        string codigo_competencia
        string codigo_resultado
        text nombre_competencia
        text nombre_resultado
        int id_ficha FK
    }

    APRENDICES {
        string documento PK
        string tipo_documento
        string nombres
        string apellidos
        string estado
        int id_ficha FK
    }

    COMPETENCIAS {
        int id_competencia PK
        string codigo
        text nombre
        string id_aprendiz FK
        int id_ficha FK
    }

    RESULTADOS {
        int id_resultado PK
        string codigo
        text nombre
        int id_juicio FK
    }

    JUICIOS {
        int id_juicio PK
        string tipo_juicio
        datetime fecha_juicio
        int id_funcionario FK
    }

    FUNCIONARIOS {
        int documento PK
        string nombre
    }
```

---

## 📋 3. Diccionario Completo de Tablas

### 1. `proyectos_formativos`
Almacena la cabecera e identificación de cada documento **GFPI-F-016 (Proyecto Formativo)** extraído desde PDF o registrado manualmente.
- `id_proyecto` (INT, PK, AUTO_INCREMENT): Identificador único del proyecto.
- `codigo_programa_sofia` (VARCHAR(20)): Código numérico del programa en SOFIA Plus.
- `nombre_programa` (VARCHAR(255)): Nombre oficial de la carrera o programa formativo.
- `nombre_proyecto` (TEXT): Título completo del proyecto formativo institucional.
- `centro_formacion` (VARCHAR(255)): Centro del SENA responsable del proyecto.
- `regional` (VARCHAR(100)): Regional del SENA a la que pertenece.
- `tiempo_estimado_meses` (INT): Duración estimada de la formación en meses.
- `total_resultados` (INT): Total de resultados de aprendizaje proyectados en el diseño.
- `created_at` / `updated_at` (DATETIME): Marcas de tiempo de auditoría.

### 2. `programas` (Fichas de Formación)
Registra las fichas de caracterización activas en el sistema y su asociación opcional al proyecto curricular.
- `id_ficha` (INT, PK): Número de ficha único asignado en SOFIA Plus.
- `nombre` (VARCHAR(255)): Nombre descriptivo del programa de formación para la ficha.
- `id_proyecto` (INT, FK opcional): Enlace con la tabla `proyectos_formativos`. Permite que múltiples fichas compartan la misma malla curricular.

### 3. `fases_proyecto`
Fases cronológicas y pedagógicas que componen el proyecto formativo.
- `id_fase` (INT, PK, AUTO_INCREMENT): Identificador numérico de la fase.
- `id_proyecto` (INT, FK): Vinculación al proyecto formativo matriz.
- `nombre_fase` (VARCHAR(255)): Nombre canónico de la fase (`ANÁLISIS`, `PLANEACIÓN`, `EJECUCIÓN`, `EVALUACIÓN`).
- `orden` (INT): Posición secuencial en el ciclo de formación (1 a 4).
- `descripcion` (TEXT): Alcance pedagógico de la fase.

### 4. `actividades_fase`
Actividades de proyecto formativo vinculadas a cada fase curricular.
- `id_actividad` (INT, PK, AUTO_INCREMENT): Identificador único de la actividad.
- `id_proyecto` (INT, FK): Enlace al proyecto formativo correspondiente.
- `id_fase` (INT, FK): Fase a la cual pertenece la actividad.
- `nombre` (TEXT): Nombre descriptivo y código interno de la actividad del proyecto.
- `descripcion` (TEXT): Detalle o especificación técnica de la labor formativa.

### 5. `fase_competencia_resultado`
Tabla relacional clave que mapea cada actividad con sus competencias y resultados de aprendizaje específicos (Sección 3 del formato GFPI-F-016).
- `id` (INT, PK, AUTO_INCREMENT): Identificador del registro curricular.
- `id_proyecto` (INT, FK): Proyecto formativo global.
- `id_actividad` (INT, FK): Actividad de proyecto que tributa al resultado.
- `codigo_competencia` (VARCHAR(20)): Código numérico normalizado de la competencia SOFIA Plus.
- `nombre_competencia` (TEXT): Denominación de la norma de competencia laboral.
- `codigo_resultado` (VARCHAR(20)): Código SOFIA o secuencia identificadora del resultado.
- `nombre_resultado` (TEXT): Descripción textual completa del resultado de aprendizaje.

### 6. `aprendices`
Información demográfica y estado formativo de los aprendices registrados en SOFIA Plus.
- `documento` (VARCHAR(255), PK): Documento de identidad del aprendiz.
- `tipo_documento` (VARCHAR(255)): CC, TI, PEP, CE, etc.
- `nombres` y `apellidos` (VARCHAR(255)): Nombres y apellidos del aprendiz.
- `estado` (ENUM): `En formación`, `Retirado`, `Trasladado`, `Egresado`.
- `id_ficha` (INT, FK): Ficha a la que pertenece el aprendiz.

### 7. Las 3 Tablas Centrales de Consultas Analíticas (`competencias`, `resultados`, `juicios`)

Estas tres tablas constituyen el **corazón evaluativo y analítico del sistema**. Es sobre este trío donde se construyen todas las analíticas, reportes y dashboards en tiempo real:

#### 7.1. `competencias` (Agrupador Curricular Operativo)
- **Propósito:** Almacena la relación de normas de competencia cursadas por cada aprendiz en su ficha.
- **Campos:** `id_competencia` (PK, INT), `codigo` (VARCHAR(20)), `nombre` (TEXT), `id_aprendiz` (FK, VARCHAR), `id_ficha` (FK, INT).
- **Función en Consultas:** Alimenta el eje horizontal de la **Curva de Retiros y Supervivencia**, agrupando a los aprendices por cada módulo de formación cursado.

#### 7.2. `resultados` (Unidad de Juicio Evaluativo)
- **Propósito:** Desglosa cada uno de los resultados de aprendizaje específicos que componen la competencia cursada por el aprendiz.
- **Campos:** `id_resultado` (PK, INT), `codigo` (VARCHAR(20)), `nombre` (TEXT), `id_juicio` (FK, INT).
- **Función en Consultas:** Es el nexo de cruce directo con la matriz del PDF (`fase_competencia_resultado.codigo_resultado`). Permite calcular el **porcentaje de cumplimiento por fase formativa** y alimentar la sábana de notas del aprendiz.

#### 7.3. `juicios` (Veredicto y Auditoría Temporal)
- **Propósito:** Registra el dictamen oficial emitido (`APROBADO`, `POR EVALUAR`, `NO APROBADO`), la estampa temporal exacta y el instructor firmante.
- **Campos:** `id_juicio` (PK, INT), `tipo_juicio` (VARCHAR(50)), `fecha_juicio` (DATETIME), `id_funcionario` (FK, INT).
- **Función en Consultas:** 
  - Alimenta los **KPIs del Dashboard** (conteo de aprobados vs pendientes).
  - Permite calcular la **fecha real del retiro en 2025** (último juicio aprobado antes de la salida).
  - Permite la **Auditoría Docente** (fecha de primer y último juicio evaluado por cada funcionario).

### 8. `funcionarios`
- **Propósito:** Catálogo institucional de instructores y evaluadores registrados en Sofia Plus.
- **Campos:** `documento` (PK, INT) y `nombre` (VARCHAR(255)).
- **Función en Consultas:** Vincula cada juicio evaluativo con el nombre y cédula del docente responsable.

### 9. `historial_cortes_reportes`
- **Propósito:** Registro histórico y trazabilidad de los cortes mensuales cargados mediante archivos Excel/CSV de Sofia Plus.
- **Campos:**
  - `id_corte` (INT, PK, AUTO_INCREMENT): Identificador del corte.
  - `id_ficha` (INT, FK, NULL): Ficha de formación a la que pertenece el reporte (o null si es global).
  - `nombre_archivo` (VARCHAR(255)): Nombre del archivo importado (ej: `Reporte de Juicios Evaluativos - 06042026.xlsx`).
  - `fecha_corte` (DATE): Fecha de corte pedagógico extraída del nombre o configurada manualmente (ej: `2026-04-06`).
  - `fecha_subida` (DATETIME): Estampa temporal exacta de la carga en zona horaria oficial `America/Bogota` (UTC-5).
  - `total_filas` (INT): Cantidad de filas procesadas del archivo.
  - `estado` (VARCHAR(50)): Estado del corte (`exitoso`, etc.).
- **Función en el Dashboard:** Cuando el usuario selecciona una ficha específica en el filtro superior, se proyecta una píldora informativa destacada en la cabecera con la fecha de corte, la fecha de subida y el nombre completo del archivo.

---

## 🐍 4. Motor de Extracción de PDFs: `extract_pdf.py`

El script ubicado en `app/controllers/scripts/extract_pdf.py` automatiza la interpretación del formato **GFPI-F-016 (Proyecto Formativo SENA)** mediante la librería `pdfplumber`.

### Flujo de Funcionamiento:
1. **Extracción de Metadatos Iniciales (`extract_info_basica`)**:
   - Lee las primeras páginas buscando mediante expresiones regulares el Código de Proyecto SOFIA, Código de Programa, Centro de Formación, Regional, Duración en meses y Cantidad total de resultados de aprendizaje.
2. **Detección Dinámica de Columnas (`detect_col_mapping`)**:
   - Analiza las tablas buscando dinámicamente las columnas de **Fase**, **Actividad de Proyecto**, **Resultado de Aprendizaje** y **Competencia**, adaptándose a variaciones entre regionales del SENA.
3. **Propagación de Celdas Combinadas (`fill_down`)**:
   - Resuelve el problema común de PDFs donde celdas combinadas de fases y actividades solo traen texto en la primera fila, propagando limpiamente los valores hacia las filas secundarias.
4. **Manejo de Continuaciones de Texto Multilínea**:
   - Une textos de nombres largos que se dividen en saltos de fila sin código SOFIA propio, asegurando que la descripción pedagógica no quede mutilada.
5. **Deduplicación Inteligente y Limpieza de Filas Fantasma**:
   - Identifica filas vacías o con solo guiones de relleno (`–`, `—`) y las descarta.
   - Genera una clave única compuesta por `(fase, act_id, res_ident, comp_ident)` normalizando espacios y mayúsculas, garantizando **cero registros duplicados** al importar proyectos de cualquier tamaño.

---

## 📡 5. Catálogo de Endpoints AJAX y Controladores

| Controlador | Acción | Método | Parámetros | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| `DashboardController` | `kpis` | GET | `id_ficha` (opcional) | Retorna contadores agregados de aprendices activos, aprobados y pendientes, junto al último corte si hay ficha seleccionada. |
| `DashboardController` | `retirados_competencia` | GET | `id_ficha` | Curva de supervivencia y competencias con mayor deserción en el año. |
| `DashboardController` | `auditoria_funcionarios` | GET | `id_ficha` | Totales evaluados por cada instructor y fechas de primer/último registro. |
| `DashboardController` | `filtro_avanzado` | GET | Criterios múltiples | Consulta paginada multicriterio con opción de exportación directa a CSV. |
| `FasesController` | `upload_pdf` | POST | Archivo `pdf` | Ejecuta `extract_pdf.py` y retorna la vista previa del proyecto estructurado. |
| `FasesController` | `guardar_pdf` | POST | JSON del proyecto | Persiste el proyecto formativo, sus fases, actividades y resultados en la BD. |
| `FasesController` | `vincular_ficha` | POST | `id_ficha`, `id_proyecto` | Asocia una ficha de formación existente a un proyecto curricular matriz. |
| `FasesController` | `cumplimiento_fases` | GET | `id_ficha` | Porcentaje de avance y cumplimiento de juicios evaluativos por fase. |
| `CargaController` | `upload` | POST | Archivo `file` | Procesa el archivo de Sofia Plus en bloques transaccionales de 500 filas, fusionando cortes sin retrocesos y registrando la fecha de corte. |
| `CargaController` | `historial_cortes` | GET | `id_ficha` (opcional) | Retorna el historial cronológico de cortes importados con su fecha y archivo. |

---

## 🚀 6. Despliegue en Servidor VPS

El despliegue está completamente automatizado a través del script `deploy.sh`:

```bash
bash deploy.sh
```

### Acciones que realiza automáticamente:
1. Ajusta los permisos locales del usuario en el servidor.
2. Descarga los últimos cambios de la rama `master` en GitHub (`git fetch` y `git reset --hard origin/master`).
3. Reconstruye y levanta los contenedores Docker (`docker compose up -d --build`).
4. **Aplica las migraciones de base de datos** (`sql/migracion_vps_actual.sql`), asegurando que las nuevas tablas (como `historial_cortes_reportes` y `proyectos_formativos`) y columnas queden creadas y actualizadas sin intervención manual.

