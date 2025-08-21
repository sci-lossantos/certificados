-- Verificar usuarios que necesitan asignación de escuela
SELECT 
    u.id as usuario_id,
    u.nombres,
    u.apellidos,
    u.email,
    r.nombre as rol,
    u.escuela_id,
    CASE 
        WHEN u.escuela_id IS NULL THEN 'SIN ASIGNAR'
        ELSE e.nombre
    END as escuela_asignada
FROM usuarios u 
JOIN roles r ON u.rol_id = r.id 
LEFT JOIN escuelas e ON u.escuela_id = e.id
WHERE r.nombre IN ('Escuela', 'Director de Escuela', 'Coordinador')
AND u.activo = 1
ORDER BY r.nombre, u.apellidos;

-- Mostrar escuelas disponibles para asignación
SELECT 
    id as escuela_id,
    nombre as nombre_escuela,
    activa
FROM escuelas 
WHERE activa = 1
ORDER BY nombre;

-- EJEMPLOS DE ASIGNACIÓN MANUAL:
-- UPDATE usuarios SET escuela_id = 1 WHERE id = [ID_DEL_USUARIO];
