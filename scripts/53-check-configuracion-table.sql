-- Verificar estructura de la tabla configuracion_certificados
DESCRIBE configuracion_certificados;

-- Si no existe, crearla
CREATE TABLE IF NOT EXISTS configuracion_certificados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL DEFAULT 'Configuración Estándar',
    descripcion TEXT,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar configuración básica si no existe
INSERT IGNORE INTO configuracion_certificados (id, nombre, descripcion, activo) 
VALUES (1, 'Configuración Estándar DNBC', 'Configuración básica para certificados', 1);
