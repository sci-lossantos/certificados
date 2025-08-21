-- Verificar y agregar columnas necesarias a la tabla matriculas
ALTER TABLE matriculas 
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Actualizar registros existentes para establecer created_at y updated_at
UPDATE matriculas 
SET created_at = fecha_matricula, 
    updated_at = fecha_matricula 
WHERE created_at IS NULL OR updated_at IS NULL;
