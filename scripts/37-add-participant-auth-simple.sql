-- Script simple para agregar campos de autenticación a participantes
-- Verificar estructura actual de la tabla
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'participantes'
ORDER BY ORDINAL_POSITION;

-- Intentar agregar password (ignorar si ya existe)
ALTER TABLE participantes ADD COLUMN password VARCHAR(255) NULL;

-- Intentar agregar ultimo_acceso (ignorar si ya existe)  
ALTER TABLE participantes ADD COLUMN ultimo_acceso TIMESTAMP NULL;

-- Intentar agregar token_reset (ignorar si ya existe)
ALTER TABLE participantes ADD COLUMN token_reset VARCHAR(255) NULL;

-- Intentar agregar token_reset_expira (ignorar si ya existe)
ALTER TABLE participantes ADD COLUMN token_reset_expira TIMESTAMP NULL;

-- Crear índices (ignorar si ya existen)
CREATE INDEX idx_participantes_email ON participantes(email);
CREATE INDEX idx_participantes_cedula ON participantes(cedula);

-- Actualizar participantes existentes con contraseñas por defecto
UPDATE participantes 
SET password = MD5(cedula) 
WHERE password IS NULL OR password = '';

-- Verificar estructura final
DESCRIBE participantes;
