-- Script simplificado para corregir la columna estado sin usar INFORMATION_SCHEMA

-- Verificar la estructura actual de la tabla documentos
DESCRIBE documentos;

-- Ver los estados actuales y sus longitudes
SELECT DISTINCT estado, LENGTH(estado) as longitud FROM documentos;

-- Modificar la columna estado para que tenga suficiente espacio
-- Primero intentamos con VARCHAR(50)
ALTER TABLE documentos MODIFY COLUMN estado VARCHAR(50) NOT NULL DEFAULT 'generado';

-- Verificar que el cambio se aplicó
DESCRIBE documentos;

-- Mostrar algunos registros para verificar
SELECT id, tipo, estado, LENGTH(estado) as longitud_estado 
FROM documentos 
LIMIT 5;
