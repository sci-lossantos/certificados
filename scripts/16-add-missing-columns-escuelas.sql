-- Verificar y agregar columnas faltantes en la tabla escuelas para configuración institucional
ALTER TABLE escuelas 
ADD COLUMN IF NOT EXISTS nombre_completo VARCHAR(500),
ADD COLUMN IF NOT EXISTS nombre_estacion VARCHAR(500),
ADD COLUMN IF NOT EXISTS codigo_formato VARCHAR(100),
ADD COLUMN IF NOT EXISTS version_formato VARCHAR(50) DEFAULT '1',
ADD COLUMN IF NOT EXISTS fecha_vigencia DATE,
ADD COLUMN IF NOT EXISTS pie_pagina TEXT,
ADD COLUMN IF NOT EXISTS slogan VARCHAR(300);

-- Actualizar escuelas existentes con valores por defecto
UPDATE escuelas 
SET nombre_completo = COALESCE(nombre_completo, UPPER(nombre)),
    codigo_formato = COALESCE(codigo_formato, CONCAT('ESIBOC-FO-', LPAD(id, 2, '0'))),
    version_formato = COALESCE(version_formato, '1'),
    slogan = COALESCE(slogan, 'FORMATO DIRECTORIO FINALIZACIÓN DE CURSO')
WHERE nombre_completo IS NULL OR codigo_formato IS NULL;
