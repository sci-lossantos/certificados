-- Script final para configurar autenticación de participantes
-- Verificar estructura actual
SHOW COLUMNS FROM participantes;

-- Agregar columna password si no existe
ALTER TABLE participantes 
ADD COLUMN IF NOT EXISTS password VARCHAR(255) NULL AFTER email;

-- Agregar columna activo si no existe
ALTER TABLE participantes 
ADD COLUMN IF NOT EXISTS activo BOOLEAN DEFAULT TRUE;

-- Agregar columna ultimo_acceso si no existe
ALTER TABLE participantes 
ADD COLUMN IF NOT EXISTS ultimo_acceso TIMESTAMP NULL;

-- Agregar columna token_reset si no existe
ALTER TABLE participantes 
ADD COLUMN IF NOT EXISTS token_reset VARCHAR(255) NULL;

-- Agregar columna token_reset_expira si no existe
ALTER TABLE participantes 
ADD COLUMN IF NOT EXISTS token_reset_expira TIMESTAMP NULL;

-- Crear índices para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_participantes_email ON participantes(email);
CREATE INDEX IF NOT EXISTS idx_participantes_cedula ON participantes(cedula);

-- Configurar contraseñas por defecto (cédula) para participantes existentes
UPDATE participantes 
SET password = MD5(cedula), activo = TRUE
WHERE password IS NULL OR password = '';

-- Verificar que todos los participantes tengan contraseña
SELECT 
    COUNT(*) as total_participantes,
    COUNT(password) as con_password,
    COUNT(CASE WHEN activo = 1 THEN 1 END) as activos
FROM participantes;

-- Mostrar algunos participantes de ejemplo
SELECT id, nombres, apellidos, cedula, email, 
       CASE WHEN password IS NOT NULL THEN 'Configurada' ELSE 'Sin configurar' END as password_status,
       activo
FROM participantes 
LIMIT 5;

-- Mostrar estructura final de la tabla
DESCRIBE participantes;
