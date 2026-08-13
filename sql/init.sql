-- ================================================================
-- Sistema de Gestión de Datos - SENA Juicios Evaluativos
-- Inicialización de Base de Datos para Docker
-- ================================================================

-- Crear la base de datos si no existe (Docker suele crearla automáticamente)
CREATE DATABASE IF NOT EXISTS sena_juicios;
USE sena_juicios;

-- ================================================================
-- TABLAS PRINCIPALES
-- ================================================================

-- Tabla: aprendices
CREATE TABLE IF NOT EXISTS `aprendices` (
  `id_aprendiz`       INT(11)      NOT NULL AUTO_INCREMENT,
  `documento`         VARCHAR(30)  NOT NULL UNIQUE,
  `nombre`            VARCHAR(255) NOT NULL,
  `apellido`          VARCHAR(255) NOT NULL,
  `email`             VARCHAR(255) NULL,
  `telefono`          VARCHAR(20)  NULL,
  `id_ficha`          INT(11)      NULL,
  `estado`            VARCHAR(50)  NOT NULL DEFAULT 'Activo',
  `fecha_ingreso`     DATE         NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_aprendiz`),
  KEY `idx_aprendices_documento` (`documento`),
  KEY `idx_aprendices_ficha` (`id_ficha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: fichas
CREATE TABLE IF NOT EXISTS `fichas` (
  `id_ficha`          INT(11)      NOT NULL AUTO_INCREMENT,
  `numero_ficha`      VARCHAR(50)  NOT NULL UNIQUE,
  `programa`          VARCHAR(255) NOT NULL,
  `instructor`        VARCHAR(255) NULL,
  `fecha_inicio`      DATE         NULL,
  `fecha_fin`         DATE         NULL,
  `estado`            VARCHAR(50)  NOT NULL DEFAULT 'Activa',
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_ficha`),
  KEY `idx_fichas_numero` (`numero_ficha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: competencias
CREATE TABLE IF NOT EXISTS `competencias` (
  `id_competencia`    INT(11)      NOT NULL AUTO_INCREMENT,
  `codigo`            VARCHAR(50)  NOT NULL,
  `nombre`            VARCHAR(255) NOT NULL,
  `descripcion`       TEXT         NULL,
  `id_ficha`          INT(11)      NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_competencia`),
  KEY `idx_competencias_codigo` (`codigo`),
  KEY `idx_competencias_ficha` (`id_ficha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: juicios_evaluativos
CREATE TABLE IF NOT EXISTS `juicios_evaluativos` (
  `id_juicio`         INT(11)      NOT NULL AUTO_INCREMENT,
  `id_aprendiz`       INT(11)      NOT NULL,
  `id_competencia`    INT(11)      NOT NULL,
  `resultado`         VARCHAR(50)  NOT NULL,
  `estado`            VARCHAR(50)  NOT NULL DEFAULT 'Pendiente',
  `tipo_juicio`       VARCHAR(50)  NULL,
  `fecha_juicio`      DATE         NULL,
  `observaciones`     TEXT         NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_juicio`),
  KEY `idx_juicios_aprendiz` (`id_aprendiz`),
  KEY `idx_juicios_competencia` (`id_competencia`),
  KEY `idx_juicios_resultado` (`resultado`),
  KEY `idx_juicios_estado` (`estado`),
  FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`id_aprendiz`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: fases_proyecto
CREATE TABLE IF NOT EXISTS `fases_proyecto` (
  `id_fase`           INT(11)      NOT NULL AUTO_INCREMENT,
  `nombre_fase`       VARCHAR(255) NOT NULL,
  `orden`             INT(11)      NOT NULL DEFAULT 1,
  `descripcion`       TEXT         NULL,
  `id_ficha`          INT(11)      NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_fase`),
  KEY `idx_fases_ficha` (`id_ficha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: actividades_fase
CREATE TABLE IF NOT EXISTS `actividades_fase` (
  `id_actividad`      INT(11)      NOT NULL AUTO_INCREMENT,
  `id_fase`           INT(11)      NOT NULL,
  `nombre`            VARCHAR(255) NOT NULL,
  `descripcion`       TEXT         NULL,
  `id_ficha`          INT(11)      NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_actividad`),
  KEY `idx_actividades_fase` (`id_fase`),
  FOREIGN KEY (`id_fase`) REFERENCES `fases_proyecto` (`id_fase`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: cola_procesamiento (para importaciones asincrónicas)
CREATE TABLE IF NOT EXISTS `cola_procesamiento` (
  `id_tarea`          INT(11)      NOT NULL AUTO_INCREMENT,
  `tipo_tarea`        VARCHAR(50)  NOT NULL,
  `datos_json`        LONGTEXT     NOT NULL,
  `estado`            VARCHAR(50)  NOT NULL DEFAULT 'Pendiente',
  `intentos`          INT(11)      NOT NULL DEFAULT 0,
  `mensaje_error`     TEXT         NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tarea`),
  KEY `idx_cola_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================================
-- Listo: Base de datos inicializada
-- ================================================================
