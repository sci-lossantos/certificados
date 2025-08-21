-- Script para debuggear el flujo de documentos

-- 1. Verificar estados actuales de documentos
SELECT 
    d.id,
    d.codigo_unico,
    d.tipo,
    d.estado,
    d.created_at,
    c.nombre as curso_nombre,
    e.nombre as escuela_nombre,
    e.id as escuela_id
FROM documentos d
JOIN cursos c ON d.curso_id = c.id
JOIN escuelas e ON c.escuela_id = e.id
ORDER BY d.created_at DESC;

-- 2. Verificar firmas registradas
SELECT 
    fd.documento_id,
    fd.tipo_firma,
    fd.accion,
    fd.fecha_firma,
    CONCAT(u.nombres, ' ', u.apellidos) as firmante,
    r.nombre as rol
FROM firmas_documentos fd
JOIN usuarios u ON fd.usuario_id = u.id
JOIN roles r ON u.rol_id = r.id
ORDER BY fd.fecha_firma DESC;

-- 3. Verificar usuarios Director de Escuela y sus escuelas asignadas
SELECT 
    u.id,
    CONCAT(u.nombres, ' ', u.apellidos) as nombre_completo,
    u.email,
    r.nombre as rol,
    u.escuela_id,
    e.nombre as escuela_nombre
FROM usuarios u
JOIN roles r ON u.rol_id = r.id
LEFT JOIN escuelas e ON u.escuela_id = e.id
WHERE r.nombre = 'Director de Escuela';

-- 4. Verificar qué documentos debería ver el Director de Escuela
-- (documentos en estado 'firmado_coordinador' de su escuela)
SELECT 
    d.id,
    d.codigo_unico,
    d.tipo,
    d.estado,
    c.nombre as curso_nombre,
    e.nombre as escuela_nombre,
    e.id as escuela_id
FROM documentos d
JOIN cursos c ON d.curso_id = c.id
JOIN escuelas e ON c.escuela_id = e.id
WHERE d.estado = 'firmado_coordinador'
ORDER BY d.created_at DESC;
