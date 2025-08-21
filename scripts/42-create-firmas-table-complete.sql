-- Crear la tabla firmas_documentos con la estructura completa
-- Si existe, la eliminamos y la recreamos

DROP TABLE IF EXISTS firmas_documentos;

CREATE TABLE firmas_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    usuario_id INT NOT NULL,
    rol VARCHAR(50) NOT NULL DEFAULT 'coordinador',
    firmado BOOLEAN DEFAULT FALSE,
    fecha_firma DATETIME NULL,
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    INDEX idx_documento_id (documento_id),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_firmado (firmado)
);

-- Insertar firmas de prueba para documentos existentes
INSERT INTO firmas_documentos (documento_id, usuario_id, rol, firmado, fecha_firma)
SELECT 
    d.id as documento_id,
    1 as usuario_id,
    'coordinador' as rol,
    TRUE as firmado,
    NOW() as fecha_firma
FROM documentos d
WHERE d.tipo = 'certificado'
ON DUPLICATE KEY UPDATE firmado = TRUE;

-- Insertar firmas adicionales para el flujo de aprobación
INSERT INTO firmas_documentos (documento_id, usuario_id, rol, firmado, fecha_firma)
SELECT 
    d.id as documento_id,
    2 as usuario_id,
    'director' as rol,
    TRUE as firmado,
    NOW() as fecha_firma
FROM documentos d
WHERE d.tipo = 'certificado'
ON DUPLICATE KEY UPDATE firmado = TRUE;

-- Mostrar estadísticas
SELECT 'Tabla firmas_documentos creada exitosamente' as mensaje;
SELECT COUNT(*) as total_firmas FROM firmas_documentos;
SELECT COUNT(*) as firmas_completadas FROM firmas_documentos WHERE firmado = TRUE;
SELECT COUNT(*) as firmas_pendientes FROM firmas_documentos WHERE firmado = FALSE;

-- Mostrar estructura de la tabla
DESCRIBE firmas_documentos;
