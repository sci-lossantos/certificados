-- Arreglar tabla configuracion_certificados agregando columna 'activo' faltante
-- y asegurando que todas las columnas necesarias estén presentes

-- Verificar si la tabla existe
SELECT 'Verificando estructura de configuracion_certificados...' as mensaje;

-- Agregar columna 'activo' si no existe
ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS activo TINYINT(1) DEFAULT 1;

-- Agregar otras columnas que podrían faltar
ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Actualizar registros existentes para que tengan activo = 1
UPDATE configuracion_certificados SET activo = 1 WHERE activo IS NULL;

-- Verificar que la columna se agregó correctamente
SELECT 'Columna activo agregada correctamente' as mensaje;
DESCRIBE configuracion_certificados;

-- Mostrar configuraciones existentes
SELECT id, nombre, activo FROM configuracion_certificados;
