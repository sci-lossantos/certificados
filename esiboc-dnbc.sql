-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-06-2025 a las 14:31:09
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `esiboc-dnbc`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `numero_registro` varchar(50) NOT NULL,
  `coordinador_id` int(11) NOT NULL,
  `escuela_id` int(11) NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `duracion_horas` int(11) DEFAULT NULL,
  `contenido_tematico` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `escala_calificacion` enum('0-5','0-100') DEFAULT '0-5'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id`, `nombre`, `descripcion`, `numero_registro`, `coordinador_id`, `escuela_id`, `fecha_inicio`, `fecha_fin`, `duracion_horas`, `contenido_tematico`, `activo`, `created_at`, `updated_at`, `escala_calificacion`) VALUES
(1, 'DESARROLLO DE CAPACIDADES PARA LA INSTRUCCION DE BOMBEROS', 'ESTS', '890-2025', 3, 2, '2025-06-06', '2025-06-09', 32, 'ESTE', 1, '2025-06-08 01:12:30', '2025-06-09 20:38:24', '0-5');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos`
--

CREATE TABLE `documentos` (
  `id` int(11) NOT NULL,
  `tipo` varchar(30) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `participante_id` int(11) DEFAULT NULL,
  `codigo_unico` varchar(100) NOT NULL,
  `codigo_verificacion` varchar(20) DEFAULT NULL,
  `contenido` text DEFAULT NULL,
  `archivo_pdf` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `generado_por` int(11) DEFAULT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'generado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `documentos`
--

INSERT INTO `documentos` (`id`, `tipo`, `curso_id`, `participante_id`, `codigo_unico`, `codigo_verificacion`, `contenido`, `archivo_pdf`, `created_at`, `updated_at`, `generado_por`, `estado`) VALUES
(1, 'acta', 1, NULL, 'ACTA-2025-0001-68464fb37b0ab', 'VER-682571B7', NULL, NULL, '2025-06-09 03:06:27', '2025-06-09 17:37:57', 2, 'firmado_director_nacional'),
(2, 'informe', 1, NULL, 'INFORME-2025-0001-6846501ec2220', 'VER-48B53721', NULL, NULL, '2025-06-09 03:08:14', '2025-06-09 17:37:57', 2, 'revisado_educacion_dnbc'),
(3, 'certificado', 1, NULL, 'CERTIFICADO-2025-0001-684650322dee3', 'VER-11A008AD', NULL, NULL, '2025-06-09 03:08:34', '2025-06-09 17:37:57', 2, 'firmado_director_nacional'),
(4, 'directorio', 1, NULL, 'DIRECTORIO-2025-0001-6846503901f2d', 'VER-42198159', NULL, NULL, '2025-06-09 03:08:41', '2025-06-09 17:37:57', 2, 'revisado_educacion_dnbc'),
(5, 'certificado', 1, NULL, 'CERTIFICADO-2025-0001-68471f3b57a5a', NULL, NULL, NULL, '2025-06-09 17:51:55', '2025-06-09 18:20:19', 2, 'firmado_coordinador'),
(6, 'certificado', 1, NULL, 'CERTIFICADO-2025-0001-6847256daac4c', NULL, NULL, NULL, '2025-06-09 18:18:21', '2025-06-09 18:20:34', 2, 'firmado_coordinador'),
(7, 'directorio', 1, NULL, 'DIRECTORIO-2025-0001-68473b36a840f', NULL, NULL, NULL, '2025-06-09 19:51:18', '2025-06-09 19:51:18', 2, 'generado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos_backup`
--

CREATE TABLE `documentos_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `tipo` varchar(30) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `participante_id` int(11) DEFAULT NULL,
  `codigo_unico` varchar(100) NOT NULL,
  `contenido` text DEFAULT NULL,
  `archivo_pdf` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `generado_por` int(11) DEFAULT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'generado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `documentos_backup`
--

INSERT INTO `documentos_backup` (`id`, `tipo`, `curso_id`, `participante_id`, `codigo_unico`, `contenido`, `archivo_pdf`, `created_at`, `updated_at`, `generado_por`, `estado`) VALUES
(1, 'directorio', 1, NULL, 'DOC-684504b1a0f9d0.59894553', NULL, NULL, '2025-06-08 03:34:09', '2025-06-08 19:52:16', 2, 'aprobado_educacion_dnbc'),
(2, 'directorio', 1, NULL, 'DOC-68457b52bdafe0.36231143', NULL, NULL, '2025-06-08 12:00:18', '2025-06-08 19:52:24', 2, 'aprobado_educacion_dnbc'),
(3, 'directorio', 1, NULL, 'DOC-68457e0abfc2a2.57231155', NULL, NULL, '2025-06-08 12:11:54', '2025-06-08 19:52:29', 2, 'aprobado_educacion_dnbc'),
(4, 'informe', 1, NULL, 'DOC-6845a6c95d7ad6.88689651', NULL, NULL, '2025-06-08 15:05:45', '2025-06-08 19:52:34', 2, 'aprobado_educacion_dnbc'),
(5, 'acta', 1, NULL, 'ACTA-2025-0001-6845dfc7d496e', NULL, NULL, '2025-06-08 19:08:55', '2025-06-08 19:54:42', 2, 'firmado_director_nacional'),
(6, 'acta', 1, NULL, 'ACTA-2025-0001-6845edb937268', NULL, NULL, '2025-06-08 20:08:25', '2025-06-08 20:25:40', 2, 'firmado_director_nacional'),
(7, 'acta', 1, NULL, 'ACTA-2025-0001-6845f2989e774', NULL, NULL, '2025-06-08 20:29:12', '2025-06-08 20:36:41', 2, 'firmado_director_nacional'),
(8, 'acta', 1, NULL, 'ACTA-2025-0001-6846066a9a0e0', NULL, NULL, '2025-06-08 21:53:46', '2025-06-08 23:04:44', 2, 'firmado_director_nacional'),
(9, 'directorio', 1, NULL, 'DIRECTORIO-2025-0001-68461068cafff', NULL, NULL, '2025-06-08 22:36:24', '2025-06-08 22:43:29', 2, 'revisado_educacion_dnbc'),
(10, 'directorio', 1, NULL, 'DIRECTORIO-2025-0001-6846191f0b42e', NULL, NULL, '2025-06-08 23:13:35', '2025-06-08 23:13:35', 2, 'generado'),
(11, 'directorio', 1, NULL, 'DIRECTORIO-2025-0001-684620f986328', NULL, NULL, '2025-06-08 23:47:05', '2025-06-08 23:47:05', 2, 'generado'),
(12, 'acta', 1, NULL, 'ACTA-2025-0001-684621a11c089', NULL, NULL, '2025-06-08 23:49:53', '2025-06-08 23:49:53', 2, 'generado'),
(13, 'directorio', 1, NULL, 'DIRECTORIO-2025-0001-68462284d654c', NULL, NULL, '2025-06-08 23:53:40', '2025-06-08 23:53:40', 2, 'generado'),
(14, 'directorio', 1, NULL, 'DIRECTORIO-2025-0001-68462ba678cbc', NULL, NULL, '2025-06-09 00:32:38', '2025-06-09 00:32:38', 2, 'generado'),
(15, 'directorio', 1, NULL, 'DIRECTORIO-2025-0001-68462f7379dd6', NULL, NULL, '2025-06-09 00:48:51', '2025-06-09 00:48:51', 2, 'generado'),
(16, 'directorio', 1, NULL, 'DIRECTORIO-2025-0001-684632af33f07', NULL, NULL, '2025-06-09 01:02:39', '2025-06-09 01:02:39', 2, 'generado'),
(17, 'informe', 1, NULL, 'INFORME-2025-0001-68463c7880d17', NULL, NULL, '2025-06-09 01:44:24', '2025-06-09 01:44:24', 2, 'generado'),
(18, 'informe', 1, NULL, 'INFORME-2025-0001-6846453d92ed7', NULL, NULL, '2025-06-09 02:21:49', '2025-06-09 02:48:35', 2, 'firmado_coordinador'),
(19, 'acta', 1, NULL, 'ACTA-2025-0001-68464a63d5eac', NULL, NULL, '2025-06-09 02:43:47', '2025-06-09 02:43:47', 2, 'generado'),
(20, 'informe', 1, NULL, 'INFORME-2025-0001-68464c35db401', NULL, NULL, '2025-06-09 02:51:33', '2025-06-09 02:51:33', 2, 'generado'),
(21, 'informe', 1, NULL, 'INFORME-2025-0001-68464c7c491aa', NULL, NULL, '2025-06-09 02:52:44', '2025-06-09 02:54:08', 2, 'firmado_coordinador'),
(22, 'acta', 1, NULL, 'ACTA-2025-0001-68464c8e78cea', NULL, NULL, '2025-06-09 02:53:02', '2025-06-09 02:53:02', 2, 'generado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `escuelas`
--

CREATE TABLE `escuelas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `director_id` int(11) DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `nombre_completo` text DEFAULT NULL,
  `nombre_estacion` varchar(200) DEFAULT NULL,
  `codigo_formato` varchar(50) DEFAULT NULL,
  `version_formato` varchar(10) DEFAULT '1',
  `fecha_vigencia` date DEFAULT NULL,
  `logo_institucional` varchar(255) DEFAULT NULL,
  `pie_pagina` text DEFAULT NULL,
  `slogan` varchar(200) DEFAULT NULL,
  `director` varchar(255) DEFAULT NULL,
  `coordinador` varchar(255) DEFAULT NULL,
  `lema` text DEFAULT NULL,
  `mision` text DEFAULT NULL,
  `vision` text DEFAULT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `coordinador_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `escuelas`
--

INSERT INTO `escuelas` (`id`, `nombre`, `codigo`, `direccion`, `telefono`, `email`, `director_id`, `activa`, `created_at`, `nombre_completo`, `nombre_estacion`, `codigo_formato`, `version_formato`, `fecha_vigencia`, `logo_institucional`, `pie_pagina`, `slogan`, `director`, `coordinador`, `lema`, `mision`, `vision`, `logo`, `coordinador_id`) VALUES
(1, 'Escuela Nacional de Bomberos', 'ENB001', 'Bogotá D.C., Colombia', '+57 1 234 5678', 'escuela@bomberos.gov.co', NULL, 1, '2025-06-07 05:53:31', 'BOMBEROS VOLUNTARIOS LOS SANTOS', 'ESTACION DE BOMBEROS CT. JAIME DIAZ CAMARGO', 'ESIBOC-FO-03', '1', '2024-12-14', NULL, 'CUERPO BOMBEROS VOLUNTARIOS LOS SANTOS\nESCUELA INTERNACIONAL DE BOMBEROS DEL ORIENTE COLOMBIANO', 'FORMATO DIRECTORIO FINALIZACIÓN DE CURSO', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'ESCUELA INTERNACIONAL DE BOMBEROS DEL ORIENTE COLOMBIANO ESIBOC', '123', 'Carrera 22C # 20C - 33', '3003272507', 'esiboc@esiboc.com', NULL, 1, '2025-06-08 00:43:04', 'CUERPO DE BOMBEROS VOLUNTARIOS LOS SANTOS', 'ESTACION EJEMPLO', 'ESIBOC-F-045', '1', '2025-04-27', NULL, 'INFORMACION DE PIE DE PAGINA', 'FORMATO EJEMPLO PARA EL DIRECTORIO', 'MANUEL ENRIQUE SALAZAR', 'JORGE SERRANO', '', '', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `firmas_documentos`
--

CREATE TABLE `firmas_documentos` (
  `id` int(11) NOT NULL,
  `documento_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_firma` varchar(30) NOT NULL,
  `accion` varchar(20) NOT NULL DEFAULT 'firma',
  `fecha_firma` timestamp NOT NULL DEFAULT current_timestamp(),
  `hash_firma` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `es_rechazo` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `firmas_documentos`
--

INSERT INTO `firmas_documentos` (`id`, `documento_id`, `usuario_id`, `tipo_firma`, `accion`, `fecha_firma`, `hash_firma`, `observaciones`, `es_rechazo`) VALUES
(1, 1, 3, 'coordinador', 'firma', '2025-06-09 03:06:51', NULL, '', 0),
(3, 3, 3, 'coordinador', 'firma', '2025-06-09 03:09:52', NULL, '', 0),
(4, 4, 3, 'coordinador', 'revision', '2025-06-09 03:09:58', NULL, '', 0),
(5, 2, 2, 'correccion', 'correccion', '2025-06-09 03:11:00', NULL, 'Documento corregido y reenviado', 0),
(6, 2, 3, 'coordinador', 'firma', '2025-06-09 03:11:10', NULL, '', 0),
(7, 4, 4, 'director_escuela', 'revision', '2025-06-09 04:19:42', NULL, '', 0),
(8, 3, 4, 'director_escuela', 'firma', '2025-06-09 04:19:54', NULL, '', 0),
(9, 2, 4, 'director_escuela', 'revision', '2025-06-09 04:20:43', NULL, '', 0),
(10, 1, 4, 'director_escuela', 'firma', '2025-06-09 04:20:50', NULL, '', 0),
(11, 1, 6, 'educacion_dnbc', 'revision', '2025-06-09 04:21:24', NULL, '', 0),
(12, 2, 6, 'educacion_dnbc', 'revision', '2025-06-09 04:21:29', NULL, '', 0),
(13, 3, 6, 'educacion_dnbc', 'revision', '2025-06-09 04:21:35', NULL, '', 0),
(14, 4, 6, 'educacion_dnbc', 'revision', '2025-06-09 04:21:39', NULL, '', 0),
(15, 3, 5, 'director_nacional', 'firma', '2025-06-09 04:22:31', NULL, '', 0),
(16, 1, 5, 'director_nacional', 'firma', '2025-06-09 04:23:03', NULL, '', 0),
(17, 5, 3, 'coordinador', 'firma', '2025-06-09 18:20:19', NULL, '', 0),
(18, 6, 3, 'coordinador', 'firma', '2025-06-09 18:20:34', NULL, '', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `firmas_documentos_backup`
--

CREATE TABLE `firmas_documentos_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `documento_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_firma` varchar(30) NOT NULL,
  `accion` varchar(20) NOT NULL DEFAULT 'firma',
  `fecha_firma` timestamp NOT NULL DEFAULT current_timestamp(),
  `hash_firma` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `es_rechazo` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `firmas_documentos_backup`
--

INSERT INTO `firmas_documentos_backup` (`id`, `documento_id`, `usuario_id`, `tipo_firma`, `accion`, `fecha_firma`, `hash_firma`, `observaciones`, `es_rechazo`) VALUES
(1, 4, 3, 'coordinador', 'firma', '2025-06-08 15:07:46', NULL, '', 0),
(2, 3, 3, 'coordinador', 'firma', '2025-06-08 15:08:01', NULL, '', 0),
(3, 2, 3, 'coordinador', 'firma', '2025-06-08 15:08:07', NULL, '', 0),
(4, 1, 3, 'coordinador', 'firma', '2025-06-08 15:08:11', NULL, '', 0),
(5, 1, 4, 'director_escuela', 'revision', '2025-06-08 18:58:37', NULL, '', 0),
(6, 2, 4, 'director_escuela', 'revision', '2025-06-08 18:58:44', NULL, '', 0),
(7, 3, 4, 'director_escuela', 'revision', '2025-06-08 18:58:50', NULL, '', 0),
(8, 4, 4, 'director_escuela', 'revision', '2025-06-08 18:59:36', NULL, '', 0),
(9, 5, 3, 'coordinador', 'firma', '2025-06-08 19:10:07', NULL, 'prueba firma coordinador', 0),
(10, 5, 4, 'director_escuela', 'firma', '2025-06-08 19:29:11', NULL, 'FIRMA DIRECTOR ESCUELA', 0),
(11, 1, 6, 'educacion_dnbc', 'revision', '2025-06-08 19:52:16', NULL, '', 0),
(12, 2, 6, 'educacion_dnbc', 'revision', '2025-06-08 19:52:24', NULL, '', 0),
(13, 3, 6, 'educacion_dnbc', 'revision', '2025-06-08 19:52:29', NULL, '', 0),
(14, 4, 6, 'educacion_dnbc', 'revision', '2025-06-08 19:52:34', NULL, '', 0),
(15, 5, 6, 'educacion_dnbc', 'revision', '2025-06-08 19:52:38', NULL, '', 0),
(16, 5, 5, 'director_nacional', 'firma', '2025-06-08 19:54:42', NULL, '', 0),
(17, 6, 3, 'coordinador', 'firma', '2025-06-08 20:10:07', NULL, '', 0),
(18, 6, 4, 'director_escuela', 'firma', '2025-06-08 20:11:51', NULL, '', 0),
(20, 6, 6, 'educacion_dnbc', 'revision', '2025-06-08 20:22:14', NULL, '', 0),
(21, 6, 5, 'director_nacional', 'firma', '2025-06-08 20:25:40', NULL, '', 0),
(22, 7, 3, 'coordinador', 'firma', '2025-06-08 20:29:57', NULL, '', 0),
(23, 7, 4, 'director_escuela', 'firma', '2025-06-08 20:30:33', NULL, '', 0),
(24, 7, 6, 'educacion_dnbc', 'revision', '2025-06-08 20:31:17', NULL, '', 0),
(25, 7, 5, 'director_nacional', 'firma', '2025-06-08 20:36:41', NULL, '', 0),
(36, 9, 2, 'correccion', 'correccion', '2025-06-08 22:41:44', NULL, 'Documento corregido y reenviado', 0),
(37, 9, 3, 'coordinador', 'revision', '2025-06-08 22:42:35', NULL, '', 0),
(38, 9, 4, 'director_escuela', 'revision', '2025-06-08 22:42:49', NULL, '', 0),
(39, 9, 6, 'educacion_dnbc', 'revision', '2025-06-08 22:43:29', NULL, '', 0),
(40, 8, 3, 'correccion', 'correccion', '2025-06-08 22:56:50', NULL, 'Documento corregido y reenviado', 0),
(41, 8, 3, 'coordinador', 'firma', '2025-06-08 23:03:12', NULL, '', 0),
(42, 8, 4, 'director_escuela', 'firma', '2025-06-08 23:03:52', NULL, '', 0),
(43, 8, 6, 'educacion_dnbc', 'revision', '2025-06-08 23:04:24', NULL, '', 0),
(44, 8, 5, 'director_nacional', 'firma', '2025-06-08 23:04:44', NULL, '', 0),
(45, 18, 3, 'coordinador', 'firma', '2025-06-09 02:48:35', NULL, '', 0),
(46, 21, 3, 'coordinador', 'firma', '2025-06-09 02:54:08', NULL, '', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `firmas_usuarios`
--

CREATE TABLE `firmas_usuarios` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_firma` varchar(20) NOT NULL,
  `contenido_firma` text DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `firmas_usuarios`
--

INSERT INTO `firmas_usuarios` (`id`, `usuario_id`, `tipo_firma`, `contenido_firma`, `activa`, `created_at`, `updated_at`) VALUES
(3, 3, 'upload', 'uploads/firmas/firma_3_1749438453.png', 1, '2025-06-09 03:07:33', '2025-06-09 03:07:33'),
(4, 4, 'upload', 'uploads/firmas/firma_4_1749442832.png', 1, '2025-06-09 04:20:32', '2025-06-09 04:20:32'),
(5, 5, 'upload', 'uploads/firmas/firma_5_1749442941.png', 1, '2025-06-09 04:22:21', '2025-06-09 04:22:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `instructores_curso`
--

CREATE TABLE `instructores_curso` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('coordinador','instructor','logistica') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `instructores_cursos`
--

CREATE TABLE `instructores_cursos` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `tipo_instructor` enum('coordinador','instructor','auxiliar') DEFAULT 'instructor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `instructores_cursos`
--

INSERT INTO `instructores_cursos` (`id`, `curso_id`, `instructor_id`, `tipo_instructor`, `created_at`) VALUES
(1, 1, 3, 'coordinador', '2025-06-09 00:19:26'),
(2, 1, 7, 'instructor', '2025-06-09 00:32:06'),
(3, 1, 8, 'instructor', '2025-06-09 00:50:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `matriculas`
--

CREATE TABLE `matriculas` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `participante_id` int(11) NOT NULL,
  `fecha_matricula` timestamp NOT NULL DEFAULT current_timestamp(),
  `calificacion` decimal(5,2) DEFAULT NULL,
  `aprobado` tinyint(1) DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `matriculas`
--

INSERT INTO `matriculas` (`id`, `curso_id`, `participante_id`, `fecha_matricula`, `calificacion`, `aprobado`, `observaciones`, `updated_at`, `created_at`) VALUES
(1, 1, 3, '2025-06-08 01:34:31', 5.00, 1, NULL, '2025-06-09 21:15:09', '2025-06-08 01:34:31'),
(2, 1, 1, '2025-06-08 01:39:24', 5.00, 1, NULL, '2025-06-09 21:15:09', '2025-06-08 01:39:24'),
(3, 1, 4, '2025-06-08 01:39:24', 5.00, 1, NULL, '2025-06-09 21:15:09', '2025-06-08 01:39:24'),
(4, 1, 2, '2025-06-08 01:39:24', 5.00, 1, NULL, '2025-06-09 21:15:09', '2025-06-08 01:39:24'),
(5, 1, 5, '2025-06-09 21:11:17', 4.70, 1, NULL, '2025-06-09 21:15:09', '2025-06-09 21:11:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participantes`
--

CREATE TABLE `participantes` (
  `id` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `institucion` varchar(100) DEFAULT NULL,
  `genero` enum('M','F','Masculino','Femenino','Otro') DEFAULT NULL,
  `fotografia` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `entidad` varchar(200) DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `foto` longtext DEFAULT NULL COMMENT 'Foto del participante en base64'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `participantes`
--

INSERT INTO `participantes` (`id`, `nombres`, `apellidos`, `cedula`, `email`, `telefono`, `institucion`, `genero`, `fotografia`, `password_hash`, `activo`, `created_at`, `entidad`, `celular`, `foto`) VALUES
(1, 'JUAN', 'RUEDA', '1234567', 'prof.jorgeserrano@gmail.com', '3003272507', 'CBV LOS SANTOS', 'M', 'uploads/fotografias/participante_1234567_1749439401.jpeg', '$2y$10$5UQuGntwhTvx2XVUjp/4jeII5HB57zQeihNneWC3GqhzTpiai3UFu', 1, '2025-06-07 06:14:06', 'CBV LOS SANTOS', '3003272507', NULL),
(2, 'jorge', 'Apellidos', 'Cédula', 'Email@es.com', 'Teléfono', 'Institución', 'Otro', 'uploads/fotografias/participante_Cédula_1749439380.jpeg', '$2y$10$EuJUp3GEUtoVbL5gBNcWyuC8SmoBDQL85wbUlui0KwiUYXYiJSt9y', 1, '2025-06-08 01:25:54', 'Institución', 'Teléfono', NULL),
(3, 'Juan', 'Pérez', '12345678', 'juan.perez@email.com', '3001234567', 'Bomberos LOS SANTOS', 'M', 'uploads/fotografias/participante_12345678_1749439324.jpg', '$2y$10$lvO7ecbjvmjnIgEEpjsMDeo08niQqTxxlWr4X4.i0jjQ1yFhXSFwO', 1, '2025-06-08 01:25:55', 'Bomberos LOS SANTOS', '3001234567', NULL),
(4, 'María', 'García', '87654321', 'maria.garcia@email.com', '3007654321', 'Bomberos Medellín', 'F', 'uploads/fotografias/participante_87654321_1749439338.png', '$2y$10$VJSpYxRqMZXsx4pvbvUR/uNoIGzhEq1RBGWnCiskmuqFAUW0Wh9P.', 1, '2025-06-08 01:25:55', 'Bomberos Medellín', '3007654321', NULL),
(5, 'Andrey Felipe', 'FERNANDEZ', '65678', 'andrey@f.com', '3003272507', '123', 'M', 'uploads/fotografias/participante_65678_1749503431.png', '$2y$10$8zvhYxZSBl8i3YKfranGWu7edeO31MsYorzJSFWagLPQOyjVurbfS', 1, '2025-06-09 21:10:31', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `created_at`) VALUES
(1, 'Administrador General', 'Administrador con acceso completo al sistema', '2025-06-07 05:53:31'),
(2, 'Dirección Nacional', 'Director Nacional con capacidad de firma final', '2025-06-07 05:53:31'),
(3, 'Educación DNBC', 'Personal de educación para revisión y aprobación', '2025-06-07 05:53:31'),
(4, 'Escuela', 'Personal administrativo de escuela', '2025-06-07 05:53:31'),
(5, 'Director de Escuela', 'Director de escuela con capacidad de firma', '2025-06-07 05:53:31'),
(6, 'Coordinador', 'Coordinador de cursos con capacidad de calificación', '2025-06-07 05:53:31'),
(7, 'Participante', 'Bombero participante en cursos', '2025-06-07 05:53:31'),
(8, 'Instructor', 'Instructor de cursos de formación', '2025-06-09 00:30:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `institucion` varchar(100) DEFAULT NULL,
  `genero` enum('M','F','Otro') DEFAULT NULL,
  `fotografia` varchar(255) DEFAULT NULL,
  `firma_digital` varchar(255) DEFAULT NULL,
  `tipo_firma` enum('imagen','criptografica') DEFAULT 'imagen',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `escuela_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombres`, `apellidos`, `cedula`, `email`, `password_hash`, `rol_id`, `telefono`, `institucion`, `genero`, `fotografia`, `firma_digital`, `tipo_firma`, `activo`, `created_at`, `updated_at`, `escuela_id`) VALUES
(1, 'Administrador', 'Sistema', '00000000', 'admin@esiboc.gov.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NULL, NULL, NULL, NULL, NULL, 'imagen', 1, '2025-06-07 05:53:31', '2025-06-08 15:03:23', 1),
(2, 'JORGE ELIECER', 'SERRANO', '91355840', 'serranojorge.st@gmail.com', '$2y$10$qoWxjLYRILXmu0C6L5cJj.N0ywpuEyEW34T8TOGzS2dTi.5el2fQW', 4, '3003272507', 'ESCUELA INTERNACIONAL DE BOMBERO SDEL ORIENTE COLOMBIANO', 'M', 'uploads/fotos_usuarios/usuario_2_1749470675.png', NULL, 'imagen', 1, '2025-06-07 06:07:00', '2025-06-09 12:04:35', 2),
(3, 'ISABEL', 'RUEDA', '1098625923', 'irz1098@hotmail.com', '$2y$10$R32MuECPa/uU9FM/r6z/Ruzj6X6nJNb75j/KwsUuhWhxcnCGCzdZm', 6, '3003272507', 'CBV LOS SANTOS', 'F', 'uploads/fotos_usuarios/usuario_3_1749440339.jpg', NULL, 'imagen', 1, '2025-06-08 01:11:37', '2025-06-09 03:38:59', NULL),
(4, 'MANUEL ENRIQUE', 'SALAZAR HERNANDEZ', '13722107', 'manuel@c.com', '$2y$10$0WRjF/pvBrGqv7KnTaXrGOVnnG6TYe/0HuKkskfXavFuFQcoTOy8C', 5, '3176679257', 'CBV LOS SANTOS', 'M', 'uploads/fotos_usuarios/usuario_4_1749432896.png', NULL, 'imagen', 1, '2025-06-08 18:17:26', '2025-06-09 01:34:56', 2),
(5, 'LINA MARIA', 'MARIN', '32345', 'lina@m.com', '$2y$10$Lpt.7CJ1LRJsjaZoCXjs3.iRZsaRYjLtxnyeHiZOcKuBNe28QfNjy', 2, '3001234567', 'CBV PEREIRA', 'F', NULL, NULL, 'imagen', 1, '2025-06-08 19:46:46', '2025-06-08 19:46:46', NULL),
(6, 'MASSIEL', 'FERNANDEZ', '7654', 'massiel@f.com', '$2y$10$RJJKvVKK6/Lr3uOIKBDFU.ZTwpaPg8Xk8kXcxaEnKTR5Tbua2hMRi', 3, '3001234567', 'CBV FLORIDABLANCA', 'F', NULL, NULL, 'imagen', 1, '2025-06-08 19:51:06', '2025-06-08 19:51:06', NULL),
(7, 'PEPITO', 'PEREZ', '0987', 'pepito@p.com', '$2y$10$uEbCRxUpKAF06CXD5zzc7.mxIDgZjTMTrApDrikFxUBnmtiRw5Sly', 8, '3176679257', 'CBV FLORIDABLANCA', 'M', 'uploads/fotos_usuarios/usuario_7_1749432929.jpg', NULL, 'imagen', 1, '2025-06-09 00:31:52', '2025-06-09 01:35:29', NULL),
(8, 'JUANC', 'RUEDA', '3456', 'juan.c@p.com', '$2y$10$eXmZV/PVExVkgsS9THMLG.NypCzj3ntHj5aTH2BYEsg2mAnqxkO4K', 8, '3001234567', 'Bomberos LOS SANTOS', 'M', 'uploads/fotos_usuarios/usuario_8_1749432913.png', NULL, 'imagen', 1, '2025-06-09 00:50:31', '2025-06-09 01:35:13', NULL);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_documentos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_documentos` (
`id` int(11)
,`tipo` varchar(30)
,`codigo_unico` varchar(100)
,`estado` varchar(50)
,`created_at` timestamp
,`curso_id` int(11)
,`participante_id` int(11)
,`generado_por` int(11)
,`curso_nombre` varchar(200)
,`numero_registro` varchar(50)
,`fecha_inicio` date
,`fecha_fin` date
,`escuela_nombre` varchar(100)
,`escuela_codigo` varchar(20)
,`generado_por_nombre` varchar(201)
,`participante_nombres` varchar(100)
,`participante_apellidos` varchar(100)
,`participante_cedula` varchar(20)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_flujo_documentos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_flujo_documentos` (
`id` int(11)
,`tipo` varchar(30)
,`codigo_unico` varchar(100)
,`estado` varchar(50)
,`created_at` timestamp
,`curso_nombre` varchar(200)
,`numero_registro` varchar(50)
,`escuela_nombre` varchar(100)
,`coordinador_nombre` varchar(201)
,`director_escuela_nombre` varchar(201)
,`firmado_coordinador` bigint(21)
,`firmado_director_escuela` bigint(21)
,`aprobado_educacion` bigint(21)
,`firmado_director_nacional` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_documentos`
--
DROP TABLE IF EXISTS `vista_documentos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_documentos`  AS SELECT `d`.`id` AS `id`, `d`.`tipo` AS `tipo`, `d`.`codigo_unico` AS `codigo_unico`, `d`.`estado` AS `estado`, `d`.`created_at` AS `created_at`, `d`.`curso_id` AS `curso_id`, `d`.`participante_id` AS `participante_id`, `d`.`generado_por` AS `generado_por`, `c`.`nombre` AS `curso_nombre`, `c`.`numero_registro` AS `numero_registro`, `c`.`fecha_inicio` AS `fecha_inicio`, `c`.`fecha_fin` AS `fecha_fin`, `e`.`nombre` AS `escuela_nombre`, `e`.`codigo` AS `escuela_codigo`, concat(`u`.`nombres`,' ',`u`.`apellidos`) AS `generado_por_nombre`, `p`.`nombres` AS `participante_nombres`, `p`.`apellidos` AS `participante_apellidos`, `p`.`cedula` AS `participante_cedula` FROM ((((`documentos` `d` join `cursos` `c` on(`d`.`curso_id` = `c`.`id`)) join `escuelas` `e` on(`c`.`escuela_id` = `e`.`id`)) left join `usuarios` `u` on(`d`.`generado_por` = `u`.`id`)) left join `participantes` `p` on(`d`.`participante_id` = `p`.`id`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_flujo_documentos`
--
DROP TABLE IF EXISTS `vista_flujo_documentos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_flujo_documentos`  AS SELECT `d`.`id` AS `id`, `d`.`tipo` AS `tipo`, `d`.`codigo_unico` AS `codigo_unico`, `d`.`estado` AS `estado`, `d`.`created_at` AS `created_at`, `c`.`nombre` AS `curso_nombre`, `c`.`numero_registro` AS `numero_registro`, `e`.`nombre` AS `escuela_nombre`, concat(`coord`.`nombres`,' ',`coord`.`apellidos`) AS `coordinador_nombre`, concat(`dir_esc`.`nombres`,' ',`dir_esc`.`apellidos`) AS `director_escuela_nombre`, (select count(0) from `firmas_documentos` `fd` where `fd`.`documento_id` = `d`.`id` and `fd`.`tipo_firma` = 'coordinador') AS `firmado_coordinador`, (select count(0) from `firmas_documentos` `fd` where `fd`.`documento_id` = `d`.`id` and `fd`.`tipo_firma` = 'director_escuela') AS `firmado_director_escuela`, (select count(0) from `firmas_documentos` `fd` where `fd`.`documento_id` = `d`.`id` and `fd`.`tipo_firma` = 'educacion_dnbc') AS `aprobado_educacion`, (select count(0) from `firmas_documentos` `fd` where `fd`.`documento_id` = `d`.`id` and `fd`.`tipo_firma` = 'director_nacional') AS `firmado_director_nacional` FROM ((((`documentos` `d` join `cursos` `c` on(`d`.`curso_id` = `c`.`id`)) join `escuelas` `e` on(`c`.`escuela_id` = `e`.`id`)) left join `usuarios` `coord` on(`c`.`coordinador_id` = `coord`.`id`)) left join `usuarios` `dir_esc` on(`e`.`director_id` = `dir_esc`.`id`)) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_registro` (`numero_registro`),
  ADD KEY `coordinador_id` (`coordinador_id`),
  ADD KEY `escuela_id` (`escuela_id`),
  ADD KEY `idx_cursos_escala` (`escala_calificacion`);

--
-- Indices de la tabla `documentos`
--
ALTER TABLE `documentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_unico` (`codigo_unico`),
  ADD UNIQUE KEY `codigo_verificacion` (`codigo_verificacion`),
  ADD KEY `curso_id` (`curso_id`),
  ADD KEY `participante_id` (`participante_id`),
  ADD KEY `fk_documentos_generado_por` (`generado_por`),
  ADD KEY `idx_codigo_verificacion` (`codigo_verificacion`);

--
-- Indices de la tabla `escuelas`
--
ALTER TABLE `escuelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_escuelas_director` (`director_id`),
  ADD KEY `idx_escuelas_coordinador` (`coordinador_id`);

--
-- Indices de la tabla `firmas_documentos`
--
ALTER TABLE `firmas_documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `documento_id` (`documento_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_firmas_rechazo` (`es_rechazo`);

--
-- Indices de la tabla `firmas_usuarios`
--
ALTER TABLE `firmas_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_usuario_activa` (`usuario_id`,`activa`);

--
-- Indices de la tabla `instructores_curso`
--
ALTER TABLE `instructores_curso`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_instructor_curso` (`curso_id`,`usuario_id`,`tipo`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `instructores_cursos`
--
ALTER TABLE `instructores_cursos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_instructor_curso` (`curso_id`,`instructor_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indices de la tabla `matriculas`
--
ALTER TABLE `matriculas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_matricula` (`curso_id`,`participante_id`),
  ADD KEY `participante_id` (`participante_id`);

--
-- Indices de la tabla `participantes`
--
ALTER TABLE `participantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD KEY `idx_participante_cedula` (`cedula`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `rol_id` (`rol_id`),
  ADD KEY `fk_usuarios_escuela` (`escuela_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `escuelas`
--
ALTER TABLE `escuelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `firmas_documentos`
--
ALTER TABLE `firmas_documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `firmas_usuarios`
--
ALTER TABLE `firmas_usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `instructores_curso`
--
ALTER TABLE `instructores_curso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `instructores_cursos`
--
ALTER TABLE `instructores_cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `matriculas`
--
ALTER TABLE `matriculas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `participantes`
--
ALTER TABLE `participantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`coordinador_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `cursos_ibfk_2` FOREIGN KEY (`escuela_id`) REFERENCES `escuelas` (`id`);

--
-- Filtros para la tabla `documentos`
--
ALTER TABLE `documentos`
  ADD CONSTRAINT `documentos_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`),
  ADD CONSTRAINT `documentos_ibfk_2` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`),
  ADD CONSTRAINT `fk_documentos_generado_por` FOREIGN KEY (`generado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `escuelas`
--
ALTER TABLE `escuelas`
  ADD CONSTRAINT `escuelas_ibfk_1` FOREIGN KEY (`director_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_escuelas_coordinador` FOREIGN KEY (`coordinador_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_escuelas_director` FOREIGN KEY (`director_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `firmas_documentos`
--
ALTER TABLE `firmas_documentos`
  ADD CONSTRAINT `firmas_documentos_ibfk_1` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`),
  ADD CONSTRAINT `firmas_documentos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `firmas_usuarios`
--
ALTER TABLE `firmas_usuarios`
  ADD CONSTRAINT `firmas_usuarios_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `instructores_curso`
--
ALTER TABLE `instructores_curso`
  ADD CONSTRAINT `instructores_curso_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`),
  ADD CONSTRAINT `instructores_curso_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `instructores_cursos`
--
ALTER TABLE `instructores_cursos`
  ADD CONSTRAINT `instructores_cursos_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `instructores_cursos_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `matriculas`
--
ALTER TABLE `matriculas`
  ADD CONSTRAINT `matriculas_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`),
  ADD CONSTRAINT `matriculas_ibfk_2` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_escuela` FOREIGN KEY (`escuela_id`) REFERENCES `escuelas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
