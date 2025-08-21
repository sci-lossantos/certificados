-- Agregar campos de autenticación a la tabla participantes
ALTER TABLE participantes 
ADD COLUMN password VARCHAR(255) NULL AFTER email,
ADD COLUMN ultimo_acceso TIMESTAMP NULL,
ADD COLUMN activo BOOLEAN DEFAULT TRUE,
ADD COLUMN token_reset VARCHAR(255) NULL,
ADD COLUMN token_reset_expira TIMESTAMP NULL;

-- Crear índices para mejorar el rendimiento
CREATE INDEX idx_participantes_email ON participantes(email);
CREATE INDEX idx_participantes_cedula ON participantes(cedula);

-- Actualizar participantes existentes con contraseñas por defecto (cédula)
UPDATE participantes 
SET password = MD5(cedula) 
WHERE password IS NULL;

-- Hacer el campo password obligatorio después de la actualización
ALTER TABLE participantes 
MODIFY COLUMN password VARCHAR(255) NOT NULL;
