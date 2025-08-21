-- Script para asignar usuarios con rol "Escuela" a sus escuelas correspondientes
-- Autor: Sistema ESIBOC
-- Fecha: 2025-01-08

-- 1. Mostrar todos los usuarios con rol "Escuela" que no tienen escuela asignada
SELECT 
    u.id as usuario_id,
    CONCAT(u.nombres, ' ', u.apellidos) as nombre_completo,
    u.email,
    r.nombre as rol,
    u.escuela_id,
    CASE 
        WHEN u.escuela_id IS NULL THEN 'SIN ASIGNAR'
        ELSE 'ASIGNADO'
    END as estado_asignacion
FROM usuarios u 
JOIN roles r ON u.rol_id = r.id 
WHERE r.nombre = 'Escuela' 
AND u.activo = 1
ORDER BY u.nombres, u.apellidos;

-- 2. Mostrar todas las escuelas disponibles
SELECT 
    id as escuela_id,
    nombre as nombre_escuela,
    direccion,
    telefono,
    email,
    activa
FROM escuelas 
WHERE activa = 1
ORDER BY nombre;

-- 3. Verificar usuarios ya asignados a escuelas
SELECT 
    u.id as usuario_id,
    CONCAT(u.nombres, ' ', u.apellidos) as usuario,
    u.email,
    r.nombre as rol,
    e.id as escuela_id,
    e.nombre as escuela_nombre
FROM usuarios u 
JOIN roles r ON u.rol_id = r.id 
LEFT JOIN escuelas e ON u.escuela_id = e.id
WHERE r.nombre IN ('Escuela', 'Director de Escuela', 'Coordinador')
AND u.activo = 1
ORDER BY e.nombre, u.nombres;

-- 4. Ejemplos de consultas para asignar usuarios a escuelas
-- DESCOMENTA Y MODIFICA SEGÚN CORRESPONDA:

-- Ejemplo: Asignar usuario ID 1 a escuela ID 1
-- UPDATE usuarios SET escuela_id = 1 WHERE id = 1;

-- Ejemplo: Asignar usuario ID 2 a escuela ID 2  
-- UPDATE usuarios SET escuela_id = 2 WHERE id = 2;

-- Ejemplo: Asignar múltiples usuarios de una vez (modifica los IDs según corresponda)
-- UPDATE usuarios SET escuela_id = 1 WHERE id IN (1, 3, 5);

-- 5. Verificar asignaciones después de ejecutar los UPDATE
-- SELECT 
--     u.id,
--     CONCAT(u.nombres, ' ', u.apellidos) as usuario,
--     r.nombre as rol,
--     e.nombre as escuela
-- FROM usuarios u 
-- JOIN roles r ON u.rol_id = r.id 
-- LEFT JOIN escuelas e ON u.escuela_id = e.id
-- WHERE r.nombre = 'Escuela' 
-- AND u.activo = 1;
