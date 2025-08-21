-- Agregar columna es_rechazo a la tabla firmas_documentos si no existe
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'firmas_documentos'
    AND COLUMN_NAME = 'es_rechazo'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE firmas_documentos ADD COLUMN es_rechazo TINYINT(1) DEFAULT 0 AFTER observaciones',
    'SELECT "La columna es_rechazo ya existe" as mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Actualizar registros existentes para asegurar que no sean NULL
UPDATE firmas_documentos SET es_rechazo = 0 WHERE es_rechazo IS NULL;

-- Verificar la estructura final
DESCRIBE firmas_documentos;

-- Mostrar estadísticas
SELECT 
    COUNT(*) as total_firmas,
    SUM(CASE WHEN es_rechazo = 1 THEN 1 ELSE 0 END) as rechazos,
    SUM(CASE WHEN es_rechazo = 0 THEN 1 ELSE 0 END) as aprobaciones
FROM firmas_documentos;
