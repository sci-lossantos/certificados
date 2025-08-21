-- Primero verificar los estados actuales
SELECT DISTINCT estado FROM documentos;

-- Crear una columna temporal para el nuevo estado
ALTER TABLE documentos ADD COLUMN nuevo_estado ENUM(
    'generado',
    'firmado_coordinador', 
    'revisado_directorio_coordinador',
    'firmado_director_escuela',
    'revisado_director_escuela', 
    'aprobado_educacion_dnbc',
    'firmado_director_nacional',
    'completado'
) DEFAULT 'generado';

-- Mapear los estados existentes a los nuevos
UPDATE documentos SET nuevo_estado = CASE 
    WHEN estado = 'generado' THEN 'generado'
    WHEN estado = 'firmado_coordinador' THEN 'firmado_coordinador'
    WHEN estado = 'revisado_directorio' THEN 'revisado_directorio_coordinador'
    WHEN estado = 'completado' THEN 'completado'
    ELSE 'generado'
END;

-- Eliminar la columna antigua
ALTER TABLE documentos DROP COLUMN estado;

-- Renombrar la nueva columna
ALTER TABLE documentos CHANGE COLUMN nuevo_estado estado ENUM(
    'generado',
    'firmado_coordinador', 
    'revisado_directorio_coordinador',
    'firmado_director_escuela',
    'revisado_director_escuela', 
    'aprobado_educacion_dnbc',
    'firmado_director_nacional',
    'completado'
) DEFAULT 'generado' NOT NULL;

-- Verificar que no hay problemas con la tabla de firmas
SELECT DISTINCT tipo_firma FROM firmas_documentos;

-- Actualizar la tabla de firmas para incluir todos los tipos
ALTER TABLE firmas_documentos 
MODIFY COLUMN tipo_firma ENUM(
    'coordinador',
    'director_escuela', 
    'educacion_dnbc',
    'director_nacional'
) NOT NULL;

-- Agregar campo para tipo de acción (firma o revisión) si no existe
ALTER TABLE firmas_documentos 
ADD COLUMN IF NOT EXISTS accion ENUM('firma', 'revision') DEFAULT 'firma' AFTER tipo_firma;

-- Crear vista para el flujo de documentos
DROP VIEW IF EXISTS vista_flujo_documentos;

CREATE VIEW vista_flujo_documentos AS
SELECT 
    d.id,
    d.tipo,
    d.codigo_unico,
    d.estado,
    d.created_at,
    c.nombre as curso_nombre,
    c.numero_registro,
    e.nombre as escuela_nombre,
    CONCAT(coord.nombres, ' ', coord.apellidos) as coordinador_nombre,
    CONCAT(dir_esc.nombres, ' ', dir_esc.apellidos) as director_escuela_nombre,
    -- Verificar si ya fue firmado/revisado por cada rol
    (SELECT COUNT(*) FROM firmas_documentos fd WHERE fd.documento_id = d.id AND fd.tipo_firma = 'coordinador') as firmado_coordinador,
    (SELECT COUNT(*) FROM firmas_documentos fd WHERE fd.documento_id = d.id AND fd.tipo_firma = 'director_escuela') as firmado_director_escuela,
    (SELECT COUNT(*) FROM firmas_documentos fd WHERE fd.documento_id = d.id AND fd.tipo_firma = 'educacion_dnbc') as aprobado_educacion,
    (SELECT COUNT(*) FROM firmas_documentos fd WHERE fd.documento_id = d.id AND fd.tipo_firma = 'director_nacional') as firmado_director_nacional
FROM documentos d
JOIN cursos c ON d.curso_id = c.id
JOIN escuelas e ON c.escuela_id = e.id
LEFT JOIN usuarios coord ON c.coordinador_id = coord.id
LEFT JOIN usuarios dir_esc ON e.director_id = dir_esc.id;

-- Verificar el resultado
SELECT 'Estados de documentos después de la migración:' as mensaje;
SELECT estado, COUNT(*) as cantidad FROM documentos GROUP BY estado;
