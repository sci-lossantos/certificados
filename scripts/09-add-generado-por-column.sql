-- Agregar columna generado_por a la tabla documentos
ALTER TABLE documentos 
ADD COLUMN generado_por INT NULL,
ADD CONSTRAINT fk_documentos_generado_por 
FOREIGN KEY (generado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Actualizar documentos existentes (opcional, asignar a un usuario por defecto)
-- UPDATE documentos SET generado_por = 1 WHERE generado_por IS NULL;
