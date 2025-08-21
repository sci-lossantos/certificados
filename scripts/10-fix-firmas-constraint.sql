-- Eliminar la restricción única problemática y recrearla correctamente
ALTER TABLE firmas_usuarios DROP INDEX unique_usuario_activa;

-- Crear nueva restricción que solo aplique para firmas activas
-- Esto permite múltiples firmas inactivas pero solo una activa por usuario
ALTER TABLE firmas_usuarios 
ADD CONSTRAINT unique_usuario_activa 
UNIQUE (usuario_id, activa) 
WHERE activa = TRUE;

-- Si la sintaxis anterior no funciona en MySQL, usar este enfoque alternativo:
-- Eliminar registros duplicados inactivos si existen
DELETE f1 FROM firmas_usuarios f1
INNER JOIN firmas_usuarios f2 
WHERE f1.id > f2.id 
AND f1.usuario_id = f2.usuario_id 
AND f1.activa = FALSE 
AND f2.activa = FALSE;

-- Crear índice único solo para firmas activas usando un truco de MySQL
CREATE UNIQUE INDEX idx_usuario_activa_unique 
ON firmas_usuarios (usuario_id, (CASE WHEN activa = TRUE THEN 1 ELSE NULL END));
