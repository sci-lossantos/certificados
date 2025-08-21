-- Crear tabla de configuración de certificados
CREATE TABLE IF NOT EXISTS `configuracion_certificados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `escuela_id` int(11) DEFAULT NULL,
  
  -- Textos del certificado
  `texto_certifica_que` varchar(500) DEFAULT 'Certifica que:',
  `texto_identificado_con` varchar(500) DEFAULT 'Identificado con C.C. No.',
  `texto_asistio_aprobo` varchar(500) DEFAULT 'Asistió y aprobó los requisitos del Curso:',
  `texto_curso_autorizado` text DEFAULT 'Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia',
  `texto_bajo_acta` text DEFAULT 'Bajo acta número {numero_acta} del {fecha_acta} del {nombre_cuerpo_bomberos}',
  `texto_duracion` varchar(500) DEFAULT 'Con una duración de: {horas} HORAS',
  `texto_realizado_en` text DEFAULT 'Realizado en ({lugar_realizacion}) del ({fecha_inicio_dia}) de ({fecha_inicio_mes}) al ({fecha_fin_dia}) de ({fecha_fin_mes}) de {año}',
  `texto_constancia` text DEFAULT 'En constancia de lo anterior, se firma a los {fecha_firma_dia} dias del mes de {fecha_firma_mes} de {fecha_firma_año}',
  
  -- Configuración de numeración
  `mostrar_consecutivo` tinyint(1) DEFAULT 1,
  `formato_consecutivo` varchar(200) DEFAULT '{año}-{registro_curso}-{orden_alfabetico}',
  `numero_registro_base` varchar(100) DEFAULT 'DNBC-2025',
  
  -- Configuración de actas
  `mostrar_numero_acta` tinyint(1) DEFAULT 1,
  `formato_numero_acta` varchar(200) DEFAULT '{numero_acta}',
  
  -- Configuración de firmas
  `mostrar_firma_director_nacional` tinyint(1) DEFAULT 1,
  `mostrar_firma_director_escuela` tinyint(1) DEFAULT 1,
  `mostrar_firma_coordinador` tinyint(1) DEFAULT 1,
  
  -- Configuración de contenido programático
  `mostrar_contenido_programatico` tinyint(1) DEFAULT 1,
  `columnas_contenido` int(11) DEFAULT 2,
  
  -- Configuración de encabezados
  `mostrar_logos_institucionales` tinyint(1) DEFAULT 1,
  `texto_encabezado_izquierdo` text DEFAULT 'Cuerpo de Bomberos Los Santos Santander',
  `texto_encabezado_centro` text DEFAULT 'Escuela Internacional de Bomberos del Oriente Colombiano\nESIBOC',
  `texto_encabezado_derecho` text DEFAULT '',
  
  -- Configuración de autoridades
  `director_nacional_nombre` varchar(200) DEFAULT 'CT. EN JEFE LINA MARÍA MARÍN RODRÍGUEZ',
  `director_nacional_cargo` varchar(200) DEFAULT 'Directora Nacional DNBC',
  `comandante_nombre` varchar(200) DEFAULT 'CT. MANUEL ENRIQUE SALAZAR HERNANDEZ',
  `comandante_cargo` varchar(200) DEFAULT 'Comandante Cuerpo de Bomberos Los Santos Sant.',
  
  -- Configuración de diseño
  `orientacion` enum('vertical','horizontal') DEFAULT 'vertical',
  `tamaño_papel` varchar(20) DEFAULT 'A4',
  `margenes_superior` int(11) DEFAULT 20,
  `margenes_inferior` int(11) DEFAULT 20,
  `margenes_izquierdo` int(11) DEFAULT 15,
  `margenes_derecho` int(11) DEFAULT 15,
  
  -- Control de versiones
  `activo` tinyint(1) DEFAULT 1,
  `es_plantilla_defecto` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  KEY `fk_config_cert_escuela` (`escuela_id`),
  CONSTRAINT `fk_config_cert_escuela` FOREIGN KEY (`escuela_id`) REFERENCES `escuelas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración por defecto para ESIBOC
INSERT INTO `configuracion_certificados` (
  `nombre`, 
  `descripcion`, 
  `escuela_id`,
  `texto_certifica_que`,
  `texto_identificado_con`, 
  `texto_asistio_aprobo`,
  `texto_curso_autorizado`,
  `texto_bajo_acta`,
  `texto_duracion`,
  `texto_realizado_en`,
  `texto_constancia`,
  `formato_consecutivo`,
  `numero_registro_base`,
  `texto_encabezado_izquierdo`,
  `texto_encabezado_centro`,
  `director_nacional_nombre`,
  `director_nacional_cargo`,
  `comandante_nombre`,
  `comandante_cargo`,
  `es_plantilla_defecto`
) VALUES (
  'Configuración ESIBOC Estándar',
  'Configuración estándar para certificados ESIBOC según formato oficial',
  2, -- ID de ESIBOC
  'Certifica que:',
  'Identificado con C.C. No.',
  'Asistió y aprobó los requisitos del Curso:',
  'Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia',
  'Bajo acta número {numero_acta} del {fecha_acta} del Cuerpo de Bomberos Voluntarios Los Santos',
  'Con una duración de: {horas} HORAS',
  'Realizado en ({lugar_realizacion}) del ({fecha_inicio_dia}) de ({fecha_inicio_mes}) al ({fecha_fin_dia}) de ({fecha_fin_mes}) de {año}',
  'En constancia de lo anterior, se firma a los {fecha_firma_dia} dias del mes de {fecha_firma_mes} de {fecha_firma_año}',
  '{año}-{registro_curso}-{orden_alfabetico}',
  '184-2025',
  'Cuerpo de Bomberos Los Santos Santander',
  'Escuela Internacional de Bomberos del Oriente Colombiano\nESIBOC',
  'CT. EN JEFE LINA MARÍA MARÍN RODRÍGUEZ',
  'Directora Nacional DNBC',
  'CT. MANUEL ENRIQUE SALAZAR HERNANDEZ',
  'Comandante Cuerpo de Bomberos Los Santos Sant.',
  1
);

-- Configuración genérica para otras escuelas
INSERT INTO `configuracion_certificados` (
  `nombre`, 
  `descripcion`, 
  `escuela_id`,
  `es_plantilla_defecto`
) VALUES (
  'Configuración Genérica DNBC',
  'Configuración genérica para escuelas de bomberos afiliadas a DNBC',
  NULL, -- Aplica a todas las escuelas
  0
);
