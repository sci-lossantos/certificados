-- Diagnóstico de estados actuales
SELECT 'Estados actuales en documentos:' as info;
SELECT estado, COUNT(*) as cantidad FROM documentos GROUP BY estado;

SELECT 'Tipos de firma actuales:' as info;
SELECT tipo_firma, COUNT(*) as cantidad FROM firmas_documentos GROUP BY tipo_firma;

SELECT 'Estructura actual de la tabla documentos:' as info;
DESCRIBE documentos;

SELECT 'Estructura actual de la tabla firmas_documentos:' as info;
DESCRIBE firmas_documentos;
