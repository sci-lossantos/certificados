-- Este script crea una vista para facilitar la visualización de documentos
-- La vista combina información de varias tablas de forma segura

CREATE OR REPLACE VIEW vista_documentos AS
SELECT 
    d.id,
    d.tipo,
    d.codigo_unico,
    d.estado,
    d.created_at,
    d.curso_id,
    d.participante_id,
    d.generado_por,
    c.nombre AS curso_nombre,
    c.numero_registro,
    c.fecha_inicio,
    c.fecha_fin,
    e.nombre AS escuela_nombre,
    e.codigo AS escuela_codigo,
    CONCAT(u.nombres, ' ', u.apellidos) AS generado_por_nombre,
    p.nombres AS participante_nombres,
    p.apellidos AS participante_apellidos,
    p.cedula AS participante_cedula
FROM 
    documentos d
JOIN 
    cursos c ON d.curso_id = c.id
JOIN 
    escuelas e ON c.escuela_id = e.id
LEFT JOIN 
    usuarios u ON d.generado_por = u.id
LEFT JOIN 
    participantes p ON d.participante_id = p.id;

-- Consulta de prueba para verificar la vista
SELECT * FROM vista_documentos LIMIT 5;
