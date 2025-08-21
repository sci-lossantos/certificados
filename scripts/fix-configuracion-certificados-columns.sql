-- Agregar todas las columnas faltantes a la tabla configuracion_certificados
-- Agregando todas las columnas que el código PHP necesita

-- Primero verificar si la tabla existe, si no, crearla
CREATE TABLE IF NOT EXISTS configuracion_certificados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Agregar columna activo si no existe
ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS activo TINYINT(1) DEFAULT 1;

-- Agregar columnas de textos del certificado
ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS texto_certifica_que VARCHAR(255) DEFAULT 'Certifica que:';

ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS texto_identificado_con VARCHAR(255) DEFAULT 'Identificado con C.C. No.';

ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS texto_asistio_aprobo VARCHAR(255) DEFAULT 'Asistió y aprobó los requisitos del Curso:';

ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS texto_curso_autorizado TEXT DEFAULT 'Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia';

ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS texto_bajo_acta TEXT DEFAULT 'Bajo acta número {numero_acta} del {fecha_acta} del {nombre_cuerpo_bomberos}';

ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS texto_duracion VARCHAR(255) DEFAULT 'Con una duración de: {horas} horas académicas';

ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS texto_realizado_en VARCHAR(255) DEFAULT 'Realizado en {lugar_realizacion} del {fecha_inicio} al {fecha_fin}';

ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS texto_constancia VARCHAR(255) DEFAULT 'En constancia de lo anterior, se firma a los {fecha_firma}';

-- Agregar columnas de configuración de numeración
ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS mostrar_consecutivo TINYINT(1) DEFAULT 1;

ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS formato_consecutivo VARCHAR(255) DEFAULT '{numero_registro}-{orden_alfabetico}';

ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS numero_registro_base VARCHAR(255) DEFAULT 'DNBC-2024';

ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS mostrar_numero_acta TINYINT(1) DEFAULT 1;

ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS formato_numero_acta VARCHAR(255) DEFAULT 'ACTA-{escuela_id}-{consecutivo}';

-- Agregar columnas de configuración de firmas
ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS mostrar_firma_director_nacional TINYINT(1) DEFAULT 1;

ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS mostrar_firma_director_escuela TINYINT(1) DEFAULT 1;

ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS mostrar_firma_coordinador TINYINT(1) DEFAULT 1;

-- Agregar columnas de contenido programático
ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS mostrar_contenido_programatico TINYINT(1) DEFAULT 1;

ALTER TABLE configuracion_certificados 
ADD COLUMN IF NOT EXISTS columnas_contenido INT DEFAULT 2;

-- Insertar configuración por defecto ESIBOC si no existe
INSERT IGNORE INTO configuracion_certificados (
    nombre, 
    descripcion,
    texto_certifica_que,
    texto_identificado_con,
    texto_asistio_aprobo,
    texto_curso_autorizado,
    texto_bajo_acta,
    texto_duracion,
    texto_realizado_en,
    texto_constancia,
    mostrar_consecutivo,
    formato_consecutivo,
    numero_registro_base,
    mostrar_numero_acta,
    formato_numero_acta,
    mostrar_firma_director_nacional,
    mostrar_firma_director_escuela,
    mostrar_firma_coordinador,
    mostrar_contenido_programatico,
    columnas_contenido,
    activo
) VALUES (
    'ESIBOC - Configuración Estándar',
    'Configuración estándar para certificados ESIBOC-DNBC',
    'Certifica que:',
    'Identificado con C.C. No.',
    'Asistió y aprobó los requisitos del Curso:',
    'Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia',
    'Bajo acta número {numero_acta} del {fecha_acta} del {nombre_cuerpo_bomberos}',
    'Con una duración de: {horas} HORAS',
    'Realizado en ({lugar_realizacion}) del ({fecha_inicio}) de ({mes_inicio}) al ({fecha_fin}) de ({mes_fin}) de {año}',
    'En constancia de lo anterior, se firma a los {fecha_firma} dias del mes de {mes_firma} de {año_firma}',
    1,
    '{año}-{registro_curso}-{consecutivo:02d}',
    '2025-184',
    1,
    '{numero_acta}',
    1,
    1,
    1,
    1,
    2,
    1
);

-- Verificar la estructura final
DESCRIBE configuracion_certificados;
