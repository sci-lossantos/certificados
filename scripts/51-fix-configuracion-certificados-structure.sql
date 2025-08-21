-- Crear tabla de configuración de certificados si no existe
CREATE TABLE IF NOT EXISTS configuracion_certificados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL DEFAULT 'Configuración por defecto',
    descripcion TEXT,
    
    -- Textos del certificado
    texto_titulo VARCHAR(255) DEFAULT 'CERTIFICADO DE PARTICIPACIÓN',
    texto_otorga VARCHAR(255) DEFAULT 'La Dirección Nacional de Bomberos de Colombia',
    texto_certifica VARCHAR(255) DEFAULT 'CERTIFICA QUE',
    texto_participacion TEXT DEFAULT 'Ha participado satisfactoriamente en el curso de',
    texto_duracion VARCHAR(255) DEFAULT 'Con una duración de {duracion_horas} horas académicas',
    texto_fechas VARCHAR(255) DEFAULT 'Realizado del {fecha_inicio} al {fecha_fin}',
    texto_lugar VARCHAR(255) DEFAULT 'En {ciudad_curso}',
    texto_calificacion VARCHAR(255) DEFAULT 'Obteniendo una calificación de {calificacion}',
    
    -- Configuración de numeración
    usar_numeracion BOOLEAN DEFAULT TRUE,
    prefijo_numero VARCHAR(50) DEFAULT 'CERT',
    formato_numero VARCHAR(100) DEFAULT '{prefijo}-{año}-{consecutivo}',
    
    -- Configuración de actas
    mostrar_acta BOOLEAN DEFAULT TRUE,
    texto_acta VARCHAR(255) DEFAULT 'Según consta en Acta No. {numero_acta} de fecha {fecha_acta}',
    
    -- Configuración de firmas
    mostrar_firma_director_nacional BOOLEAN DEFAULT TRUE,
    mostrar_firma_director_escuela BOOLEAN DEFAULT TRUE,
    mostrar_firma_coordinador BOOLEAN DEFAULT FALSE,
    
    -- Configuración de contenido programático
    mostrar_contenido_programatico BOOLEAN DEFAULT TRUE,
    columnas_contenido INT DEFAULT 2,
    
    -- Configuración de imagen de fondo
    imagen_fondo VARCHAR(500),
    usar_imagen_fondo BOOLEAN DEFAULT FALSE,
    
    -- Metadatos
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Nuevas columnas
    texto_certificacion VARCHAR(255),
    texto_otorgado VARCHAR(255),
    texto_curso TEXT,
    mostrar_registro_curso BOOLEAN,
    mostrar_numero_acta BOOLEAN,
    mostrar_fecha_acta BOOLEAN,
    usar_numeracion_consecutiva BOOLEAN
);

-- Insertar configuración por defecto si no existe
INSERT IGNORE INTO configuracion_certificados (
    id, 
    nombre, 
    descripcion,
    texto_titulo,
    texto_otorga,
    texto_certifica,
    texto_participacion,
    texto_duracion,
    texto_fechas,
    texto_lugar,
    texto_calificacion,
    usar_numeracion,
    prefijo_numero,
    formato_numero,
    mostrar_acta,
    texto_acta,
    mostrar_firma_director_nacional,
    mostrar_firma_director_escuela,
    mostrar_firma_coordinador,
    mostrar_contenido_programatico,
    columnas_contenido,
    usar_imagen_fondo,
    activo,
    texto_certificacion,
    texto_otorgado,
    texto_curso,
    mostrar_registro_curso,
    mostrar_numero_acta,
    mostrar_fecha_acta,
    prefijo_numeracion,
    usar_numeracion_consecutiva
) VALUES (
    1,
    'Configuración Estándar DNBC',
    'Configuración estándar para certificados de la Dirección Nacional de Bomberos de Colombia',
    'CERTIFICADO DE PARTICIPACIÓN',
    'La Dirección Nacional de Bomberos de Colombia',
    'CERTIFICA QUE',
    'Ha participado satisfactoriamente en el curso de',
    'Con una duración de {duracion_horas} horas académicas',
    'Realizado del {fecha_inicio} al {fecha_fin}',
    'En {ciudad_curso}',
    'Obteniendo una calificación de {calificacion}',
    TRUE,
    'CERT',
    '{prefijo}-{año}-{consecutivo}',
    TRUE,
    'Según consta en Acta No. {numero_acta} de fecha {fecha_acta}',
    TRUE,
    TRUE,
    FALSE,
    TRUE,
    2,
    FALSE,
    TRUE,
    'CERTIFICAMOS',
    'Que se otorga el presente certificado a:',
    'Por su participación en el curso:', 'Desarrollado en el marco del',
    1, 1, 1, 1, 1
);

-- Verificar que la tabla tenga los datos correctos
SELECT 'Configuración de certificados creada correctamente' as mensaje;
SELECT COUNT(*) as total_configuraciones FROM configuracion_certificados;

-- Verificar estructura de la tabla
DESCRIBE configuracion_certificados;
