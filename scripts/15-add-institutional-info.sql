-- Agregar información institucional a las escuelas
ALTER TABLE escuelas ADD COLUMN IF NOT EXISTS nombre_completo TEXT;
ALTER TABLE escuelas ADD COLUMN IF NOT EXISTS nombre_estacion VARCHAR(200);
ALTER TABLE escuelas ADD COLUMN IF NOT EXISTS codigo_formato VARCHAR(50);
ALTER TABLE escuelas ADD COLUMN IF NOT EXISTS version_formato VARCHAR(10) DEFAULT '1';
ALTER TABLE escuelas ADD COLUMN IF NOT EXISTS fecha_vigencia DATE;
ALTER TABLE escuelas ADD COLUMN IF NOT EXISTS logo_institucional VARCHAR(255);
ALTER TABLE escuelas ADD COLUMN IF NOT EXISTS pie_pagina TEXT;
ALTER TABLE escuelas ADD COLUMN IF NOT EXISTS slogan VARCHAR(200);

-- Actualizar la escuela existente con datos de ejemplo
UPDATE escuelas SET 
    nombre_completo = 'BOMBEROS VOLUNTARIOS LOS SANTOS',
    nombre_estacion = 'ESTACION DE BOMBEROS CT. JAIME DIAZ CAMARGO',
    codigo_formato = 'ESIBOC-FO-03',
    version_formato = '1',
    fecha_vigencia = '2024-12-14',
    pie_pagina = 'CUERPO BOMBEROS VOLUNTARIOS LOS SANTOS\nESCUELA INTERNACIONAL DE BOMBEROS DEL ORIENTE COLOMBIANO',
    slogan = 'FORMATO DIRECTORIO FINALIZACIÓN DE CURSO'
WHERE id = 1;

-- Agregar campos adicionales a participantes para el directorio completo
ALTER TABLE participantes ADD COLUMN IF NOT EXISTS entidad VARCHAR(200);
ALTER TABLE participantes ADD COLUMN IF NOT EXISTS celular VARCHAR(20);

-- Crear tabla para instructores y coordinadores
CREATE TABLE IF NOT EXISTS instructores_curso (
    id INT PRIMARY KEY AUTO_INCREMENT,
    curso_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('coordinador', 'instructor', 'logistica') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    UNIQUE KEY unique_instructor_curso (curso_id, usuario_id, tipo)
);
