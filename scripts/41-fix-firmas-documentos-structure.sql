-- Arreglar la estructura de la tabla firmas_documentos
-- Agregar la columna 'firmado' que falta

-- Verificar y agregar la columna 'firmado' si no existe
ALTER TABLE firmas_documentos 
ADD COLUMN IF NOT EXISTS firmado BOOLEAN DEFAULT FALSE AFTER rol;

-- Actualizar registros existentes para marcar como firmados si tienen fecha_firma
UPDATE firmas_documentos 
SET firmado = TRUE 
WHERE fecha_firma IS NOT NULL AND firmado = FALSE;

-- Verificar la estructura final
DESCRIBE firmas_documentos;

-- Mostrar datos actuales
SELECT 'Estructura de firmas_documentos actualizada' as mensaje;
SELECT COUNT(*) as total_firmas FROM firmas_documentos;
SELECT COUNT(*) as firmas_completadas FROM firmas_documentos WHERE firmado = TRUE;
