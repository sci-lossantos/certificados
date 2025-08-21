-- Agregar columna para escala de calificación en cursos
ALTER TABLE cursos ADD COLUMN IF NOT EXISTS escala_calificacion ENUM('0-5', '0-100') DEFAULT '0-5';

-- Actualizar cursos existentes que no tengan escala definida
UPDATE cursos SET escala_calificacion = '0-5' WHERE escala_calificacion IS NULL;

-- Agregar índice para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_cursos_escala ON cursos(escala_calificacion);

-- Comentario para documentar el cambio
-- Esta actualización permite que cada curso tenga su propia escala de calificación
-- '0-5': Escala tradicional de 0 a 5 puntos (nota mínima para aprobar: 3.0)
-- '0-100': Escala porcentual de 0 a 100 puntos (nota mínima para aprobar: 60)
