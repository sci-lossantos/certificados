-- Agregar columna tipo_firma a la tabla usuarios si no existe

-- Verificar si la columna tipo_firma existe
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'usuarios' 
    AND COLUMN_NAME = 'tipo_firma'
);

-- Agregar la columna si no existe
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE usuarios ADD COLUMN tipo_firma ENUM(''texto'', ''imagen'') DEFAULT ''texto''', 
    'SELECT "Columna tipo_firma ya existe"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar si la columna firma_digital existe
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'usuarios' 
    AND COLUMN_NAME = 'firma_digital'
);

-- Agregar la columna firma_digital si no existe
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE usuarios ADD COLUMN firma_digital TEXT NULL', 
    'SELECT "Columna firma_digital ya existe"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Actualizar usuarios existentes con valores por defecto
UPDATE usuarios 
SET 
    tipo_firma = COALESCE(tipo_firma, 'texto'),
    firma_digital = COALESCE(firma_digital, CONCAT(nombres, ' ', apellidos))
WHERE tipo_firma IS NULL OR firma_digital IS NULL;

SELECT 'Columnas tipo_firma y firma_digital agregadas exitosamente' as resultado;
