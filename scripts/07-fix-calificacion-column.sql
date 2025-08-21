-- Modificar la columna calificacion para soportar escalas 0-5 y 0-100
-- Cambiar de DECIMAL(2,1) a DECIMAL(5,2) para permitir valores hasta 100.00

-- Verificar la estructura actual
DESCRIBE matriculas;

-- Modificar la columna calificacion
ALTER TABLE matriculas MODIFY COLUMN calificacion DECIMAL(5,2) NULL DEFAULT NULL;

-- Verificar que el cambio se aplicó correctamente
SHOW COLUMNS FROM matriculas LIKE 'calificacion';

-- Comentario para documentar el cambio
-- DECIMAL(5,2) permite:
-- - Valores de 0.00 a 999.99
-- - Suficiente para escalas 0-5 (ej: 3.50) y 0-100 (ej: 85.50)
-- - 2 decimales para mayor precisión
