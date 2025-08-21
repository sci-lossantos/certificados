-- Verificar estructura actual de la tabla escuelas
DESCRIBE escuelas;

-- Agregar columnas faltantes si no existen
ALTER TABLE escuelas 
ADD COLUMN IF NOT EXISTS director VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS coordinador VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS lema TEXT NULL,
ADD COLUMN IF NOT EXISTS mision TEXT NULL,
ADD COLUMN IF NOT EXISTS vision TEXT NULL,
ADD COLUMN IF NOT EXISTS logo VARCHAR(500) NULL;

-- Verificar la estructura actualizada
DESCRIBE escuelas;

-- Mostrar datos actuales
SELECT * FROM escuelas WHERE activa = 1;
