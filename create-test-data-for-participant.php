<?php
require_once 'config/database.php';

echo "<h1>Creando datos de prueba para participantes</h1>";

try {
    $conn = getMySQLiConnection();
    
    // 1. Verificar si existe un participante de prueba
    $check_participant = $conn->query("SELECT id FROM participantes WHERE cedula = '12345678' LIMIT 1");
    
    if ($check_participant->num_rows == 0) {
        echo "<p style='color: orange;'>Creando participante de prueba...</p>";
        
        $insert_participant = "
            INSERT INTO participantes (
                cedula, nombre, apellido, email, telefono, direccion, 
                fecha_nacimiento, genero, password_hash, activo
            ) VALUES (
                '12345678', 'Juan Carlos', 'Pérez López', 'juan.perez@test.com', 
                '3001234567', 'Calle 123 #45-67', '1990-01-15', 'M', 
                ?, 1
            )
        ";
        
        $password_hash = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $conn->prepare($insert_participant);
        $stmt->bind_param("s", $password_hash);
        
        if ($stmt->execute()) {
            $participante_id = $conn->insert_id;
            echo "<p style='color: green;'>✓ Participante creado con ID: $participante_id</p>";
            echo "<p><strong>Credenciales:</strong> Cédula: 12345678, Contraseña: 123456</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creando participante</p>";
            exit;
        }
    } else {
        $participante = $check_participant->fetch_assoc();
        $participante_id = $participante['id'];
        echo "<p style='color: green;'>✓ Participante ya existe con ID: $participante_id</p>";
    }
    
    // 2. Verificar si existe una escuela
    $check_school = $conn->query("SELECT id FROM escuelas WHERE activa = 1 LIMIT 1");
    
    if ($check_school->num_rows == 0) {
        echo "<p style='color: orange;'>Creando escuela de prueba...</p>";
        
        $insert_school = "
            INSERT INTO escuelas (
                nombre, direccion, telefono, email, director, activa
            ) VALUES (
                'Escuela de Bomberos Bogotá', 'Carrera 30 #26-25', '6013456789', 
                'escuela.bogota@bomberos.gov.co', 'Director de Prueba', 1
            )
        ";
        
        if ($conn->query($insert_school)) {
            $escuela_id = $conn->insert_id;
            echo "<p style='color: green;'>✓ Escuela creada con ID: $escuela_id</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creando escuela</p>";
            exit;
        }
    } else {
        $escuela = $check_school->fetch_assoc();
        $escuela_id = $escuela['id'];
        echo "<p style='color: green;'>✓ Escuela ya existe con ID: $escuela_id</p>";
    }
    
    // 3. Verificar si existe un curso
    $check_course = $conn->query("SELECT id FROM cursos WHERE escuela_id = $escuela_id LIMIT 1");
    
    if ($check_course->num_rows == 0) {
        echo "<p style='color: orange;'>Creando curso de prueba...</p>";
        
        $insert_course = "
            INSERT INTO cursos (
                nombre, descripcion, duracion_horas, escuela_id, activo, 
                fecha_inicio, fecha_fin
            ) VALUES (
                'Curso Básico de Bomberos', 'Curso introductorio para nuevos bomberos', 
                40, $escuela_id, 1, '2024-01-15', '2024-02-15'
            )
        ";
        
        if ($conn->query($insert_course)) {
            $curso_id = $conn->insert_id;
            echo "<p style='color: green;'>✓ Curso creado con ID: $curso_id</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creando curso</p>";
            exit;
        }
    } else {
        $curso = $check_course->fetch_assoc();
        $curso_id = $curso['id'];
        echo "<p style='color: green;'>✓ Curso ya existe con ID: $curso_id</p>";
    }
    
    // 4. Crear matrícula
    $check_matricula = $conn->query("
        SELECT id FROM matriculas 
        WHERE participante_id = $participante_id AND curso_id = $curso_id
    ");
    
    if ($check_matricula->num_rows == 0) {
        echo "<p style='color: orange;'>Creando matrícula de prueba...</p>";
        
        $insert_matricula = "
            INSERT INTO matriculas (
                participante_id, curso_id, fecha_inscripcion, fecha_inicio, 
                fecha_finalizacion, calificacion, estado
            ) VALUES (
                $participante_id, $curso_id, '2024-01-10', '2024-01-15', 
                '2024-02-15', 85, 'finalizado'
            )
        ";
        
        if ($conn->query($insert_matricula)) {
            echo "<p style='color: green;'>✓ Matrícula creada</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creando matrícula</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ Matrícula ya existe</p>";
    }
    
    // 5. Crear documento/certificado
    $check_documento = $conn->query("
        SELECT id FROM documentos 
        WHERE participante_id = $participante_id AND curso_id = $curso_id AND tipo = 'certificado'
    ");
    
    if ($check_documento->num_rows == 0) {
        echo "<p style='color: orange;'>Creando certificado de prueba...</p>";
        
        $codigo_unico = 'CERT-' . date('Y') . '-' . str_pad($participante_id, 6, '0', STR_PAD_LEFT);
        
        $insert_documento = "
            INSERT INTO documentos (
                participante_id, curso_id, tipo, estado, codigo_unico, 
                fecha_generacion, generado_por
            ) VALUES (
                $participante_id, $curso_id, 'certificado', 'completado', 
                '$codigo_unico', NOW(), 1
            )
        ";
        
        if ($conn->query($insert_documento)) {
            $documento_id = $conn->insert_id;
            echo "<p style='color: green;'>✓ Certificado creado con ID: $documento_id</p>";
            
            // Crear firmas simuladas
            $roles_firmas = [
                ['nombre' => 'Coordinador', 'orden' => 1],
                ['nombre' => 'Director de Escuela', 'orden' => 2],
                ['nombre' => 'Educación DNBC', 'orden' => 3],
                ['nombre' => 'Dirección Nacional', 'orden' => 4]
            ];
            
            foreach ($roles_firmas as $rol) {
                // Buscar un usuario con este rol
                $user_query = "
                    SELECT u.id FROM usuarios u 
                    JOIN roles r ON u.rol_id = r.id 
                    WHERE r.nombre = '{$rol['nombre']}' LIMIT 1
                ";
                $user_result = $conn->query($user_query);
                
                if ($user_result->num_rows > 0) {
                    $user = $user_result->fetch_assoc();
                    $user_id = $user['id'];
                } else {
                    $user_id = 1; // Usuario por defecto
                }
                
                $insert_firma = "
                    INSERT INTO firmas_documentos (
                        documento_id, usuario_id, fecha_firma, orden_firma, es_rechazo
                    ) VALUES (
                        $documento_id, $user_id, NOW(), {$rol['orden']}, 0
                    )
                ";
                
                if ($conn->query($insert_firma)) {
                    echo "<p style='color: green;'>✓ Firma {$rol['nombre']} agregada</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>✗ Error creando certificado</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ Certificado ya existe</p>";
    }
    
    // 6. Verificar configuración de certificados
    $check_config = $conn->query("SELECT id FROM configuracion_certificados WHERE escuela_id = $escuela_id");
    
    if ($check_config->num_rows == 0) {
        echo "<p style='color: orange;'>Creando configuración de certificados...</p>";
        
        $insert_config = "
            INSERT INTO configuracion_certificados (escuela_id) VALUES ($escuela_id)
        ";
        
        if ($conn->query($insert_config)) {
            echo "<p style='color: green;'>✓ Configuración de certificados creada</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ Configuración de certificados ya existe</p>";
    }
    
    echo "<hr>";
    echo "<h2 style='color: green;'>¡Datos de prueba creados exitosamente!</h2>";
    echo "<p><strong>Para probar:</strong></p>";
    echo "<ol>";
    echo "<li><a href='participante-login.php'>Ir al login de participantes</a></li>";
    echo "<li>Usar cédula: <strong>12345678</strong> y contraseña: <strong>123456</strong></li>";
    echo "<li>Acceder al dashboard y probar la descarga de certificados</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
