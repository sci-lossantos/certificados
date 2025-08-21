-- Asegurar que todas las escuelas tengan configuración de certificados
INSERT IGNORE INTO configuracion_certificados (escuela_id)
SELECT id FROM escuelas WHERE activa = 1;

-- Verificar datos
SELECT 
    e.id as escuela_id,
    e.nombre as escuela_nombre,
    CASE 
        WHEN cc.id IS NOT NULL THEN 'SÍ' 
        ELSE 'NO' 
    END as tiene_configuracion
FROM escuelas e
LEFT JOIN configuracion_certificados cc ON e.id = cc.escuela_id
WHERE e.activa = 1
ORDER BY e.nombre;
