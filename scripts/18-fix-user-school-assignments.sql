-- Verificar y corregir asignaciones de escuelas a usuarios

-- Primero, verificar la estructura actual
SELECT 'Verificando estructura de usuarios...' as mensaje;
DESCRIBE usuarios;

-- Verificar usuarios sin escuela asignada
SELECT 'Usuarios sin escuela asignada:' as mensaje;
SELECT u.id, u.nombres, u.apellidos, u.email, r.nombre as rol
FROM usuarios u 
JOIN roles r ON u.rol_id = r.id 
WHERE u.escuela_id IS NULL 
AND r.nombre IN ('Escuela', 'Director de Escuela', 'Coordinador')
AND u.activo = 1;

-- Verificar escuelas disponibles
SELECT 'Escuelas disponibles:' as mensaje;
SELECT id, nombre, director_id, coordinador_id, activa 
FROM escuelas 
WHERE activa = 1;

-- Si necesitas asignar usuarios a escuelas automáticamente basado en algún criterio
-- Descomenta y ajusta según tus necesidades:

-- Ejemplo: Asignar usuarios con rol 'Escuela' a la primera escuela disponible
-- UPDATE usuarios u 
-- SET escuela_id = (SELECT MIN(id) FROM escuelas WHERE activa = 1)
-- WHERE u.rol_id = (SELECT id FROM roles WHERE nombre = 'Escuela')
-- AND u.escuela_id IS NULL;

-- Verificar resultado final
SELECT 'Estado final de asignaciones:' as mensaje;
SELECT u.id, u.nombres, u.apellidos, u.escuela_id, e.nombre as escuela_nombre, r.nombre as rol
FROM usuarios u 
LEFT JOIN escuelas e ON u.escuela_id = e.id
JOIN roles r ON u.rol_id = r.id 
WHERE r.nombre IN ('Escuela', 'Director de Escuela', 'Coordinador')
AND u.activo = 1
ORDER BY r.nombre, u.nombres;
