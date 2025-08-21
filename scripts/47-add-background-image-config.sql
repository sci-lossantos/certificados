-- Agregar campos para imagen de fondo en configuración de certificados
ALTER TABLE configuracion_certificados 
ADD COLUMN imagen_fondo_pagina1 VARCHAR(500) NULL COMMENT 'Ruta de la imagen de fondo para página 1',
ADD COLUMN imagen_fondo_pagina2 VARCHAR(500) NULL COMMENT 'Ruta de la imagen de fondo para página 2',
ADD COLUMN usar_imagen_fondo BOOLEAN DEFAULT FALSE COMMENT 'Activar/desactivar uso de imagen de fondo',
ADD COLUMN opacidad_fondo DECIMAL(3,2) DEFAULT 1.00 COMMENT 'Opacidad de la imagen de fondo (0.1 a 1.0)',
ADD COLUMN ajustar_imagen_fondo ENUM('stretch', 'fit', 'fill') DEFAULT 'stretch' COMMENT 'Cómo ajustar la imagen al tamaño de página';

-- Crear directorio para fondos de certificados si no existe
-- Nota: Este directorio debe crearse manualmente: uploads/fondos_certificados/
