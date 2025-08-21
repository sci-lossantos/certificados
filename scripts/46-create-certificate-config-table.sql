-- Crear tabla de configuración de certificados por escuela
CREATE TABLE IF NOT EXISTS configuracion_certificados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    escuela_id INT NOT NULL,
    
    -- Textos del encabezado
    titulo_principal VARCHAR(255) DEFAULT 'DIRECCIÓN NACIONAL DE BOMBEROS DE COLOMBIA',
    subtitulo_certificado VARCHAR(255) DEFAULT 'CERTIFICADO DE APROBACIÓN',
    
    -- Textos del cuerpo del certificado
    texto_certifica TEXT DEFAULT 'La Dirección Nacional de Bomberos de Colombia\nPor medio del presente certifica que:',
    texto_aprobacion TEXT DEFAULT 'Ha aprobado satisfactoriamente el curso:',
    texto_intensidad VARCHAR(255) DEFAULT 'Con una intensidad horaria de {horas} horas académicas',
    texto_realizacion VARCHAR(255) DEFAULT 'Realizado en el año {año}',
    
    -- Configuración de firmas
    mostrar_firma_director_nacional BOOLEAN DEFAULT TRUE,
    texto_director_nacional VARCHAR(255) DEFAULT 'Dirección Nacional\nBomberos de Colombia',
    mostrar_firma_director_escuela BOOLEAN DEFAULT TRUE,
    texto_director_escuela VARCHAR(255) DEFAULT 'Director de Escuela',
    mostrar_firma_coordinador BOOLEAN DEFAULT TRUE,
    texto_coordinador VARCHAR(255) DEFAULT 'Coordinador del Curso',
    
    -- Configuración página 2
    titulo_contenido VARCHAR(255) DEFAULT 'CONTENIDO TEMÁTICO',
    mostrar_info_curso_pagina2 BOOLEAN DEFAULT FALSE,
    
    -- Configuración de logos
    logo_principal VARCHAR(255) NULL,
    logo_secundario VARCHAR(255) NULL,
    mostrar_logos BOOLEAN DEFAULT TRUE,
    
    -- Configuración de colores (para futuras mejoras)
    color_principal VARCHAR(7) DEFAULT '#000000',
    color_secundario VARCHAR(7) DEFAULT '#666666',
    
    -- Pie de página
    texto_codigo_verificacion VARCHAR(255) DEFAULT 'Código de verificación: {codigo}',
    texto_expedicion VARCHAR(255) DEFAULT 'Expedido el {fecha}',
    
    -- Metadatos
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (escuela_id) REFERENCES escuelas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_escuela_config (escuela_id)
);

-- Insertar configuración por defecto para escuelas existentes
INSERT IGNORE INTO configuracion_certificados (escuela_id)
SELECT id FROM escuelas WHERE activa = 1;
