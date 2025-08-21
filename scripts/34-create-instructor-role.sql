-- Crear rol de Instructor si no existe
INSERT IGNORE INTO roles (nombre, descripcion) 
VALUES ('Instructor', 'Instructor de cursos de formación');

-- Verificar roles existentes
SELECT * FROM roles ORDER BY nombre;

-- Ejemplo: Crear algunos usuarios instructores de prueba
-- (Opcional - puedes crear estos usuarios desde la interfaz)
/*
INSERT INTO usuarios (nombres, apellidos, cedula, email, password_hash, rol_id, activo) 
SELECT 
    'Juan Carlos', 'Pérez', '12345678', 'instructor1@example.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    r.id, 1
FROM roles r WHERE r.nombre = 'Instructor'
AND NOT EXISTS (SELECT 1 FROM usuarios WHERE email = 'instructor1@example.com');

INSERT INTO usuarios (nombres, apellidos, cedula, email, password_hash, rol_id, activo) 
SELECT 
    'María Elena', 'González', '87654321', 'instructor2@example.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    r.id, 1
FROM roles r WHERE r.nombre = 'Instructor'
AND NOT EXISTS (SELECT 1 FROM usuarios WHERE email = 'instructor2@example.com');
*/
