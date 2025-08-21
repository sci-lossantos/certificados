-- Script simplificado para ajustar todas las columnas problemáticas

-- Ajustar columnas de la tabla documentos
ALTER TABLE documentos MODIFY COLUMN estado VARCHAR(50) NOT NULL DEFAULT 'generado';
ALTER TABLE documentos MODIFY COLUMN tipo VARCHAR(30) NOT NULL;

-- Verificar tabla documentos
DESCRIBE documentos;

-- Ajustar columnas de la tabla firmas_documentos (si existe)
-- Primero verificamos si la tabla existe
SELECT COUNT(*) as tabla_existe FROM firmas_documentos LIMIT 1;

-- Si existe, ajustamos las columnas
ALTER TABLE firmas_documentos MODIFY COLUMN tipo_firma VARCHAR(30) NOT NULL;
ALTER TABLE firmas_documentos MODIFY COLUMN accion VARCHAR(20) NOT NULL DEFAULT 'firma';

-- Verificar tabla firmas_documentos
DESCRIBE firmas_documentos;

-- Mostrar algunos registros de prueba
SELECT 'Verificación final - documentos:' as info;
SELECT id, tipo, estado, LENGTH(estado) as longitud_estado 
FROM documentos 
ORDER BY id DESC 
LIMIT 3;
