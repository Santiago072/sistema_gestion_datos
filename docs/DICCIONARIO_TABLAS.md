# 📚 Diccionario Exhaustivo de Tablas de la Base de Datos (`sena_juicios`)

Este documento detalla el propósito, origen de datos, estructura de campos, claves foráneas y relaciones de cada una de las tablas del **Sistema de Gestión de Datos**.

---

## 🗂️ Resumen de Tablas por Origen de Información

El sistema unifica dos fuentes de datos principales:

| Tabla | Origen de Datos | Propósito Principal |
| :--- | :--- | :--- |
| `proyectos_formativos` | **PDF GFPI-F-016** / Registro Manual | Cabecera general del proyecto formativo institucional. |
| `fases_proyecto` | **PDF GFPI-F-016** | Fases curriculares (`ANÁLISIS`, `PLANEACIÓN`, `EJECUCIÓN`, `EVALUACIÓN`). |
| `actividades_fase` | **PDF GFPI-F-016** | Actividades de proyecto formativo asociadas a cada fase. |
| `fase_competencia_resultado` | **PDF GFPI-F-016** | Mapeo curricular: actividad ↔ competencia ↔ resultado. |
| `programas` | **Sofia Plus (Excel/CSV)** | Fichas de caracterización y su vinculación al proyecto formativo. |
| `aprendices` | **Sofia Plus (Excel/CSV)** | Datos de aprendices matriculados y sus estados de formación. |
| `competencias` | **Sofia Plus (Excel/CSV)** | Competencias asignadas y cursadas por cada aprendiz. |
| `resultados` | **Sofia Plus (Excel/CSV)** | Resultados de aprendizaje evaluados a los aprendices. |
| `juicios` | **Sofia Plus (Excel/CSV)** | Juicios evaluativos (`APROBADO`, `POR EVALUAR`, `NO APROBADO`). |
| `funcionarios` | **Sofia Plus (Excel/CSV)** | Instructores y evaluadores que emitieron los juicios en el sistema. |

---

## 🔍 Detalle Exhaustivo de Cada Tabla

### 1. `proyectos_formativos`
* **Propósito:** Almacena los Proyectos Formativos institucionales como entidades independientes. Permite que un proyecto curricular exista de forma global y pueda vincularse a múltiples fichas (Modelo Desacoplado v4).
* **Campos:**
  - `id_proyecto` (INT, PK, AUTO_INCREMENT): Identificador numérico único del proyecto.
  - `codigo_programa_sofia` (VARCHAR(20)): Código numérico oficial del programa en SOFIA Plus (ej: `723105`).
  - `nombre_programa` (VARCHAR(255)): Nombre de la carrera o programa técnico/tecnológico (ej: `PRODUCCION GANADERA`).
  - `nombre_proyecto` (TEXT): Título oficial completo del proyecto formativo aprobado.
  - `centro_formacion` (VARCHAR(255)): Centro de formación del SENA responsable del proyecto.
  - `regional` (VARCHAR(100)): Regional territorial del SENA (ej: `REGIONAL CAQUETA`).
  - `tiempo_estimado_meses` (INT): Duración estimada total del programa en meses lectivos.
  - `total_resultados` (INT): Cantidad total de resultados de aprendizaje estipulados en el diseño.
  - `created_at` / `updated_at` (DATETIME): Marcas de tiempo de creación y modificación.

---

### 2. `programas` (Fichas de Formación)
* **Propósito:** Representa las fichas de caracterización de los grupos de aprendices que ingresan al SENA. Conecta los grupos de formación con su correspondiente proyecto formativo.
* **Campos:**
  - `id_ficha` (INT, PK): Número de ficha único de SOFIA Plus (ej: `2825000`).
  - `nombre` (VARCHAR(255)): Nombre del programa asignado a la ficha.
  - `id_proyecto` (INT, FK opcional): Llave foránea hacia `proyectos_formativos(id_proyecto)`. Si está asociada, la ficha hereda automáticamente la malla de fases, actividades y resultados del proyecto formativo.
  - `updated_at` (DATETIME): Última fecha de actualización del registro.
* **Relaciones:** `ON DELETE SET NULL`: si se elimina el proyecto formativo matriz, la ficha no se borra, únicamente queda desvinculada.

---

### 3. `fases_proyecto`
* **Propósito:** Divide el proyecto formativo en sus etapas cronológicas de desarrollo pedagógico. Todo proyecto formativo del SENA se estructura en cuatro fases estándar.
* **Campos:**
  - `id_fase` (INT, PK, AUTO_INCREMENT): Identificador único de la fase.
  - `id_proyecto` (INT, FK): Proyecto formativo al que pertenece.
  - `nombre_fase` (VARCHAR(255)): Nombre canónico de la fase (`ANÁLISIS`, `PLANEACIÓN`, `EJECUCIÓN`, `EVALUACIÓN`).
  - `orden` (INT): Secuencia lógica dentro del ciclo de aprendizaje (1: Análisis, 2: Planeación, 3: Ejecución, 4: Evaluación).
  - `descripcion` (TEXT): Descripción pedagógica de los objetivos de la fase.
  - `id_ficha` (INT, NULL): Mantenido por retrocompatibilidad para fichas históricas directas.
* **Relaciones:** `ON DELETE CASCADE` con `proyectos_formativos`: si se elimina el proyecto matriz, sus fases se eliminan limpiamente en cascada.

---

### 4. `actividades_fase`
* **Propósito:** Almacena las actividades específicas de aprendizaje y producción formuladas para cada fase dentro del formato GFPI-F-016.
* **Campos:**
  - `id_actividad` (INT, PK, AUTO_INCREMENT): Identificador único de la actividad.
  - `id_proyecto` (INT, FK): Enlace directo al proyecto matriz.
  - `id_fase` (INT, FK): Fase curricular a la cual está asignada la actividad.
  - `nombre` (TEXT): Denominación completa de la actividad (ej: `2. DIAGNOSTICAR DE MANERA INTEGRAL LA UNIDAD DE PRODUCCIÓN...`).
  - `descripcion` (TEXT): Especificaciones o alcance técnico de la actividad.
  - `id_ficha` (INT, NULL): Campo de compatibilidad histórica.
* **Relaciones:** `ON DELETE CASCADE` con `fases_proyecto`: al removerse una fase se eliminan automáticamente sus actividades.

---

### 5. `fase_competencia_resultado`
* **Propósito:** Es la **tabla relacional central de la malla curricular**. Mapea cada actividad de proyecto con las normas de competencia laboral y sus respectivos resultados de aprendizaje (Sección 3 del documento GFPI-F-016).
* **Campos:**
  - `id` (INT, PK, AUTO_INCREMENT): Identificador único del registro curricular.
  - `id_proyecto` (INT, FK): Proyecto formativo global al que pertenece la asociación.
  - `id_actividad` (INT, FK): Actividad de fase que genera y tributa a este resultado.
  - `codigo_competencia` (VARCHAR(20)): Código numérico normalizado de la norma de competencia (ej: `270401015`).
  - `nombre_competencia` (TEXT): Denominación oficial de la competencia laboral.
  - `codigo_resultado` (VARCHAR(20)): Código SOFIA Plus o identificador del resultado (ej: `282576`).
  - `nombre_resultado` (TEXT): Descripción textual detallada del resultado de aprendizaje a alcanzar.
  - `id_ficha` (INT, NULL): Campo de retrocompatibilidad.
* **Relaciones:** `ON DELETE CASCADE` con `actividades_fase`. Permite que el sistema cruce los resultados del PDF contra los juicios evaluados en Sofia Plus sin duplicaciones.

---

### 6. `aprendices`
* **Propósito:** Almacena los aprendices matriculados en cada una de las fichas de formación, junto con su estado académico actual.
* **Campos:**
  - `documento` (VARCHAR(255), PK): Documento de identidad del aprendiz (Cédula, Tarjeta de Identidad, etc.).
  - `tipo_documento` (VARCHAR(255)): Sigla del tipo de documento (`CC`, `TI`, `PEP`, `CE`).
  - `nombres` (VARCHAR(255)): Nombres de pila del aprendiz.
  - `apellidos` (VARCHAR(255)): Apellidos del aprendiz.
  - `estado` (ENUM): Estado de matrícula en Sofia Plus:
    * `En formación`: Aprendiz activo cursando normalmente.
    * `Retirado`: Deserción, cancelación de matrícula o retiro voluntario.
    * `Trasladado`: Movilidad a otra ficha o centro.
    * `Egresado`: Culminó exitosamente su etapa formativa y productiva.
  - `id_ficha` (INT, FK): Ficha a la que pertenece el aprendiz.
* **Relaciones:** Llave foránea hacia `programas(id_ficha)`.

---

### 7. `competencias`
* **Propósito:** Registra las asignaciones de competencias a cada aprendiz individualmente según el reporte cargado de Sofia Plus.
* **Campos:**
  - `id_competencia` (INT, PK, AUTO_INCREMENT): Identificador único de la asignación.
  - `codigo` (VARCHAR(20)): Código de la norma de competencia en Sofia Plus.
  - `nombre` (TEXT): Nombre descriptivo de la competencia laboral.
  - `id_aprendiz` (VARCHAR(255), FK): Documento del aprendiz que cursa la competencia.
  - `id_ficha` (INT, FK): Ficha en la cual está matriculado.

---

### 8. `resultados`
* **Propósito:** Registra cada resultado de aprendizaje que cursa un aprendiz dentro de una competencia, asociándole su juicio evaluativo correspondiente.
* **Campos:**
  - `id_resultado` (INT, PK, AUTO_INCREMENT): Identificador único del registro evaluativo.
  - `codigo` (VARCHAR(20)): Código del resultado en Sofia Plus.
  - `nombre` (TEXT): Descripción textual del resultado de aprendizaje.
  - `id_juicio` (INT, FK): Llave foránea hacia la tabla `juicios` para conocer el veredicto evaluativo.

---

### 9. `juicios`
* **Propósito:** Almacena el veredicto evaluativo emitido por los instructores para cada resultado de aprendizaje, la fecha exacta de asentamiento y el funcionario responsable.
* **Campos:**
  - `id_juicio` (INT, PK, AUTO_INCREMENT): Identificador del juicio evaluativo.
  - `tipo_juicio` (VARCHAR(50)): Veredicto oficial (`APROBADO`, `POR EVALUAR`, `NO APROBADO`, etc.).
  - `fecha_juicio` (DATETIME): Fecha y hora en que el juicio fue asentado en la plataforma Sofia Plus. Es vital para la **Curva de Retiros 2025** y auditoría.
  - `id_funcionario` (INT, FK): Documento de identidad del instructor que evaluó.

---

### 10. `funcionarios`
* **Propósito:** Catálogo de instructores, evaluadores y coordinadores registrados en las actas de evaluación de Sofia Plus.
* **Campos:**
  - `documento` (INT, PK): Número de cédula o documento del instructor.
  - `nombre` (VARCHAR(255)): Nombre completo del instructor.

---

## 🔗 Cómo Interactúan las Dos Fuentes de Datos

```
[ PDF Curricular GFPI-F-016 ]                         [ Reporte Sofia Plus (Excel/CSV) ]
             │                                                          │
             ▼                                                          ▼
   proyectos_formativos                                            programas (Fichas)
             │                                                          │
             ├───────────► Vinculación (id_proyecto) ◄──────────────────┤
             │                                                          │
             ▼                                                          ▼
       fases_proyecto                                               aprendices
             │                                                          │
             ▼                                                          ▼
      actividades_fase                                             competencias
             │                                                          │
             ▼                                                          ▼
fase_competencia_resultado ◄────── Cruce por codigo_resultado ──────► resultados
                                                                        │
                                                                        ▼
                                                                     juicios
                                                                        │
                                                                        ▼
                                                                   funcionarios
```

1. **La Malla Teórica (PDF):** Establece qué resultados *deberían* cursarse en cada fase (`Análisis`, `Planeación`, etc.).
2. **La Realidad Evaluativa (Sofia Plus):** Informa cuáles resultados *ya fueron aprobados*, cuáles están *por evaluar* y en qué fecha.
3. **El Cruce Automático del Sistema:** Al comparar `fase_competencia_resultado.codigo_resultado` con `resultados.codigo`, el sistema determina exactamente el porcentaje de avance formativo real por fase y la curva de retiros estudiantil sin inconsistencias.
