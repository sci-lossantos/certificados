-- Reparar completamente la tabla firmas_documentos
-- Agregar todas las columnas necesarias para el sistema de documentos

-- Verificar si la tabla existe
CREATE TABLE IF NOT EXISTS firmas_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    usuario_id INT DEFAULT 1,
    rol VARCHAR(50) DEFAULT 'coordinador',
    firmado BOOLEAN DEFAULT TRUE,
    fecha_firma DATETIME DEFAULT CURRENT_TIMESTAMP,
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Agregar columna 'accion' si no existe
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'firmas_documentos' 
    AND COLUMN_NAME = 'accion'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE firmas_documentos ADD COLUMN accion ENUM(''firma'', ''revision'') DEFAULT ''firma''', 
    'SELECT "Columna accion ya existe"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar columna 'es_rechazo' si no existe
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'firmas_documentos' 
    AND COLUMN_NAME = 'es_rechazo'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE firmas_documentos ADD COLUMN es_rechazo TINYINT(1) DEFAULT 0', 
    'SELECT "Columna es_rechazo ya existe"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar columna 'motivo_rechazo' si no existe
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'firmas_documentos' 
    AND COLUMN_NAME = 'motivo_rechazo'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE firmas_documentos ADD COLUMN motivo_rechazo TEXT NULL', 
    'SELECT "Columna motivo_rechazo ya existe"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar columna 'fecha_rechazo' si no existe
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'firmas_documentos' 
    AND COLUMN_NAME = 'fecha_rechazo'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE firmas_documentos ADD COLUMN fecha_rechazo DATETIME NULL', 
    'SELECT "Columna fecha_rechazo ya existe"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Actualizar registros existentes para que tengan valores por defecto
UPDATE firmas_documentos 
SET 
    accion = 'firma',
    es_rechazo = 0
WHERE accion IS NULL OR es_rechazo IS NULL;

-- Crear índices para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_documento_id ON firmas_documentos(documento_id);
CREATE INDEX IF NOT EXISTS idx_usuario_id ON firmas_documentos(usuario_id);
CREATE INDEX IF NOT EXISTS idx_es_rechazo ON firmas_documentos(es_rechazo);
CREATE INDEX IF NOT EXISTS idx_firmado ON firmas_documentos(firmado);

-- Insertar algunas firmas de prueba si no existen
INSERT IGNORE INTO firmas_documentos (documento_id, usuario_id, rol, accion, firmado, fecha_firma, es_rechazo)
SELECT 
    d.id as documento_id,
    1 as usuario_id,
    'coordinador' as rol,
    'firma' as accion,
    TRUE as firmado,
    NOW() as fecha_firma,
    0 as es_rechazo
FROM documentos d
WHERE d.estado = 'completado'
AND NOT EXISTS (
    SELECT 1 FROM firmas_documentos fd 
    WHERE fd.documento_id = d.id
);

SELECT 'Tabla firmas_documentos reparada exitosamente' as resultado;
