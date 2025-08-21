<?php
require_once 'config/database.php';

// Función para crear un participante de prueba
function createTestParticipant() {
    $conn = getMySQLiConnection();
    
    // Verificar si ya existe un participante con la cédula 12345678
    $check_sql = "SELECT id FROM participantes WHERE cedula = '12345678'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows > 0) {
        echo "<p>El participante de prueba ya existe.</p>";
        $participante_id = $result->fetch_assoc()['id'];
    } else {
        // Crear el participante
        $insert_sql = "INSERT INTO participantes (
            nombres, 
            apellidos, 
            cedula, 
            email, 
            telefono, 
            direccion, 
            fecha_nacimiento, 
            genero, 
            password, 
            activo, 
            fecha_registro
        ) VALUES (
            'Juan', 
            'Pérez', 
            '12345678', 
            'juan.perez@email.com', 
            '3001234567', 
            'Calle 123 #45-67', 
            '1990-01-15', 
            'M', 
            MD5('12345678'), 
            1, 
            NOW()
        )";
        
        if ($conn->query($insert_sql) === TRUE) {
            $participante_id = $conn->insert_id;
            echo "<p>Participante de prueba creado con éxito.</p>";
        } else {
            echo "<p>Error al crear el participante: " . $conn->error . "</p>";
            return false;
        }
    }
    
    // Verificar si ya está matriculado en algún curso
    $check_matricula = "SELECT id FROM matriculas WHERE participante_id = ?";
    $stmt = $conn->prepare($check_matricula);
    $stmt->bind_param("i", $participante_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<p>El participante ya está matriculado en cursos.</p>";
    } else {
        // Obtener algunos cursos para matricular al participante
        $cursos_sql = "SELECT id, escuela_id FROM cursos LIMIT 3";
        $cursos_result = $conn->query($cursos_sql);
        
        if ($cursos_result->num_rows > 0) {
            while ($curso = $cursos_result->fetch_assoc()) {
                // Matricular al participante en el curso
                $matricula_sql = "INSERT INTO matriculas (
                    participante_id, 
                    curso_id, 
                    fecha_inicio, 
                    fecha_finalizacion, 
                    calificacion, 
                    estado, 
                    fecha_registro
                ) VALUES (
                    ?, 
                    ?, 
                    DATE_SUB(NOW(), INTERVAL 30 DAY), 
                    NOW(), 
                    85, 
                    'completado', 
                    NOW()
                )";
                
                $stmt = $conn->prepare($matricula_sql);
                $stmt->bind_param("ii", $participante_id, $curso['id']);
                
                if ($stmt->execute()) {
                    echo "<p>Participante matriculado en curso ID: " . $curso['id'] . "</p>";
                    
                    // Generar un certificado para el participante
                    $certificado_sql = "INSERT INTO documentos (
                        tipo, 
                        participante_id, 
                        curso_id, 
                        escuela_id, 
                        fecha_generacion, 
                        fecha_inicio, 
                        fecha_fin, 
                        estado, 
                        codigo_verificacion, 
                        generado_por
                    ) VALUES (
                        'certificado', 
                        ?, 
                        ?, 
                        ?, 
                        NOW(), 
                        DATE_SUB(NOW(), INTERVAL 30 DAY), 
                        NOW(), 
                        'completado', 
                        UUID(), 
                        1
                    )";
                    
                    $stmt = $conn->prepare($certificado_sql);
                    $stmt->bind_param("iii", $participante_id, $curso['id'], $curso['escuela_id']);
                    
                    if ($stmt->execute()) {
                        $documento_id = $conn->insert_id;
                        echo "<p>Certificado generado con ID: " . $documento_id . "</p>";
                        
                        // Agregar firmas al documento
                        $usuarios_sql = "SELECT id FROM usuarios WHERE rol IN ('director', 'coordinador') LIMIT 3";
                        $usuarios_result = $conn->query($usuarios_sql);
                        
                        if ($usuarios_result->num_rows > 0) {
                            $orden = 1;
                            while ($usuario = $usuarios_result->fetch_assoc()) {
                                $firma_sql = "INSERT INTO firmas_documentos (
                                    documento_id, 
                                    usuario_id, 
                                    orden, 
                                    firmado, 
                                    fecha_firma
                                ) VALUES (
                                    ?, 
                                    ?, 
                                    ?, 
                                    1, 
                                    NOW()
                                )";
                                
                                $stmt = $conn->prepare($firma_sql);
                                $stmt->bind_param("iii", $documento_id, $usuario['id'], $orden);
                                
                                if ($stmt->execute()) {
                                    echo "<p>Firma agregada al certificado.</p>";
                                } else {
                                    echo "<p>Error al agregar firma: " . $stmt->error . "</p>";
                                }
                                
                                $orden++;
                            }
                        } else {
                            echo "<p>No se encontraron usuarios para firmar el certificado.</p>";
                        }
                    } else {
                        echo "<p>Error al generar certificado: " . $stmt->error . "</p>";
                    }
                } else {
                    echo "<p>Error al matricular al participante: " . $stmt->error . "</p>";
                }
            }
        } else {
            echo "<p>No se encontraron cursos para matricular al participante.</p>";
        }
    }
    
    echo "<h3>Datos de acceso:</h3>";
    echo "<p>Usuario: juan.perez@email.com o 12345678</p>";
    echo "<p>Contraseña: 12345678</p>";
    echo "<p><a href='participante-login.php' class='btn btn-primary'>Ir al Login</a></p>";
    
    return true;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Participante de Prueba</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Crear Participante de Prueba</h4>
                    </div>
                    <div class="card-body">
                        <?php createTestParticipant(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
