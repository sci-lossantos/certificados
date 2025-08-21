-- Verificar la estructura actual de la columna estado
DESCRIBE documentos;

-- Ver los estados actuales para entender el problema
SELECT DISTINCT estado, LENGTH(estado) as longitud FROM documentos;

-- Verificar el tipo de dato actual de la columna estado
SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'documentos' AND COLUMN_NAME = 'estado';

-- Modificar la columna estado para que tenga suficiente espacio
ALTER TABLE documentos MODIFY COLUMN estado VARCHAR(50) NOT NULL DEFAULT 'generado';

-- Verificar que el cambio se aplicó correctamente
SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'documentos' AND COLUMN_NAME = 'estado';

-- Mostrar todos los estados posibles que el sistema puede generar
SELECT 'Estados posibles en el sistema:' as info;
SELECT 'generado' as estado, LENGTH('generado') as longitud
UNION ALL SELECT 'firmado_coordinador', LENGTH('firmado_coordinador')
UNION ALL SELECT 'revisado_coordinador', LENGTH('revisado_coordinador')
UNION ALL SELECT 'firmado_director_escuela', LENGTH('firmado_director_escuela')
UNION ALL SELECT 'revisado_director_escuela', LENGTH('revisado_director_escuela')
UNION ALL SELECT 'revisado_educacion_dnbc', LENGTH('revisado_educacion_dnbc')
UNION ALL SELECT 'firmado_director_nacional', LENGTH('firmado_director_nacional')
UNION ALL SELECT 'completado', LENGTH('completado');
