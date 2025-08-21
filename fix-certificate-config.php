<?php
require_once 'config/database.php';

echo "<h1>Reparar Configuración de Certificados</h1>";

// Obtener conexión a la base de datos
$conn = getMySQLiConnection();

// Verificar si la tabla existe
$check_table = "SHOW TABLES LIKE 'configuracion_certificados'";
$result = $conn->query($check_table);

if ($result->num_rows == 0) {
    echo "<p style='color: red;'>La tabla configuracion_certificados no existe.</p>";
    echo "<p>Ejecutando creación de tabla...</p>";
    
    // Crear tabla
    $create_table = "
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
        
        -- Configuración de colores
        color_principal VARCHAR(7) DEFAULT '#000000',
        color_secundario VARCHAR(7) DEFAULT '#666666',
        
        -- Pie de página
        texto_codigo_verificacion VARCHAR(255) DEFAULT 'Código de verificación: {codigo}',
        texto_expedicion VARCHAR(255) DEFAULT 'Expedido el {fecha}',
        
        -- Imagen de fondo
        imagen_fondo_pagina1 VARCHAR(500) NULL,
        imagen_fondo_pagina2 VARCHAR(500) NULL,
        usar_imagen_fondo BOOLEAN DEFAULT FALSE,
        opacidad_fondo DECIMAL(3,2) DEFAULT 1.00,
        ajustar_imagen_fondo ENUM('stretch', 'fit', 'fill') DEFAULT 'stretch',
        
        -- Metadatos
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (escuela_id) REFERENCES escuelas(id) ON DELETE CASCADE,
        UNIQUE KEY unique_escuela_config (escuela_id)
    )";
    
    if ($conn->query($create_table)) {
        echo "<p style='color: green;'>✓ Tabla configuracion_certificados creada exitosamente</p>";
    } else {
        echo "<p style='color: red;'>Error al crear tabla: " . $conn->error . "</p>";
        exit();
    }
}

// Insertar configuración para todas las escuelas activas
$insert_configs = "
INSERT IGNORE INTO configuracion_certificados (escuela_id)
SELECT id FROM escuelas WHERE activa = 1
";

if ($conn->query($insert_configs)) {
    echo "<p style='color: green;'>✓ Configuraciones por defecto insertadas</p>";
} else {
    echo "<p style='color: red;'>Error al insertar configuraciones: " . $conn->error . "</p>";
}

// Verificar resultados
$verify_sql = "
SELECT 
    e.id as escuela_id,
    e.nombre as escuela_nombre,
    CASE 
        WHEN cc.id IS NOT NULL THEN 'SÍ' 
        ELSE 'NO' 
    END as tiene_configuracion
FROM escuelas e
LEFT JOIN configuracion_certificados cc ON e.id = cc.escuela_id
WHERE e.activa = 1
ORDER BY e.nombre
";

$result = $conn->query($verify_sql);

echo "<h2>Estado de Configuraciones por Escuela</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID Escuela</th><th>Nombre Escuela</th><th>Tiene Configuración</th></tr>";

while ($row = $result->fetch_assoc()) {
    $color = $row['tiene_configuracion'] == 'SÍ' ? 'green' : 'red';
    echo "<tr>";
    echo "<td>" . $row['escuela_id'] . "</td>";
    echo "<td>" . $row['escuela_nombre'] . "</td>";
    echo "<td style='color: $color; font-weight: bold;'>" . $row['tiene_configuracion'] . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h2>Próximos pasos:</h2>";
echo "<ol>";
echo "<li>Ir a <a href='configuracion-certificados.php'>Configuración de Certificados</a> para personalizar</li>";
echo "<li>Probar con <a href='debug-certificate-config.php?id=1'>Debug Certificate Config</a></li>";
echo "</ol>";

$conn->close();
?>
