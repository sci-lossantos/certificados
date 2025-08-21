-- Agregar campos adicionales necesarios para el nuevo formato de certificado

-- Agregar campos a la tabla cursos
ALTER TABLE cursos 
ADD COLUMN numero_registro_curso VARCHAR(100) NULL COMMENT 'Número de registro del curso ante la DNBC',
ADD COLUMN lugar_realizacion VARCHAR(255) NULL COMMENT 'Lugar donde se realiza el curso',
ADD COLUMN fecha_inicio DATE NULL COMMENT 'Fecha de inicio del curso',
ADD COLUMN fecha_fin DATE NULL COMMENT 'Fecha de finalización del curso';

-- Agregar campos a configuracion_certificados para el nuevo formato
ALTER TABLE configuracion_certificados 
ADD COLUMN texto_certifica_que VARCHAR(255) DEFAULT 'Certifica que:' COMMENT 'Texto "Certifica que"',
ADD COLUMN texto_identificado VARCHAR(255) DEFAULT 'Identificado con C.C. No.' COMMENT 'Texto identificación',
ADD COLUMN texto_asistio_aprobo VARCHAR(255) DEFAULT 'Asistió y aprobó los requisitos del Curso:' COMMENT 'Texto asistió y aprobó',
ADD COLUMN texto_curso_autorizado VARCHAR(500) DEFAULT 'Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia' COMMENT 'Texto curso autorizado',
ADD COLUMN texto_bajo_acta VARCHAR(255) DEFAULT 'Bajo acta número {numero_acta} del {fecha_acta} del {nombre_cuerpo_bomberos}' COMMENT 'Texto bajo acta',
ADD COLUMN texto_duracion VARCHAR(255) DEFAULT 'Con una duración de: {horas} horas académicas' COMMENT 'Texto duración',
ADD COLUMN texto_realizado_en VARCHAR(255) DEFAULT 'Realizado en {lugar} del {fecha_inicio} al {fecha_fin}' COMMENT 'Texto realizado en',
ADD COLUMN texto_constancia VARCHAR(255) DEFAULT 'En constancia de lo anterior, se firma a los {fecha_firma}' COMMENT 'Texto constancia',
ADD COLUMN fecha_firma_certificados DATE NULL COMMENT 'Fecha en que se firman los certificados';

-- Actualizar configuraciones existentes
UPDATE configuracion_certificados 
SET texto_certifica_que = 'Certifica que:',
    texto_identificado = 'Identificado con C.C. No.',
    texto_asistio_aprobo = 'Asistió y aprobó los requisitos del Curso:',
    texto_curso_autorizado = 'Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia',
    texto_bajo_acta = 'Bajo acta número {numero_acta} del {fecha_acta} del {nombre_cuerpo_bomberos}',
    texto_duracion = 'Con una duración de: {horas} horas académicas',
    texto_realizado_en = 'Realizado en {lugar} del {fecha_inicio} al {fecha_fin}',
    texto_constancia = 'En constancia de lo anterior, se firma a los {fecha_firma}'
WHERE texto_certifica_que IS NULL;

-- Actualizar algunos cursos de ejemplo con datos
UPDATE cursos 
SET numero_registro_curso = CONCAT('REG-CURSO-', id, '-2024'),
    lugar_realizacion = 'Instalaciones de la Escuela',
    fecha_inicio = DATE_SUB(CURDATE(), INTERVAL 30 DAY),
    fecha_fin = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
WHERE numero_registro_curso IS NULL;
