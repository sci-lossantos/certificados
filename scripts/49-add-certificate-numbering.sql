-- Agregar campos para numeración de certificados y actas

-- Agregar campos a la tabla configuracion_certificados
ALTER TABLE configuracion_certificados 
ADD COLUMN numero_registro VARCHAR(50) DEFAULT 'REG-001' COMMENT 'Número de registro base para certificados',
ADD COLUMN mostrar_numero_consecutivo BOOLEAN DEFAULT TRUE COMMENT 'Mostrar número consecutivo del certificado',
ADD COLUMN texto_numero_consecutivo VARCHAR(255) DEFAULT 'Certificado No. {consecutivo}' COMMENT 'Texto para mostrar el número consecutivo',
ADD COLUMN mostrar_numero_acta BOOLEAN DEFAULT TRUE COMMENT 'Mostrar número de acta',
ADD COLUMN texto_numero_acta VARCHAR(255) DEFAULT 'Acta No. {numero_acta} del {fecha_acta}' COMMENT 'Texto para mostrar el número de acta',
ADD COLUMN formato_consecutivo VARCHAR(100) DEFAULT '{registro}-{orden}' COMMENT 'Formato del número consecutivo';

-- Agregar campos a la tabla documentos para tracking de numeración
ALTER TABLE documentos 
ADD COLUMN numero_consecutivo VARCHAR(100) NULL COMMENT 'Número consecutivo del certificado',
ADD COLUMN numero_acta INT NULL COMMENT 'Número de acta asignado',
ADD COLUMN fecha_acta DATE NULL COMMENT 'Fecha del acta',
ADD COLUMN orden_alfabetico INT NULL COMMENT 'Orden alfabético del participante en el curso';

-- Crear tabla para control de actas
CREATE TABLE IF NOT EXISTS actas_certificacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acta INT NOT NULL,
    curso_id INT NOT NULL,
    escuela_id INT NOT NULL,
    fecha_acta DATE NOT NULL,
    fecha_terminacion_curso DATE NOT NULL,
    total_participantes INT DEFAULT 0,
    participantes_aprobados INT DEFAULT 0,
    estado ENUM('generada', 'firmada', 'archivada') DEFAULT 'generada',
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (escuela_id) REFERENCES escuelas(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_acta_escuela (numero_acta, escuela_id),
    INDEX idx_fecha_terminacion (fecha_terminacion_curso),
    INDEX idx_escuela_fecha (escuela_id, fecha_terminacion_curso)
);

-- Función para obtener el siguiente número de acta por escuela
DELIMITER //
CREATE FUNCTION IF NOT EXISTS GetNextActaNumber(p_escuela_id INT) 
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE next_number INT DEFAULT 1;
    
    SELECT COALESCE(MAX(numero_acta), 0) + 1 
    INTO next_number 
    FROM actas_certificacion 
    WHERE escuela_id = p_escuela_id;
    
    RETURN next_number;
END//
DELIMITER ;

-- Actualizar configuraciones existentes con los nuevos campos
UPDATE configuracion_certificados 
SET numero_registro = 'REG-001',
    mostrar_numero_consecutivo = TRUE,
    texto_numero_consecutivo = 'Certificado No. {consecutivo}',
    mostrar_numero_acta = TRUE,
    texto_numero_acta = 'Acta No. {numero_acta} del {fecha_acta}',
    formato_consecutivo = '{registro}-{orden}'
WHERE numero_registro IS NULL;
