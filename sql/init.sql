-- ================================================================
-- Sistema de Gestión de Datos - SENA Juicios Evaluativos
-- Inicialización de Base de Datos
-- ================================================================

CREATE DATABASE IF NOT EXISTS sena_juicios;
USE sena_juicios;

-- Tabla: funcionarios (instructores/evaluadores)
CREATE TABLE IF NOT EXISTS `funcionarios` (
  `id_funcionario`    INT(11)      NOT NULL AUTO_INCREMENT,
  `documento`         VARCHAR(30)  NOT NULL UNIQUE,
  `nombre`            VARCHAR(255) NOT NULL,
  `email`             VARCHAR(255) NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_funcionario`),
  KEY `idx_funcionarios_doc` (`documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: programas
CREATE TABLE IF NOT EXISTS `programas` (
  `id_ficha`          INT(11)      NOT NULL AUTO_INCREMENT,
  `nombre`            VARCHAR(255) NOT NULL,
  `codigo`            VARCHAR(50)  NULL,
  `descripcion`       TEXT         NULL,
  `estado`            VARCHAR(50)  NOT NULL DEFAULT 'Activo',
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_ficha`),
  KEY `idx_programas_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: aprendices
CREATE TABLE IF NOT EXISTS `aprendices` (
  `id_aprendiz`       INT(11)      NOT NULL AUTO_INCREMENT,
  `documento`         VARCHAR(30)  NOT NULL UNIQUE,
  `tipo_documento`    VARCHAR(50)  NULL,
  `nombres`           VARCHAR(255) NOT NULL,
  `apellidos`         VARCHAR(255) NOT NULL,
  `email`             VARCHAR(255) NULL,
  `telefono`          VARCHAR(20)  NULL,
  `id_ficha`          INT(11)      NULL,
  `estado`            VARCHAR(50)  NOT NULL DEFAULT 'En formación',
  `fecha_ingreso`     DATE         NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_aprendiz`),
  KEY `idx_aprendices_documento` (`documento`),
  KEY `idx_aprendices_ficha` (`id_ficha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: competencias
CREATE TABLE IF NOT EXISTS `competencias` (
  `id_competencia`    INT(11)      NOT NULL AUTO_INCREMENT,
  `codigo`            VARCHAR(50)  NULL,
  `nombre`            VARCHAR(255) NOT NULL,
  `descripcion`       TEXT         NULL,
  `id_ficha`          INT(11)      NULL,
  `id_aprendiz`       INT(11)      NULL,
  `id_resultado`      INT(11)      NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_competencia`),
  KEY `idx_competencias_codigo` (`codigo`),
  KEY `idx_competencias_ficha` (`id_ficha`),
  KEY `idx_competencias_aprendiz` (`id_aprendiz`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: resultados
CREATE TABLE IF NOT EXISTS `resultados` (
  `id_resultado`      INT(11)      NOT NULL AUTO_INCREMENT,
  `codigo`            VARCHAR(50)  NULL,
  `nombre`            VARCHAR(255) NOT NULL,
  `id_juicio`         INT(11)      NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_resultado`),
  KEY `idx_resultados_codigo` (`codigo`),
  KEY `idx_resultados_juicio` (`id_juicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: juicios
CREATE TABLE IF NOT EXISTS `juicios` (
  `id_juicio`         INT(11)      NOT NULL AUTO_INCREMENT,
  `documento_aprendiz` VARCHAR(30) NULL,
  `id_ficha`          INT(11)      NULL,
  `id_funcionario`    INT(11)      NULL,
  `tipo_juicio`       VARCHAR(50)  NOT NULL DEFAULT 'Por evaluar',
  `fecha_juicio`      DATETIME     NULL,
  `observaciones`     TEXT         NULL,
  `estado`            VARCHAR(50)  NOT NULL DEFAULT 'Pendiente',
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_juicio`),
  KEY `idx_juicios_aprendiz` (`documento_aprendiz`),
  KEY `idx_juicios_ficha` (`id_ficha`),
  KEY `idx_juicios_funcionario` (`id_funcionario`),
  KEY `idx_juicios_tipo` (`tipo_juicio`)
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

-- Tabla: cola_procesamiento
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
-- Base de datos inicializada
-- ================================================================
