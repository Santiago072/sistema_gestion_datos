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
  `nombres`           VARCHAR(255) NOT NULL,
  `apellidos`         VARCHAR(255) NOT NULL,
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

-- Tabla: programas (referencias a fichas/programas SENA)
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

-- ================================================================
-- DATOS DE PRUEBA
-- ================================================================

-- Insertar programas de prueba
INSERT IGNORE INTO `programas` (`id_ficha`, `nombre`, `codigo`, `descripcion`) VALUES
(1, 'Análisis y Desarrollo de Sistemas de Información', 'ADSI', 'Programa de formación en desarrollo de software'),
(2, 'Gestión de Redes de Computadores', 'GRC', 'Programa de formación en redes e infraestructura'),
(3, 'Mantenimiento de Equipos de Cómputo', 'MEC', 'Programa de formación en mantenimiento'),
(4, 'Soporte a Usuarios en Tecnología Informática', 'SUTI', 'Programa de formación en soporte técnico'),
(5, 'Programación de Software', 'PS', 'Programa de formación en programación avanzada');

-- Insertar fichas de prueba
INSERT IGNORE INTO `fichas` (`id_ficha`, `numero_ficha`, `programa`, `instructor`, `fecha_inicio`, `fecha_fin`, `estado`) VALUES
(1, '2024-001', 'Análisis y Desarrollo de Sistemas de Información', 'Instructor 1', '2024-01-15', '2024-08-15', 'Activa'),
(2, '2024-002', 'Gestión de Redes de Computadores', 'Instructor 2', '2024-02-01', '2024-07-30', 'Activa'),
(3, '2024-003', 'Mantenimiento de Equipos de Cómputo', 'Instructor 3', '2024-01-20', '2024-06-20', 'Finalizada');

-- Insertar aprendices de prueba
INSERT IGNORE INTO `aprendices` (`documento`, `nombres`, `apellidos`, `email`, `telefono`, `id_ficha`, `estado`) VALUES
('1020304050', 'Juan', 'Pérez', 'juan@example.com', '3101234567', 1, 'En formación'),
('1020304051', 'María', 'García', 'maria@example.com', '3101234568', 1, 'En formación'),
('1020304052', 'Carlos', 'López', 'carlos@example.com', '3101234569', 2, 'En formación'),
('1020304053', 'Ana', 'Martínez', 'ana@example.com', '3101234570', 2, 'Retirada'),
('1020304054', 'Pedro', 'González', 'pedro@example.com', '3101234571', 3, 'Egresado');

-- Insertar competencias de prueba
INSERT IGNORE INTO `competencias` (`codigo`, `nombre`, `descripcion`, `id_ficha`) VALUES
('COMP-001', 'Análisis de Requisitos', 'Capacidad para analizar y documentar requisitos de software', 1),
('COMP-002', 'Diseño de Sistemas', 'Capacidad para diseñar arquitecturas de software', 1),
('COMP-003', 'Implementación de Redes', 'Capacidad para implementar soluciones de red', 2),
('COMP-004', 'Administración de Servidores', 'Capacidad para administrar servidores Linux/Windows', 2);

-- Insertar juicios evaluativos de prueba
INSERT IGNORE INTO `juicios_evaluativos` (`id_aprendiz`, `id_competencia`, `resultado`, `estado`, `tipo_juicio`, `fecha_juicio`) VALUES
(1, 1, 'Aprobado', 'Finalizado', 'Evaluación', '2024-06-15'),
(1, 2, 'Por evaluar', 'Pendiente', 'Evaluación', NULL),
(2, 1, 'Aprobado', 'Finalizado', 'Evaluación', '2024-06-10'),
(3, 3, 'No aprobado', 'Finalizado', 'Evaluación', '2024-05-20'),
(4, 3, 'Aprobado', 'Finalizado', 'Evaluación', '2024-05-25');

-- ================================================================
