-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
-- Exportado desde XAMPP - Estructura de base de datos sena_juicios (SIN DATOS)
-- Compatible con MariaDB 10.11 en Docker

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE IF NOT EXISTS sena_juicios;
USE sena_juicios;

--
-- Table structure for table `actividades_fase`
--

DROP TABLE IF EXISTS `actividades_fase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actividades_fase` (
  `id_actividad` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` text NOT NULL,
  `descripcion` text DEFAULT NULL,
  `id_fase` int(11) NOT NULL,
  `id_ficha` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_actividad`),
  KEY `fk_actividades_fase` (`id_fase`),
  CONSTRAINT `fk_actividades_fase` FOREIGN KEY (`id_fase`) REFERENCES `fases_proyecto` (`id_fase`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aprendices`
--

DROP TABLE IF EXISTS `aprendices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `aprendices` (
  `documento` varchar(255) NOT NULL,
  `tipo_documento` varchar(255) NOT NULL,
  `nombres` varchar(255) NOT NULL,
  `apellidos` varchar(255) NOT NULL,
  `estado` enum('En formación','Retirado','Trasladado','Egresado') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'En formación',
  `id_ficha` int(11) NOT NULL,
  PRIMARY KEY (`documento`),
  UNIQUE KEY `documento` (`documento`),
  KEY `idx_aprendices_ficha` (`id_ficha`),
  KEY `idx_aprendices_estado` (`estado`),
  CONSTRAINT `fk_programas_id_ficha_aprendices` FOREIGN KEY (`id_ficha`) REFERENCES `programas` (`id_ficha`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cola_procesamiento`
--

DROP TABLE IF EXISTS `cola_procesamiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cola_procesamiento` (
  `id_tarea` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_tarea` varchar(50) NOT NULL,
  `datos_json` longtext NOT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'Pendiente',
  `intentos` int(11) NOT NULL DEFAULT 0,
  `mensaje_error` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_tarea`),
  KEY `idx_cola_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `competencias`
--

DROP TABLE IF EXISTS `competencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `competencias` (
  `id_competencia` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(255) DEFAULT NULL,
  `nombre` text NOT NULL,
  `id_aprendiz` varchar(255) NOT NULL,
  `id_ficha` int(11) DEFAULT NULL,
  `id_resultado` int(11) NOT NULL,
  PRIMARY KEY (`id_competencia`),
  KEY `fk_competencias_id_resultado_resultados` (`id_resultado`),
  KEY `aprendices_documento_competencias` (`id_aprendiz`),
  KEY `idx_competencias_ficha` (`id_ficha`),
  CONSTRAINT `aprendices_documento_competencias` FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`documento`) ON DELETE CASCADE,
  CONSTRAINT `fk_competencias_id_resultado_resultados` FOREIGN KEY (`id_resultado`) REFERENCES `resultados` (`id_resultado`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34215 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fase_competencia_resultado`
--

DROP TABLE IF EXISTS `fase_competencia_resultado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fase_competencia_resultado` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_actividad` int(11) NOT NULL,
  `id_ficha` int(11) DEFAULT NULL,
  `id_competencia` int(11) DEFAULT NULL,
  `id_resultado` int(11) DEFAULT NULL,
  `nombre_competencia` text DEFAULT NULL,
  `nombre_resultado` text DEFAULT NULL,
  `codigo_competencia` varchar(20) DEFAULT NULL,
  `codigo_resultado` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_fcr_actividad` (`id_actividad`),
  KEY `idx_fcr_ficha` (`id_ficha`),
  CONSTRAINT `fk_fcr_actividad` FOREIGN KEY (`id_actividad`) REFERENCES `actividades_fase` (`id_actividad`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1393 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fases_proyecto`
--

DROP TABLE IF EXISTS `fases_proyecto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fases_proyecto` (
  `id_fase` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_fase` varchar(255) NOT NULL,
  `orden` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `id_ficha` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_fase`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fichas`
--

DROP TABLE IF EXISTS `fichas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fichas` (
  `id_ficha` int(11) NOT NULL AUTO_INCREMENT,
  `numero_ficha` varchar(50) NOT NULL,
  `programa` varchar(255) NOT NULL,
  `instructor` varchar(255) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'Activa',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_ficha`),
  UNIQUE KEY `numero_ficha` (`numero_ficha`),
  KEY `idx_fichas_numero` (`numero_ficha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `funcionarios`
--

DROP TABLE IF EXISTS `funcionarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `funcionarios` (
  `documento` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  PRIMARY KEY (`documento`)
) ENGINE=InnoDB AUTO_INCREMENT=1117546315 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `juicios`
--

DROP TABLE IF EXISTS `juicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `juicios` (
  `id_juicio` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_juicio` enum('Aprobado','Por evaluar','No aprobado') NOT NULL,
  `fecha_juicio` datetime NOT NULL,
  `id_funcionario` int(11) NOT NULL,
  `id_ficha` int(11) DEFAULT NULL,
  `documento_aprendiz` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_juicio`),
  KEY `fk_juicios_funcionario` (`id_funcionario`),
  KEY `idx_juicios_ficha` (`id_ficha`),
  KEY `idx_juicios_aprendiz` (`documento_aprendiz`),
  CONSTRAINT `fk_juicios_funcionario` FOREIGN KEY (`id_funcionario`) REFERENCES `funcionarios` (`documento`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34215 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logs_importacion`
--

DROP TABLE IF EXISTS `logs_importacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs_importacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `fila` int(11) DEFAULT NULL,
  `mensaje_error` text NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  CONSTRAINT `logs_importacion_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `trabajos_importacion` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `programas`
--

DROP TABLE IF EXISTS `programas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programas` (
  `id_ficha` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `codigo_programa_sofia` varchar(20) DEFAULT NULL,
  `nombre_proyecto` text DEFAULT NULL,
  `centro_formacion` varchar(255) DEFAULT NULL,
  `regional` varchar(100) DEFAULT NULL,
  `tiempo_estimado_meses` int(11) DEFAULT NULL,
  `total_resultados` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_ficha`)
) ENGINE=InnoDB AUTO_INCREMENT=3407848 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `resultados`
--

DROP TABLE IF EXISTS `resultados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resultados` (
  `id_resultado` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(255) DEFAULT NULL,
  `nombre` text NOT NULL,
  `id_juicio` int(11) NOT NULL,
  PRIMARY KEY (`id_resultado`),
  KEY `fk_resultados_id_juicio_juicios` (`id_juicio`),
  CONSTRAINT `fk_resultados_id_juicio_juicios` FOREIGN KEY (`id_juicio`) REFERENCES `juicios` (`id_juicio`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34215 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `trabajos_importacion`
--

DROP TABLE IF EXISTS `trabajos_importacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trabajos_importacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) NOT NULL COMMENT 'Ej: excel_aprendices, pdf_fases',
  `ruta_archivo` varchar(255) NOT NULL,
  `estado` enum('pendiente','procesando','completado','error') DEFAULT 'pendiente',
  `progreso` int(11) DEFAULT 0,
  `resultado` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resultado`)),
  `errores` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `v_aprobacion_por_competencia`
--

DROP TABLE IF EXISTS `v_aprobacion_por_competencia`;
/*!50001 DROP VIEW IF EXISTS `v_aprobacion_por_competencia`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_aprobacion_por_competencia` AS SELECT
 1 AS `competencia`,
  1 AS `total_evaluaciones`,
  1 AS `aprobadas`,
  1 AS `porcentaje_aprobacion` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_dashboard_indicadores`
--

DROP TABLE IF EXISTS `v_dashboard_indicadores`;
/*!50001 DROP VIEW IF EXISTS `v_dashboard_indicadores`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_dashboard_indicadores` AS SELECT
 1 AS `total_aprendices_activos`,
  1 AS `total_retirados`,
  1 AS `total_trasladados`,
  1 AS `total_juicios_aprobados`,
  1 AS `total_juicios_por_evaluar`,
  1 AS `total_juicios_no_aprobados`,
  1 AS `total_programas`,
  1 AS `total_funcionarios` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_resumen_aprendiz`
--

DROP TABLE IF EXISTS `v_resumen_aprendiz`;
/*!50001 DROP VIEW IF EXISTS `v_resumen_aprendiz`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_resumen_aprendiz` AS SELECT
 1 AS `documento`,
  1 AS `nombre_completo`,
  1 AS `estado`,
  1 AS `programa`,
  1 AS `total_competencias`,
  1 AS `aprobados`,
  1 AS `por_evaluar`,
  1 AS `no_aprobados`,
  1 AS `porcentaje_avance` */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `v_aprobacion_por_competencia`
--

/*!50001 DROP VIEW IF EXISTS `v_aprobacion_por_competencia`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_aprobacion_por_competencia` AS select `c`.`nombre` AS `competencia`,count(`c`.`id_competencia`) AS `total_evaluaciones`,sum(`j`.`tipo_juicio` = 'Aprobado') AS `aprobadas`,round(sum(`j`.`tipo_juicio` = 'Aprobado') * 100.0 / nullif(count(`c`.`id_competencia`),0),2) AS `porcentaje_aprobacion` from ((`competencias` `c` join `resultados` `r` on(`c`.`id_resultado` = `r`.`id_resultado`)) join `juicios` `j` on(`r`.`id_juicio` = `j`.`id_juicio`)) group by `c`.`nombre` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_dashboard_indicadores`
--

/*!50001 DROP VIEW IF EXISTS `v_dashboard_indicadores`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_dashboard_indicadores` AS select (select count(0) from `aprendices` where `aprendices`.`estado` = 'En formación') AS `total_aprendices_activos`,(select count(0) from `aprendices` where `aprendices`.`estado` = 'Retirado') AS `total_retirados`,(select count(0) from `aprendices` where `aprendices`.`estado` = 'Trasladado') AS `total_trasladados`,(select count(0) from `juicios` where `juicios`.`tipo_juicio` = 'Aprobado') AS `total_juicios_aprobados`,(select count(0) from `juicios` where `juicios`.`tipo_juicio` = 'Por evaluar') AS `total_juicios_por_evaluar`,(select count(0) from `juicios` where `juicios`.`tipo_juicio` = 'No aprobado') AS `total_juicios_no_aprobados`,(select count(0) from `programas`) AS `total_programas`,(select count(0) from `funcionarios`) AS `total_funcionarios` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_resumen_aprendiz`
--

/*!50001 DROP VIEW IF EXISTS `v_resumen_aprendiz`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_resumen_aprendiz` AS select `a`.`documento` AS `documento`,concat(`a`.`nombres`,' ',`a`.`apellidos`) AS `nombre_completo`,`a`.`estado` AS `estado`,`p`.`nombre` AS `programa`,count(`c`.`id_competencia`) AS `total_competencias`,sum(`j`.`tipo_juicio` = 'Aprobado') AS `aprobados`,sum(`j`.`tipo_juicio` = 'Por evaluar') AS `por_evaluar`,sum(`j`.`tipo_juicio` = 'No aprobado') AS `no_aprobados`,round(sum(`j`.`tipo_juicio` = 'Aprobado') * 100.0 / nullif(count(`c`.`id_competencia`),0),2) AS `porcentaje_avance` from ((((`aprendices` `a` join `programas` `p` on(`a`.`id_ficha` = `p`.`id_ficha`)) join `competencias` `c` on(`a`.`documento` = `c`.`id_aprendiz`)) join `resultados` `r` on(`c`.`id_resultado` = `r`.`id_resultado`)) join `juicios` `j` on(`r`.`id_juicio` = `j`.`id_juicio`)) group by `a`.`documento`,concat(`a`.`nombres`,' ',`a`.`apellidos`),`a`.`estado`,`p`.`nombre` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
