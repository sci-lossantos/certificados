-- Corregir el flujo de rechazo de documentos
-- Primero, verificar la estructura actual
SELECT 
    d.id,
    d.codigo_unico,
    d.tipo,
    d.estado,
    fd.accion,
    fd.es_rechazo,
    u.nombres,
    r.nombre as rol
FROM documentos d
LEFT JOIN firmas_documentos fd ON d.id = fd.documento_id
LEFT JOIN usuarios u ON fd.usuario_id = u.id
LEFT JOIN roles r ON u.rol_id = r.id
WHERE d.tipo = 'acta'
ORDER BY d.id, fd.fecha_firma;

-- Limpiar registros de rechazo mal procesados
-- (Solo ejecutar si hay documentos con problemas)
-- DELETE FROM firmas_documentos WHERE es_rechazo = 1;

-- Verificar que la columna es_rechazo existe y tiene el tipo correcto
DESCRIBE firmas_documentos;

-- Mostrar el estado actual de todos los documentos
SELECT 
    d.id,
    d.codigo_unico,
    d.tipo,
    d.estado,
    COUNT(fd.id) as total_firmas,
    SUM(CASE WHEN fd.es_rechazo = 1 THEN 1 ELSE 0 END) as rechazos
FROM documentos d
LEFT JOIN firmas_documentos fd ON d.id = fd.documento_id
GROUP BY d.id, d.codigo_unico, d.tipo, d.estado;
