-- ================================================================
-- MIGRACIÓN v4: Desacoplamiento de Proyectos Formativos y Fichas
-- Fecha: 2026-09-11
-- Base de datos: sena_juicios (MariaDB / MySQL)
-- TOTALMENTE IDEMPOTENTE: Puede ejecutarse 1 o N veces sin duplicar ni fallar
-- ================================================================

USE sena_juicios;

-- 1. Crear tabla independiente de proyectos formativos si no existe
CREATE TABLE IF NOT EXISTS `proyectos_formativos` (
  `id_proyecto` INT(11) NOT NULL AUTO_INCREMENT,
  `codigo_programa_sofia` VARCHAR(20) DEFAULT NULL,
  `nombre_programa` VARCHAR(255) DEFAULT NULL,
  `nombre_proyecto` TEXT NOT NULL,
  `centro_formacion` VARCHAR(255) DEFAULT NULL,
  `regional` VARCHAR(100) DEFAULT NULL,
  `tiempo_estimado_meses` INT(11) DEFAULT NULL,
  `total_resultados` INT(11) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_proyecto`),
  KEY `idx_pf_sofia` (`codigo_programa_sofia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Migrar los proyectos existentes desde `programas` a `proyectos_formativos` (solo si la columna nombre_proyecto o codigo_programa_sofia aún existe en programas)
SET @exist_nombre_proy := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'programas' AND COLUMN_NAME = 'nombre_proyecto'
);

SET @sql_migrar := IF(@exist_nombre_proy > 0, 
  'INSERT INTO `proyectos_formativos` (codigo_programa_sofia, nombre_programa, nombre_proyecto, centro_formacion, regional, tiempo_estimado_meses, total_resultados)
   SELECT p.codigo_programa_sofia, p.nombre, COALESCE(p.nombre_proyecto, p.nombre), p.centro_formacion, p.regional, p.tiempo_estimado_meses, p.total_resultados
   FROM `programas` p
   WHERE (p.nombre_proyecto IS NOT NULL OR p.codigo_programa_sofia IS NOT NULL OR EXISTS (SELECT 1 FROM fases_proyecto fp WHERE fp.id_ficha = p.id_ficha))
     AND NOT EXISTS (
       SELECT 1 FROM `proyectos_formativos` pf 
       WHERE (p.codigo_programa_sofia IS NOT NULL AND pf.codigo_programa_sofia = p.codigo_programa_sofia)
          OR (p.nombre_proyecto IS NOT NULL AND pf.nombre_proyecto = p.nombre_proyecto)
     )', 
  'SELECT 1');
PREPARE stmt_migrar FROM @sql_migrar;
EXECUTE stmt_migrar;
DEALLOCATE PREPARE stmt_migrar;

-- 3. Agregar columna id_proyecto a `programas` si no existe
SET @exist_prog_col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'programas' AND COLUMN_NAME = 'id_proyecto'
);
SET @sql_prog := IF(@exist_prog_col = 0, 
  'ALTER TABLE `programas` ADD COLUMN `id_proyecto` INT(11) NULL DEFAULT NULL AFTER `nombre`', 
  'SELECT 1');
PREPARE stmt_prog FROM @sql_prog;
EXECUTE stmt_prog;
DEALLOCATE PREPARE stmt_prog;

-- 4. Asociar cada programa con su id_proyecto correspondiente (si aún tiene columnas viejas)
SET @sql_asoc := IF(@exist_nombre_proy > 0, 
  'UPDATE `programas` p
   JOIN `proyectos_formativos` pf 
     ON (p.codigo_programa_sofia IS NOT NULL AND p.codigo_programa_sofia = pf.codigo_programa_sofia)
     OR (p.nombre_proyecto IS NOT NULL AND p.nombre_proyecto = pf.nombre_proyecto)
   SET p.id_proyecto = pf.id_proyecto
   WHERE p.id_proyecto IS NULL',
  'SELECT 1');
PREPARE stmt_asoc FROM @sql_asoc;
EXECUTE stmt_asoc;
DEALLOCATE PREPARE stmt_asoc;

-- 5. Agregar id_proyecto a `fases_proyecto`, `actividades_fase` y `fase_competencia_resultado`
SET @exist_fp_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fases_proyecto' AND COLUMN_NAME = 'id_proyecto');
SET @sql_fp := IF(@exist_fp_col = 0, 'ALTER TABLE `fases_proyecto` ADD COLUMN `id_proyecto` INT(11) NULL DEFAULT NULL AFTER `id_fase`', 'SELECT 1');
PREPARE stmt_fp FROM @sql_fp; EXECUTE stmt_fp; DEALLOCATE PREPARE stmt_fp;

SET @exist_af_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'actividades_fase' AND COLUMN_NAME = 'id_proyecto');
SET @sql_af := IF(@exist_af_col = 0, 'ALTER TABLE `actividades_fase` ADD COLUMN `id_proyecto` INT(11) NULL DEFAULT NULL AFTER `id_actividad`', 'SELECT 1');
PREPARE stmt_af FROM @sql_af; EXECUTE stmt_af; DEALLOCATE PREPARE stmt_af;

SET @exist_fcr_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fase_competencia_resultado' AND COLUMN_NAME = 'id_proyecto');
SET @sql_fcr := IF(@exist_fcr_col = 0, 'ALTER TABLE `fase_competencia_resultado` ADD COLUMN `id_proyecto` INT(11) NULL DEFAULT NULL AFTER `id`', 'SELECT 1');
PREPARE stmt_fcr FROM @sql_fcr; EXECUTE stmt_fcr; DEALLOCATE PREPARE stmt_fcr;

-- 6. Rellenar id_proyecto en fases_proyecto, actividades_fase y fase_competencia_resultado si están NULL
UPDATE `fases_proyecto` fp
JOIN `programas` p ON fp.id_ficha = p.id_ficha
SET fp.id_proyecto = p.id_proyecto
WHERE fp.id_proyecto IS NULL AND p.id_proyecto IS NOT NULL;

UPDATE `actividades_fase` af
JOIN `fases_proyecto` fp ON af.id_fase = fp.id_fase
SET af.id_proyecto = fp.id_proyecto
WHERE af.id_proyecto IS NULL AND fp.id_proyecto IS NOT NULL;

UPDATE `fase_competencia_resultado` fcr
JOIN `actividades_fase` af ON fcr.id_actividad = af.id_actividad
SET fcr.id_proyecto = af.id_proyecto
WHERE fcr.id_proyecto IS NULL AND af.id_proyecto IS NOT NULL;

-- 7. Agregar índices (idempotente)
ALTER TABLE `programas` ADD INDEX IF NOT EXISTS `idx_programas_proyecto` (`id_proyecto`);
ALTER TABLE `fases_proyecto` ADD INDEX IF NOT EXISTS `idx_fases_proyecto_id` (`id_proyecto`);
ALTER TABLE `actividades_fase` ADD INDEX IF NOT EXISTS `idx_actividades_proyecto_id` (`id_proyecto`);
ALTER TABLE `fase_competencia_resultado` ADD INDEX IF NOT EXISTS `idx_fcr_proyecto_id` (`id_proyecto`);

-- 8. Clave foránea segura (idempotente)
SET @exist_fk := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'programas' 
    AND CONSTRAINT_NAME = 'fk_programas_proyecto'
);
SET @sql_fk := IF(@exist_fk = 0,
  'ALTER TABLE `programas` ADD CONSTRAINT `fk_programas_proyecto` FOREIGN KEY (`id_proyecto`) REFERENCES `proyectos_formativos` (`id_proyecto`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1');
PREPARE stmt_fk FROM @sql_fk;
EXECUTE stmt_fk;
DEALLOCATE PREPARE stmt_fk;

-- 9. LIMPIEZA DE TABLAS OBSOLETAS Y EN DESUSO
DROP TABLE IF EXISTS `fichas`;
DROP TABLE IF EXISTS `cola_procesamiento`;
DROP TABLE IF EXISTS `logs_importacion`;

-- 10. LIMPIEZA DE COLUMNAS REDUNDANTES EN PROGRAMAS
ALTER TABLE `programas`
  DROP COLUMN IF EXISTS `codigo_programa_sofia`,
  DROP COLUMN IF EXISTS `nombre_proyecto`,
  DROP COLUMN IF EXISTS `centro_formacion`,
  DROP COLUMN IF EXISTS `regional`,
  DROP COLUMN IF EXISTS `tiempo_estimado_meses`,
  DROP COLUMN IF EXISTS `total_resultados`;
