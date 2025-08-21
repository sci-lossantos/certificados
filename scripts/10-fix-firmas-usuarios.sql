-- Modificar la columna tipo_firma para que pueda almacenar valores más largos
ALTER TABLE firmas_usuarios MODIFY COLUMN tipo_firma VARCHAR(20) NOT NULL;

-- Verificar la estructura de la tabla
DESCRIBE firmas_usuarios;
