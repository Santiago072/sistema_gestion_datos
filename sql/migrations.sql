-- ================================================================
-- SENA JUICIOS EVALUATIVOS — Script de Migraciones
-- Tablas adicionales para módulo de Fases del Proyecto Formativo
-- ================================================================

USE sena_juicios;

-- ================================================================
-- MIGRACIÓN v2: Vincular fases y actividades al programa (id_ficha)
-- ================================================================
-- EJECUTAR SOLO SI LAS COLUMNAS NO EXISTEN AÚN:
-- ALTER TABLE `fases_proyecto`   ADD COLUMN IF NOT EXISTS `id_ficha` INT(11) NULL DEFAULT NULL AFTER `descripcion`;
-- ALTER TABLE `actividades_fase` ADD COLUMN IF NOT EXISTS `id_ficha` INT(11) NULL DEFAULT NULL AFTER `id_fase`;

-- ----------------------------------------------------------------
-- Tabla: fases_proyecto
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fases_proyecto` (
  `id_fase`      INT(11)      NOT NULL AUTO_INCREMENT,
  `nombre_fase`  VARCHAR(255) NOT NULL,
  `orden`        INT(11)      NOT NULL DEFAULT 1,
  `descripcion`  TEXT         NULL,
  `id_ficha`     INT(11)      NULL DEFAULT NULL COMMENT 'Ficha del programa al que pertenece este proyecto formativo',
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_fase`),
  KEY `idx_fases_ficha` (`id_ficha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------
-- Tabla: actividades_fase
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `actividades_fase` (
  `id_actividad` INT(11)      NOT NULL AUTO_INCREMENT,
  `nombre`       VARCHAR(255) NOT NULL,
  `descripcion`  TEXT         NULL,
  `id_fase`      INT(11)      NOT NULL,
  `id_ficha`     INT(11)      NULL DEFAULT NULL COMMENT 'Ficha del programa (denormalizado para filtros rápidos)',
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_actividad`),
  KEY `fk_actividades_fase_idx` (`id_fase`),
  KEY `idx_actividades_ficha`   (`id_ficha`),
  CONSTRAINT `fk_actividades_fase` FOREIGN KEY (`id_fase`)
    REFERENCES `fases_proyecto` (`id_fase`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------
-- Tabla: fase_competencia_resultado
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fase_competencia_resultado` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `id_actividad`    INT(11) NOT NULL,
  `id_competencia`  INT(11) NULL DEFAULT NULL,
  `id_resultado`    INT(11) NULL DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_fcr_actividad_idx`   (`id_actividad`),
  KEY `fk_fcr_competencia_idx` (`id_competencia`),
  KEY `fk_fcr_resultado_idx`   (`id_resultado`),
  CONSTRAINT `fk_fcr_actividad`   FOREIGN KEY (`id_actividad`)   REFERENCES `actividades_fase`  (`id_actividad`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fcr_competencia` FOREIGN KEY (`id_competencia`) REFERENCES `competencias`       (`id_competencia`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fcr_resultado`   FOREIGN KEY (`id_resultado`)   REFERENCES `resultados`         (`id_resultado`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------
-- Datos de ejemplo: Fases SENA
-- ----------------------------------------------------------------
INSERT IGNORE INTO `fases_proyecto` (`id_fase`, `nombre_fase`, `orden`, `descripcion`) VALUES
(1, 'Análisis',    1, 'Fase de análisis del proyecto formativo: identificación de necesidades, contexto y problemática.'),
(2, 'Planeación',  2, 'Fase de planeación: definición de estrategias, recursos y cronograma de actividades.'),
(3, 'Ejecución',   3, 'Fase de ejecución: desarrollo práctico de actividades según el plan formativo.'),
(4, 'Evaluación',  4, 'Fase de evaluación: valoración de resultados, competencias alcanzadas y retroalimentación.');

-- ----------------------------------------------------------------
-- Datos de ejemplo: Actividades por Fase
-- ----------------------------------------------------------------
INSERT IGNORE INTO `actividades_fase` (`id_actividad`, `nombre`, `descripcion`, `id_fase`) VALUES
(1,  'Levantamiento de requisitos',         'Identificación de requerimientos del sistema o proyecto', 1),
(2,  'Análisis de contexto',                'Estudio del entorno donde se aplicará la solución',       1),
(3,  'Diseño de la solución',               'Planeación técnica de la solución a implementar',         2),
(4,  'Cronograma de actividades',           'Definición de tiempos y responsables',                    2),
(5,  'Desarrollo del módulo principal',     'Codificación del núcleo del sistema',                     3),
(6,  'Integración de bases de datos',       'Conexión y configuración del motor de BD',                3),
(7,  'Pruebas unitarias',                   'Verificación individual de componentes desarrollados',     3),
(8,  'Evaluación de competencias técnicas', 'Juicio evaluativo de los resultados de aprendizaje',      4),
(9,  'Retroalimentación al aprendiz',       'Sesión de retroalimentación individual con el instructor', 4);

-- ----------------------------------------------------------------
-- Datos de ejemplo: Relaciones Competencia-Resultado-Fase
-- ----------------------------------------------------------------
INSERT IGNORE INTO `fase_competencia_resultado` (`id_actividad`, `id_competencia`, `id_resultado`) VALUES
(1, 1, 1),
(1, 1, 2),
(3, 2, 2),
(5, 1, 1),
(5, 3, 3),
(6, 2, 2),
(7, 4, 4),
(8, 1, 1),
(8, 3, 3),
(9, 5, 5);

-- ----------------------------------------------------------------
-- Actualizar vista del dashboard para incluir totales de programas y funcionarios
-- ----------------------------------------------------------------
CREATE OR REPLACE VIEW `v_dashboard_indicadores` AS
SELECT
  (SELECT COUNT(*) FROM aprendices WHERE estado = 'En formación')     AS total_aprendices_activos,
  (SELECT COUNT(*) FROM aprendices WHERE estado = 'Retirado')         AS total_retirados,
  (SELECT COUNT(*) FROM aprendices WHERE estado = 'Trasladado')       AS total_trasladados,
  (SELECT COUNT(*) FROM juicios    WHERE tipo_juicio = 'Aprobado')    AS total_juicios_aprobados,
  (SELECT COUNT(*) FROM juicios    WHERE tipo_juicio = 'Por evaluar') AS total_juicios_por_evaluar,
  (SELECT COUNT(*) FROM juicios    WHERE tipo_juicio = 'No aprobado') AS total_juicios_no_aprobados,
  (SELECT COUNT(*) FROM programas)                                    AS total_programas,
  (SELECT COUNT(*) FROM funcionarios)                                 AS total_funcionarios;

-- ================================================================
-- MIGRACIÓN v3: Relacionar juicios evaluativos con fases del PDF
-- Ejecutado: 2026-05-22
-- ================================================================

-- 1. Enriquecer programas con datos del PDF (centro, regional, SOFIA, etc.)
ALTER TABLE `programas`
  ADD COLUMN IF NOT EXISTS `codigo_programa_sofia` VARCHAR(20)  NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `nombre_proyecto`        TEXT         NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `centro_formacion`       VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `regional`               VARCHAR(100) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `tiempo_estimado_meses`  INT          NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `total_resultados`       INT          NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `updated_at`             DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 2. Vincular juicios directamente al aprendiz y a la ficha (evitar 3 JOINs)
ALTER TABLE `juicios`
  ADD COLUMN IF NOT EXISTS `id_ficha`            INT(11)      NULL DEFAULT NULL AFTER `id_funcionario`,
  ADD COLUMN IF NOT EXISTS `documento_aprendiz`  VARCHAR(255) NULL DEFAULT NULL AFTER `id_ficha`;

ALTER TABLE `juicios`
  ADD INDEX IF NOT EXISTS `idx_juicios_ficha`    (`id_ficha`),
  ADD INDEX IF NOT EXISTS `idx_juicios_aprendiz` (`documento_aprendiz`);

-- 3. Vincular competencias a la ficha
ALTER TABLE `competencias`
  ADD COLUMN IF NOT EXISTS `id_ficha` INT(11) NULL DEFAULT NULL AFTER `id_aprendiz`;

ALTER TABLE `competencias`
  ADD INDEX IF NOT EXISTS `idx_competencias_ficha` (`id_ficha`);

-- 4. Vincular catálogo de fases (PDF) a la ficha
ALTER TABLE `fase_competencia_resultado`
  ADD COLUMN IF NOT EXISTS `id_ficha` INT(11) NULL DEFAULT NULL AFTER `id_actividad`;

ALTER TABLE `fase_competencia_resultado`
  ADD INDEX IF NOT EXISTS `idx_fcr_ficha` (`id_ficha`);

-- ================================================================
-- Relación juicio <-> fase por código (no FK, JOIN por código):
--   competencias.codigo          <-> fase_competencia_resultado.codigo_competencia
--   resultados.codigo            <-> fase_competencia_resultado.codigo_resultado
--   juicios.documento_aprendiz   <-> aprendices.documento
--   juicios.id_ficha             <-> fases_proyecto.id_ficha
-- ================================================================

