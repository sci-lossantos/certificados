-- Verificar todas las columnas que podrían tener problemas de longitud

-- 1. Verificar tabla documentos
SELECT 'Tabla documentos - columna estado:' as info;
SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'documentos' AND COLUMN_NAME IN ('estado', 'tipo');

-- 2. Verificar tabla firmas_documentos
SELECT 'Tabla firmas_documentos:' as info;
SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'firmas_documentos' AND COLUMN_NAME IN ('tipo_firma', 'accion');

-- 3. Ajustar todas las columnas que podrían tener problemas
-- Documentos
ALTER TABLE documentos MODIFY COLUMN estado VARCHAR(50) NOT NULL DEFAULT 'generado';
ALTER TABLE documentos MODIFY COLUMN tipo VARCHAR(20) NOT NULL;

-- Firmas documentos
ALTER TABLE firmas_documentos MODIFY COLUMN tipo_firma VARCHAR(30) NOT NULL;
ALTER TABLE firmas_documentos MODIFY COLUMN accion VARCHAR(20) NOT NULL DEFAULT 'firma';

-- 4. Verificar los cambios
SELECT 'Verificación final:' as info;
SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME IN ('documentos', 'firmas_documentos') 
AND COLUMN_NAME IN ('estado', 'tipo', 'tipo_firma', 'accion')
ORDER BY TABLE_NAME, COLUMN_NAME;
