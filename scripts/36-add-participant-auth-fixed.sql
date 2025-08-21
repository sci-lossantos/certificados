-- Verificar y agregar campos de autenticación a la tabla participantes
-- Solo agregar columnas que no existan

-- Agregar password si no existe
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'participantes' 
AND column_name = 'password';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE participantes ADD COLUMN password VARCHAR(255) NULL AFTER email', 
    'SELECT "Column password already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar ultimo_acceso si no existe
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'participantes' 
AND column_name = 'ultimo_acceso';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE participantes ADD COLUMN ultimo_acceso TIMESTAMP NULL', 
    'SELECT "Column ultimo_acceso already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar token_reset si no existe
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'participantes' 
AND column_name = 'token_reset';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE participantes ADD COLUMN token_reset VARCHAR(255) NULL', 
    'SELECT "Column token_reset already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar token_reset_expira si no existe
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'participantes' 
AND column_name = 'token_reset_expira';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE participantes ADD COLUMN token_reset_expira TIMESTAMP NULL', 
    'SELECT "Column token_reset_expira already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear índices si no existen
CREATE INDEX IF NOT EXISTS idx_participantes_email ON participantes(email);
CREATE INDEX IF NOT EXISTS idx_participantes_cedula ON participantes(cedula);

-- Actualizar participantes existentes con contraseñas por defecto (cédula)
-- Solo si la columna password existe y está vacía
UPDATE participantes 
SET password = MD5(cedula) 
WHERE password IS NULL OR password = '';

-- Verificar si necesitamos hacer el campo password NOT NULL
SET @col_nullable = '';
SELECT IS_NULLABLE INTO @col_nullable
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'participantes' 
AND column_name = 'password';

-- Si la columna existe y es nullable, cambiarla a NOT NULL
SET @sql = IF(@col_nullable = 'YES', 
    'ALTER TABLE participantes MODIFY COLUMN password VARCHAR(255) NOT NULL', 
    'SELECT "Column password is already NOT NULL or does not exist" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Mostrar estructura final de la tabla
DESCRIBE participantes;
