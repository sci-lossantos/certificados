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
    director,
    coordinador,
    activa
FROM escuelas 
WHERE activa = 1
ORDER BY nombre;

-- Verificar si hay directores ya asignados en la tabla escuelas
SELECT 
    e.id as escuela_id,
    e.nombre as escuela_nombre,
    e.director_id,
    u.nombres as director_nombres,
    u.apellidos as director_apellidos
FROM escuelas e
LEFT JOIN usuarios u ON e.director_id = u.id
WHERE e.activa = 1;

-- EJEMPLOS DE ASIGNACIÓN MANUAL (descomenta y modifica según tus datos):
-- 
-- Para asignar un usuario específico a una escuela:
-- UPDATE usuarios SET escuela_id = 1 WHERE id = [ID_DEL_USUARIO];
-- 
-- Para asignar todos los usuarios con rol 'Escuela' a la escuela con ID 1:
-- UPDATE usuarios u 
-- JOIN roles r ON u.rol_id = r.id 
-- SET u.escuela_id = 1 
-- WHERE r.nombre = 'Escuela' AND u.escuela_id IS NULL;
--
-- Para asignar directores basándose en la tabla escuelas:
-- UPDATE usuarios u
-- JOIN escuelas e ON e.director_id = u.id
-- SET u.escuela_id = e.id
-- WHERE u.escuela_id IS NULL;

-- Verificación final después de las asignaciones
-- SELECT 
--     u.id,
--     CONCAT(u.nombres, ' ', u.apellidos) as nombre_completo,
--     r.nombre as rol,
--     e.nombre as escuela
-- FROM usuarios u
-- JOIN roles r ON u.rol_id = r.id
-- LEFT JOIN escuelas e ON u.escuela_id = e.id
-- WHERE r.nombre IN ('Escuela', 'Director de Escuela', 'Coordinador')
-- AND u.activo = 1
-- ORDER BY e.nombre, r.nombre;
