-- ====================================================================
-- MIGRACIÓN DE SINCRONIZACIÓN LOCAL -> VPS (MariaDB / Docker)
-- Base de Datos: sena_juicios
-- Aplica: Proyectos Formativos v4 + Historial de Cortes + Limpieza
-- ====================================================================

USE sena_juicios;

-- 1. Eliminar tabla obsoleta si existiera
DROP TABLE IF EXISTS `trabajos_importacion`;

-- 2. Crear tabla independiente de proyectos formativos si no existe
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

-- 3. Crear tabla de historial de cortes de juicios si no existe
CREATE TABLE IF NOT EXISTS `historial_cortes_reportes` (
  `id_corte` INT(11) NOT NULL AUTO_INCREMENT,
  `id_ficha` INT(11) DEFAULT NULL,
  `nombre_archivo` VARCHAR(255) NOT NULL,
  `fecha_corte` DATE NOT NULL,
  `fecha_subida` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `total_filas` INT(11) DEFAULT 0,
  `estado` VARCHAR(50) DEFAULT 'exitoso',
  PRIMARY KEY (`id_corte`),
  KEY `idx_hcr_ficha` (`id_ficha`),
  KEY `idx_hcr_fecha_corte` (`fecha_corte`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Asegurar columna id_proyecto en programas
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

-- 5. Asegurar columna id_proyecto en fases_proyecto
SET @exist_fases_col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fases_proyecto' AND COLUMN_NAME = 'id_proyecto'
);
SET @sql_fases := IF(@exist_fases_col = 0, 
  'ALTER TABLE `fases_proyecto` ADD COLUMN `id_proyecto` INT(11) NULL DEFAULT NULL AFTER `id_fase`, ADD KEY `idx_fases_proyecto_id` (`id_proyecto`)', 
  'SELECT 1');
PREPARE stmt_fases FROM @sql_fases;
EXECUTE stmt_fases;
DEALLOCATE PREPARE stmt_fases;

-- 6. Asegurar columna id_proyecto en actividades_fase
SET @exist_act_col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'actividades_fase' AND COLUMN_NAME = 'id_proyecto'
);
SET @sql_act := IF(@exist_act_col = 0, 
  'ALTER TABLE `actividades_fase` ADD COLUMN `id_proyecto` INT(11) NULL DEFAULT NULL AFTER `id_actividad`, ADD KEY `idx_actividades_proyecto_id` (`id_proyecto`)', 
  'SELECT 1');
PREPARE stmt_act FROM @sql_act;
EXECUTE stmt_act;
DEALLOCATE PREPARE stmt_act;

-- 7. Asegurar columna id_proyecto en fase_competencia_resultado
SET @exist_fcr_col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fase_competencia_resultado' AND COLUMN_NAME = 'id_proyecto'
);
SET @sql_fcr := IF(@exist_fcr_col = 0, 
  'ALTER TABLE `fase_competencia_resultado` ADD COLUMN `id_proyecto` INT(11) NULL DEFAULT NULL AFTER `id`, ADD KEY `idx_fcr_proyecto_id` (`id_proyecto`)', 
  'SELECT 1');
PREPARE stmt_fcr FROM @sql_fcr;
EXECUTE stmt_fcr;
DEALLOCATE PREPARE stmt_fcr;
