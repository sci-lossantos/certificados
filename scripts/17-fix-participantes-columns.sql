-- Verificar que la tabla participantes tenga todas las columnas necesarias
ALTER TABLE participantes 
ADD COLUMN IF NOT EXISTS entidad VARCHAR(300),
ADD COLUMN IF NOT EXISTS celular VARCHAR(20);

-- Actualizar datos existentes
UPDATE participantes 
SET entidad = COALESCE(entidad, institucion),
    celular = COALESCE(celular, telefono)
WHERE entidad IS NULL OR celular IS NULL;
