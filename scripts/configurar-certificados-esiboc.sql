-- Configuración específica para certificados ESIBOC-DNBC
-- Basada en los ejemplos proporcionados

-- Insertar configuración ESIBOC por defecto
INSERT INTO configuracion_certificados (
    nombre, 
    descripcion,
    escuela_id,
    -- Textos principales
    titulo_principal,
    subtitulo_escuela,
    subtitulo_certificado,
    texto_certifica_que,
    texto_identificado_con,
    texto_asistio_aprobo,
    texto_curso_autorizado,
    texto_bajo_acta,
    texto_duracion,
    texto_realizado_en,
    texto_constancia,
    -- Configuración de numeración
    mostrar_consecutivo,
    formato_consecutivo,
    numero_registro_base,
    mostrar_numero_acta,
    formato_numero_acta,
    -- Configuración de firmas
    mostrar_firma_director_nacional,
    mostrar_firma_director_escuela,
    mostrar_firma_coordinador,
    -- Configuración de contenido
    mostrar_contenido_programatico,
    columnas_contenido,
    titulo_contenido,
    -- Configuración de diseño
    orientacion,
    formato_papel,
    margenes_mm,
    usar_imagen_fondo,
    mostrar_logo_dnbc,
    mostrar_logo_escuela,
    -- Configuración de autoridades
    texto_director_nacional,
    cargo_director_nacional,
    texto_director_escuela,
    cargo_director_escuela,
    -- Configuración específica ESIBOC
    mostrar_marca_agua_bomberos,
    color_principal,
    color_secundario,
    activo,
    created_at
) VALUES (
    'ESIBOC - Formato Estándar',
    'Configuración estándar para certificados de la Escuela Internacional de Bomberos del Oriente Colombiano',
    1, -- ID de ESIBOC (ajustar según tu base de datos)
    -- Textos principales
    'Cuerpo de Bomberos Los Santos Santander\nEscuela Internacional de Bomberos del Oriente Colombiano\nESIBOC',
    'ESIBOC',
    'CERTIFICADO',
    'Certifica que:',
    'Identificado con C.C. No.',
    'Asistió y aprobó los requisitos del Curso:',
    'Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia',
    'Bajo acta número {numero_acta} del {fecha_acta} del Cuerpo de Bomberos Voluntarios Los Santos',
    'Con una duración de: {horas} HORAS',
    'Realizado en ({lugar}) del ({fecha_inicio_dia}) de ({fecha_inicio_mes}) al ({fecha_fin_dia}) de ({fecha_fin_mes}) de {año}',
    'En constancia de lo anterior, se firma a los {fecha_firma_dia} dias del mes de {fecha_firma_mes} de {fecha_firma_año}',
    -- Configuración de numeración
    1, -- mostrar_consecutivo
    '{año}-{registro_curso}-{consecutivo_formateado}',
    '184-2025',
    1, -- mostrar_numero_acta
    '{numero_acta}',
    -- Configuración de firmas
    1, -- mostrar_firma_director_nacional
    1, -- mostrar_firma_director_escuela
    1, -- mostrar_firma_coordinador
    -- Configuración de contenido
    1, -- mostrar_contenido_programatico
    2, -- columnas_contenido
    'CONTENIDO PROGRAMATICO',
    -- Configuración de diseño
    'horizontal', -- orientacion
    'letter', -- formato_papel
    '{"top": 15, "right": 15, "bottom": 15, "left": 15}',
    0, -- usar_imagen_fondo
    1, -- mostrar_logo_dnbc
    1, -- mostrar_logo_escuela
    -- Configuración de autoridades
    'CT. EN JEFE LINA MARÍA MARÍN RODRÍGUEZ',
    'Directora Nacional DNBC',
    'CT. MANUEL ENRIQUE SALAZAR HERNANDEZ',
    'Comandante Cuerpo de Bomberos Los Santos Sant.',
    -- Configuración específica ESIBOC
    1, -- mostrar_marca_agua_bomberos
    '#003366', -- color_principal (azul DNBC)
    '#CC0000', -- color_secundario (rojo)
    1, -- activo
    NOW()
);

-- Configuración genérica para otras escuelas
INSERT INTO configuracion_certificados (
    nombre, 
    descripcion,
    escuela_id,
    titulo_principal,
    subtitulo_certificado,
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
    titulo_contenido,
    orientacion,
    formato_papel,
    margenes_mm,
    mostrar_logo_dnbc,
    mostrar_logo_escuela,
    texto_director_nacional,
    cargo_director_nacional,
    activo,
    created_at
) VALUES (
    'DNBC - Formato Genérico',
    'Configuración genérica para escuelas de bomberos afiliadas a DNBC',
    NULL, -- Para todas las escuelas
    'DIRECCIÓN NACIONAL DE BOMBEROS DE COLOMBIA',
    'CERTIFICADO DE APROBACIÓN',
    'Certifica que:',
    'Identificado con C.C. No.',
    'Asistió y aprobó los requisitos del Curso:',
    'Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia',
    'Bajo acta número {numero_acta} del {fecha_acta} del {nombre_escuela}',
    'Con una duración de: {horas} horas académicas',
    'Realizado en {lugar} del {fecha_inicio} al {fecha_fin} de {año}',
    'En constancia de lo anterior, se firma a los {fecha_firma}',
    1, -- mostrar_consecutivo
    '{año}-{registro_curso}-{consecutivo_formateado}',
    'DNBC-2025',
    1, -- mostrar_numero_acta
    'ACTA-{consecutivo}',
    1, -- mostrar_firma_director_nacional
    1, -- mostrar_firma_director_escuela
    1, -- mostrar_firma_coordinador
    1, -- mostrar_contenido_programatico
    2, -- columnas_contenido
    'CONTENIDO PROGRAMÁTICO',
    'horizontal', -- orientacion
    'letter', -- formato_papel
    '{"top": 20, "right": 20, "bottom": 20, "left": 20}',
    1, -- mostrar_logo_dnbc
    1, -- mostrar_logo_escuela
    'CT. EN JEFE LINA MARÍA MARÍN RODRÍGUEZ',
    'Directora Nacional DNBC',
    1, -- activo
    NOW()
);
