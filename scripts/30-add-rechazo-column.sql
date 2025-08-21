-- Agregar columna es_rechazo a la tabla firmas_documentos
ALTER TABLE firmas_documentos 
ADD COLUMN es_rechazo TINYINT(1) DEFAULT 0 AFTER observaciones;

-- Actualizar registros existentes
UPDATE firmas_documentos SET es_rechazo = 0 WHERE es_rechazo IS NULL;
