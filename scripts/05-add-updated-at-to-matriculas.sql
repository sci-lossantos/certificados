-- Agregar columna updated_at a la tabla matriculas si no existe
ALTER TABLE matriculas ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL;

-- Actualizar registros existentes para establecer updated_at igual a created_at
UPDATE matriculas SET updated_at = created_at WHERE updated_at IS NULL;
