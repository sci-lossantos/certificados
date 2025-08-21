-- Solucionar problemas sin eliminar restricciones existentes
-- Enfoque alternativo que no requiere eliminar índices

-- 1. Primero, limpiar datos duplicados en firmas_usuarios
-- Mantener solo el registro más reciente por usuario
DELETE f1 FROM firmas_usuarios f1
INNER JOIN (
    SELECT usuario_id, MAX(id) as max_id
    FROM firmas_usuarios 
    GROUP BY usuario_id
) f2 ON f1.usuario_id = f2.usuario_id AND f1.id < f2.max_id;

-- 2. Asegurar que solo hay una firma activa por usuario
UPDATE firmas_usuarios SET activa = FALSE;

-- 3. Activar solo la firma más reciente de cada usuario
UPDATE firmas_usuarios f1
INNER JOIN (
    SELECT usuario_id, MAX(id) as max_id
    FROM firmas_usuarios 
    GROUP BY usuario_id
) f2 ON f1.usuario_id = f2.usuario_id AND f1.id = f2.max_id
SET f1.activa = TRUE;

-- 4. Modificar la columna participante_id en documentos para permitir NULL correctamente
ALTER TABLE documentos MODIFY COLUMN participante_id INT NULL;

-- 5. Ampliar la columna tipo_firma si es necesario
ALTER TABLE firmas_usuarios MODIFY COLUMN tipo_firma VARCHAR(20) NOT NULL;

-- 6. Verificar las estructuras
SELECT 'Estructura de firmas_usuarios:' as info;
DESCRIBE firmas_usuarios;

SELECT 'Estructura de documentos:' as info;
DESCRIBE documentos;

-- 7. Verificar datos de firmas
SELECT 'Firmas por usuario:' as info;
SELECT usuario_id, COUNT(*) as total_firmas, SUM(activa) as firmas_activas
FROM firmas_usuarios 
GROUP BY usuario_id;
