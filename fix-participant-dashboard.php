<?php
// Script para reparar el dashboard del participante
require_once 'config/database.php';

echo "<h1>Reparando Dashboard del Participante</h1>";

try {
    $conn = getMySQLiConnection();
    echo "<p style='color: green;'>✓ Conexión a base de datos exitosa</p>";
    
    // Verificar tabla participantes
    $result = $conn->query("SHOW TABLES LIKE 'participantes'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Tabla participantes existe</p>";
        
        // Contar participantes
        $count = $conn->query("SELECT COUNT(*) as total FROM participantes")->fetch_assoc()['total'];
        echo "<p>Participantes registrados: " . $count . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Tabla participantes no existe</p>";
    }
    
    // Verificar tabla documentos
    $result = $conn->query("SHOW TABLES LIKE 'documentos'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Tabla documentos existe</p>";
        
        // Contar certificados
        $count = $conn->query("SELECT COUNT(*) as total FROM documentos WHERE tipo = 'certificado'")->fetch_assoc()['total'];
        echo "<p>Certificados generados: " . $count . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Tabla documentos no existe</p>";
    }
    
    // Verificar tabla configuracion_certificados
    $result = $conn->query("SHOW TABLES LIKE 'configuracion_certificados'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Tabla configuracion_certificados existe</p>";
        
        // Contar configuraciones
        $count = $conn->query("SELECT COUNT(*) as total FROM configuracion_certificados")->fetch_assoc()['total'];
        echo "<p>Configuraciones de certificados: " . $count . "</p>";
        
        if ($count == 0) {
            echo "<p style='color: orange;'>Creando configuraciones por defecto...</p>";
            
            // Obtener escuelas
            $escuelas = $conn->query("SELECT id, nombre FROM escuelas");
            
            while ($escuela = $escuelas->fetch_assoc()) {
                $insert_sql = "
                    INSERT INTO configuracion_certificados (
                        escuela_id, titulo_principal, subtitulo_certificado, 
                        texto_certifica, texto_aprobacion, texto_intensidad, 
                        texto_realizacion, mostrar_firma_director_nacional, 
                        texto_director_nacional, mostrar_firma_director_escuela, 
                        texto_director_escuela, mostrar_firma_coordinador, 
                        texto_coordinador, titulo_contenido, 
                        texto_codigo_verificacion, texto_expedicion
                    ) VALUES (
                        ?, 'DIRECCIÓN NACIONAL DE BOMBEROS DE COLOMBIA', 
                        'CERTIFICADO DE APROBACIÓN',
                        'La Dirección Nacional de Bomberos de Colombia\nPor medio del presente certifica que:',
                        'Ha aprobado satisfactoriamente el curso:',
                        'Con una intensidad horaria de {horas} horas académicas',
                        'Realizado en el año {año}',
                        1, 'Dirección Nacional\nBomberos de Colombia',
                        1, 'Director de Escuela',
                        1, 'Coordinador del Curso',
                        'CONTENIDO TEMÁTICO',
                        'Código de verificación: {codigo}',
                        'Expedido el {fecha}'
                    )
                ";
                
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("i", $escuela['id']);
                
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>✓ Configuración creada para: " . $escuela['nombre'] . "</p>";
                } else {
                    echo "<p style='color: red;'>✗ Error creando configuración para: " . $escuela['nombre'] . "</p>";
                }
            }
        }
    } else {
        echo "<p style='color: red;'>✗ Tabla configuracion_certificados no existe</p>";
        echo "<p><a href='fix-certificate-config.php'>Crear tabla de configuración</a></p>";
    }
    
    echo "<hr>";
    echo "<p style='color: green;'>Reparación completada</p>";
    echo "<p><a href='participante-login.php'>Ir al login de participantes</a></p>";
    echo "<p><a href='test-participant-certificate.php'>Probar certificados</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
