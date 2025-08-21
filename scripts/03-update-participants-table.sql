-- Actualizar tabla de participantes para incluir fotografía
ALTER TABLE participantes 
ADD COLUMN fotografia VARCHAR(255) AFTER genero;

-- Crear directorio de uploads si no existe (esto debe hacerse manualmente en el servidor)
-- mkdir -p uploads/fotografias/
-- chmod 755 uploads/fotografias/
