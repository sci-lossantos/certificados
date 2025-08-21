-- Corregir la asignación del Director de Escuela
UPDATE usuarios 
SET escuela_id = 2 
WHERE id = 4 AND rol_id = (SELECT id FROM roles WHERE nombre = 'Director de Escuela');

-- Verificar la corrección
SELECT u.id, CONCAT(u.nombres, ' ', u.apellidos) as nombre_completo, 
       r.nombre as rol, u.escuela_id, e.nombre as escuela_nombre 
FROM usuarios u 
JOIN roles r ON u.rol_id = r.id 
LEFT JOIN escuelas e ON u.escuela_id = e.id 
WHERE r.nombre = 'Director de Escuela';
