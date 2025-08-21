-- Verificar y arreglar la estructura de la tabla matriculas
-- Agregar columnas faltantes para el sistema de participantes

-- Verificar si existe la tabla matriculas
CREATE TABLE IF NOT EXISTS matriculas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participante_id INT NOT NULL,
    curso_id INT NOT NULL,
    fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio DATE NULL,
    fecha_finalizacion DATE NULL,
    calificacion DECIMAL(5,2) NULL,
    estado ENUM('inscrito', 'en_curso', 'completado', 'abandonado') DEFAULT 'inscrito',
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_matricula (participante_id, curso_id)
);

-- Agregar columnas faltantes si no existen
ALTER TABLE matriculas 
ADD COLUMN IF NOT EXISTS fecha_inicio DATE NULL AFTER fecha_inscripcion,
ADD COLUMN IF NOT EXISTS fecha_finalizacion DATE NULL AFTER fecha_inicio,
ADD COLUMN IF NOT EXISTS calificacion DECIMAL(5,2) NULL AFTER fecha_finalizacion,
ADD COLUMN IF NOT EXISTS estado ENUM('inscrito', 'en_curso', 'completado', 'abandonado') DEFAULT 'inscrito' AFTER calificacion,
ADD COLUMN IF NOT EXISTS observaciones TEXT NULL AFTER estado;

-- Verificar si existe la tabla documentos
CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participante_id INT NOT NULL,
    curso_id INT NOT NULL,
    tipo ENUM('certificado', 'diploma', 'constancia') DEFAULT 'certificado',
    estado ENUM('pendiente', 'en_proceso', 'completado', 'rechazado') DEFAULT 'pendiente',
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_completado TIMESTAMP NULL,
    generado_por INT NULL,
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (generado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Verificar si existe la tabla firmas_documentos
CREATE TABLE IF NOT EXISTS firmas_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    usuario_id INT NOT NULL,
    rol VARCHAR(50) NOT NULL,
    firmado BOOLEAN DEFAULT FALSE,
    fecha_firma TIMESTAMP NULL,
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Insertar datos de prueba si no existen
INSERT IGNORE INTO matriculas (participante_id, curso_id, fecha_inicio, fecha_finalizacion, calificacion, estado)
SELECT 
    p.id as participante_id,
    c.id as curso_id,
    DATE_SUB(CURDATE(), INTERVAL 30 DAY) as fecha_inicio,
    DATE_SUB(CURDATE(), INTERVAL 5 DAY) as fecha_finalizacion,
    85.5 as calificacion,
    'completado' as estado
FROM participantes p
CROSS JOIN cursos c
WHERE p.cedula = '12345678'
LIMIT 2;

-- Insertar documentos de prueba
INSERT IGNORE INTO documentos (participante_id, curso_id, tipo, estado, fecha_completado)
SELECT 
    m.participante_id,
    m.curso_id,
    'certificado' as tipo,
    'completado' as estado,
    NOW() as fecha_completado
FROM matriculas m
WHERE m.calificacion >= 70;

SELECT 'Estructura de base de datos actualizada correctamente' as mensaje;
