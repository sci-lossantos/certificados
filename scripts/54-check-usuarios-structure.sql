-- Verificar la estructura de la tabla usuarios
DESCRIBE usuarios;

-- Mostrar algunos datos de ejemplo
SELECT * FROM usuarios LIMIT 5;

-- Verificar roles existentes
SELECT * FROM roles;

-- Verificar relación usuarios-roles
SELECT u.id, u.nombres, u.apellidos, u.email, r.nombre as rol_nombre 
FROM usuarios u 
LEFT JOIN roles r ON u.rol_id = r.id 
LIMIT 10;
