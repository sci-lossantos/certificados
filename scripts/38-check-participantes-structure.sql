-- Script para verificar la estructura de la tabla participantes
DESCRIBE participantes;

-- Verificar datos de ejemplo
SELECT id, nombre, apellido, cedula, email, password, activo, ultimo_acceso
FROM participantes 
LIMIT 5;

-- Contar participantes con contraseña
SELECT 
    COUNT(*) as total_participantes,
    COUNT(password) as con_password,
    COUNT(*) - COUNT(password) as sin_password
FROM participantes;
