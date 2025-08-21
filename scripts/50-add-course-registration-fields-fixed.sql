-- Agregar campos adicionales necesarios para el nuevo formato de certificado

-- Verificar y agregar columnas a la tabla cursos solo si no existen
SET @exist_numero_registro_curso = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cursos' AND COLUMN_NAME = 'numero_registro_curso');

SET @exist_lugar_realizacion = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cursos' AND COLUMN_NAME = 'lugar_realizacion');

-- Agregar columnas solo si no existen
SET @sql_add_numero_registro = IF(@exist_numero_registro_curso = 0, 
    'ALTER TABLE cursos ADD COLUMN numero_registro_curso VARCHAR(100) NULL COMMENT "Número de registro del curso ante la DNBC"', 
    'SELECT "Columna numero_registro_curso ya existe" AS mensaje');
PREPARE stmt FROM @sql_add_numero_registro;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql_add_lugar_realizacion = IF(@exist_lugar_realizacion = 0, 
    'ALTER TABLE cursos ADD COLUMN lugar_realizacion VARCHAR(255) NULL COMMENT "Lugar donde se realiza el curso"', 
    'SELECT "Columna lugar_realizacion ya existe" AS mensaje');
PREPARE stmt FROM @sql_add_lugar_realizacion;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar campos a configuracion_certificados para el nuevo formato
-- Primero verificamos si las columnas existen
SET @exist_texto_certifica = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configuracion_certificados' AND COLUMN_NAME = 'texto_certifica_que');

-- Si no existen, las agregamos
SET @sql_add_config_fields = IF(@exist_texto_certifica = 0, 
'ALTER TABLE configuracion_certificados 
ADD COLUMN texto_certifica_que VARCHAR(255) DEFAULT "Certifica que:" COMMENT "Texto Certifica que",
ADD COLUMN texto_identificado VARCHAR(255) DEFAULT "Identificado con C.C. No." COMMENT "Texto identificación",
ADD COLUMN texto_asistio_aprobo VARCHAR(255) DEFAULT "Asistió y aprobó los requisitos del Curso:" COMMENT "Texto asistió y aprobó",
ADD COLUMN texto_curso_autorizado VARCHAR(500) DEFAULT "Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia" COMMENT "Texto curso autorizado",
ADD COLUMN texto_bajo_acta VARCHAR(255) DEFAULT "Bajo acta número {numero_acta} del {fecha_acta} del {nombre_cuerpo_bomberos}" COMMENT "Texto bajo acta",
ADD COLUMN texto_duracion VARCHAR(255) DEFAULT "Con una duración de: {horas} horas académicas" COMMENT "Texto duración",
ADD COLUMN texto_realizado_en VARCHAR(255) DEFAULT "Realizado en {lugar} del {fecha_inicio} al {fecha_fin}" COMMENT "Texto realizado en",
ADD COLUMN texto_constancia VARCHAR(255) DEFAULT "En constancia de lo anterior, se firma a los {fecha_firma}" COMMENT "Texto constancia",
ADD COLUMN fecha_firma_certificados DATE NULL COMMENT "Fecha en que se firman los certificados"',
'SELECT "Columnas de configuración ya existen" AS mensaje');

PREPARE stmt FROM @sql_add_config_fields;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Actualizar configuraciones existentes solo si las columnas existen
SET @exist_all_config_columns = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configuracion_certificados' AND COLUMN_NAME = 'texto_certifica_que');

SET @sql_update_config = IF(@exist_all_config_columns > 0, 
'UPDATE configuracion_certificados 
SET texto_certifica_que = "Certifica que:",
    texto_identificado = "Identificado con C.C. No.",
    texto_asistio_aprobo = "Asistió y aprobó los requisitos del Curso:",
    texto_curso_autorizado = "Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia",
    texto_bajo_acta = "Bajo acta número {numero_acta} del {fecha_acta} del {nombre_cuerpo_bomberos}",
    texto_duracion = "Con una duración de: {horas} horas académicas",
    texto_realizado_en = "Realizado en {lugar} del {fecha_inicio} al {fecha_fin}",
    texto_constancia = "En constancia de lo anterior, se firma a los {fecha_firma}"
WHERE texto_certifica_que IS NULL',
'SELECT "No se actualizaron configuraciones" AS mensaje');

PREPARE stmt FROM @sql_update_config;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Actualizar algunos cursos de ejemplo con datos si las columnas existen
SET @exist_numero_registro_curso_for_update = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cursos' AND COLUMN_NAME = 'numero_registro_curso');

SET @exist_lugar_realizacion_for_update = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cursos' AND COLUMN_NAME = 'lugar_realizacion');

-- Solo actualizamos si ambas columnas existen
SET @can_update_cursos = @exist_numero_registro_curso_for_update > 0 AND @exist_lugar_realizacion_for_update > 0;

SET @sql_update_cursos = IF(@can_update_cursos, 
'UPDATE cursos 
SET numero_registro_curso = CONCAT("REG-CURSO-", id, "-2024"),
    lugar_realizacion = "Instalaciones de la Escuela"
WHERE numero_registro_curso IS NULL',
'SELECT "No se actualizaron cursos" AS mensaje');

PREPARE stmt FROM @sql_update_cursos;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear tabla de actas si no existe
CREATE TABLE IF NOT EXISTS actas_certificacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acta VARCHAR(50) NOT NULL COMMENT 'Número consecutivo del acta',
    fecha_acta DATE NOT NULL COMMENT 'Fecha del acta',
    escuela_id INT NOT NULL COMMENT 'Escuela que emite el acta',
    curso_id INT NOT NULL COMMENT 'Curso al que pertenece el acta',
    descripcion TEXT NULL COMMENT 'Descripción o notas adicionales',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (escuela_id) REFERENCES escuelas(id),
    FOREIGN KEY (curso_id) REFERENCES cursos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de numeración de certificados si no existe
CREATE TABLE IF NOT EXISTS numeracion_certificados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL COMMENT 'Curso al que pertenece el certificado',
    participante_id INT NOT NULL COMMENT 'Participante al que se emite el certificado',
    numero_consecutivo VARCHAR(100) NOT NULL COMMENT 'Número consecutivo del certificado',
    acta_id INT NULL COMMENT 'Acta a la que pertenece el certificado',
    fecha_emision DATE NOT NULL COMMENT 'Fecha de emisión del certificado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id),
    FOREIGN KEY (participante_id) REFERENCES participantes(id),
    FOREIGN KEY (acta_id) REFERENCES actas_certificacion(id),
    UNIQUE KEY unique_certificado (curso_id, participante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
