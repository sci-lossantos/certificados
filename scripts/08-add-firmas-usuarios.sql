-- Crear tabla para almacenar las firmas configuradas de los usuarios
CREATE TABLE IF NOT EXISTS firmas_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo_firma ENUM('texto', 'imagen') DEFAULT 'texto',
    contenido_firma TEXT, -- Para firma de texto o ruta de imagen
    activa BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_activa (usuario_id, activa)
);

-- Actualizar tabla de documentos para reflejar el nuevo flujo
ALTER TABLE documentos 
MODIFY COLUMN estado ENUM('generado', 'firmado_coordinador', 'revisado_directorio', 'completado') DEFAULT 'generado';

-- Insertar datos de ejemplo para firmas
INSERT INTO firmas_usuarios (usuario_id, tipo_firma, contenido_firma) 
SELECT u.id, 'texto', CONCAT(u.nombres, ' ', u.apellidos)
FROM usuarios u 
JOIN roles r ON u.rol_id = r.id 
WHERE r.nombre IN ('Coordinador', 'Director de Escuela', 'Educación DNBC', 'Dirección Nacional')
ON DUPLICATE KEY UPDATE contenido_firma = VALUES(contenido_firma);
