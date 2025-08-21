-- Este script muestra la estructura actual de la tabla cursos
DESCRIBE cursos;

-- Consulta para ver algunos datos de ejemplo de la tabla cursos
-- Usando solo columnas básicas que deberían existir
SELECT id, nombre, fecha_inicio, fecha_fin, activo 
FROM cursos 
LIMIT 5;
