-- Verificar la estructura actual de la tabla usuarios
DESCRIBE usuarios;

-- Añadir la columna escuela_id a la tabla usuarios
ALTER TABLE usuarios ADD COLUMN escuela_id INT NULL;

-- Añadir la restricción de clave foránea
ALTER TABLE usuarios 
ADD CONSTRAINT fk_usuarios_escuela 
FOREIGN KEY (escuela_id) REFERENCES escuelas(id) 
ON DELETE SET NULL;

-- Verificar que la columna se haya añadido correctamente
DESCRIBE usuarios;

-- Mostrar los usuarios actuales para referencia
SELECT id, nombres, apellidos, email, rol_id FROM usuarios WHERE activo = 1;

-- Mostrar las escuelas disponibles
SELECT id, nombre FROM escuelas WHERE activa = 1;

-- NOTA: Después de ejecutar este script, deberás asignar manualmente 
-- los usuarios a sus escuelas correspondientes con consultas como:
-- 
-- UPDATE usuarios SET escuela_id = [ID_ESCUELA] WHERE id = [ID_USUARIO];
