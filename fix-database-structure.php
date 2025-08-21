<?php
require_once 'config/database.php';

function fixDatabaseStructure() {
    $conn = getMySQLiConnection();
    $output = [];
    
    try {
        $output[] = "🔧 Iniciando reparación de estructura de base de datos...";
        
        // 1. Verificar y arreglar tabla matriculas
        $output[] = "📋 Verificando tabla 'matriculas'...";
        
        // Verificar si existe la tabla matriculas
        $check_matriculas = "SHOW TABLES LIKE 'matriculas'";
        $result = $conn->query($check_matriculas);
        
        if ($result->num_rows == 0) {
            $output[] = "❌ Tabla 'matriculas' no existe. Creando...";
            
            $create_matriculas = "
                CREATE TABLE matriculas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    participante_id INT NOT NULL,
                    curso_id INT NOT NULL,
                    fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    fecha_inicio DATE NULL,
                    fecha_finalizacion DATE NULL,
                    calificacion DECIMAL(5,2) NULL,
                    estado ENUM('inscrito', 'en_curso', 'completado', 'abandonado') DEFAULT 'inscrito',
                    observaciones TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";
            
            if ($conn->query($create_matriculas)) {
                $output[] = "✅ Tabla 'matriculas' creada exitosamente";
            } else {
                $output[] = "❌ Error creando tabla 'matriculas': " . $conn->error;
            }
        } else {
            $output[] = "✅ Tabla 'matriculas' existe";
            
            // Verificar columnas específicas
            $columns_to_check = [
                'fecha_inicio' => 'DATE NULL',
                'fecha_finalizacion' => 'DATE NULL',
                'calificacion' => 'DECIMAL(5,2) NULL',
                'estado' => "ENUM('inscrito', 'en_curso', 'completado', 'abandonado') DEFAULT 'inscrito'",
                'observaciones' => 'TEXT NULL'
            ];
            
            foreach ($columns_to_check as $column => $definition) {
                $check_column = "SHOW COLUMNS FROM matriculas LIKE '$column'";
                $result = $conn->query($check_column);
                
                if ($result->num_rows == 0) {
                    $output[] = "➕ Agregando columna '$column'...";
                    $add_column = "ALTER TABLE matriculas ADD COLUMN $column $definition";
                    
                    if ($conn->query($add_column)) {
                        $output[] = "✅ Columna '$column' agregada";
                    } else {
                        $output[] = "❌ Error agregando '$column': " . $conn->error;
                    }
                } else {
                    $output[] = "ℹ️ Columna '$column' ya existe";
                }
            }
        }
        
        // 2. Verificar y arreglar tabla documentos
        $output[] = "📄 Verificando tabla 'documentos'...";
        
        $check_documentos = "SHOW TABLES LIKE 'documentos'";
        $result = $conn->query($check_documentos);
        
        if ($result->num_rows == 0) {
            $output[] = "❌ Tabla 'documentos' no existe. Creando...";
            
            $create_documentos = "
                CREATE TABLE documentos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    participante_id INT NOT NULL,
                    curso_id INT NOT NULL,
                    tipo ENUM('certificado', 'diploma', 'constancia') DEFAULT 'certificado',
                    estado ENUM('pendiente', 'en_proceso', 'completado', 'rechazado') DEFAULT 'pendiente',
                    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    fecha_completado TIMESTAMP NULL,
                    generado_por INT NULL,
                    observaciones TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";
            
            if ($conn->query($create_documentos)) {
                $output[] = "✅ Tabla 'documentos' creada exitosamente";
            } else {
                $output[] = "❌ Error creando tabla 'documentos': " . $conn->error;
            }
        } else {
            $output[] = "✅ Tabla 'documentos' existe";
        }
        
        // 3. Verificar y arreglar tabla firmas_documentos
        $output[] = "✍️ Verificando tabla 'firmas_documentos'...";
        
        $check_firmas = "SHOW TABLES LIKE 'firmas_documentos'";
        $result = $conn->query($check_firmas);
        
        if ($result->num_rows == 0) {
            $output[] = "❌ Tabla 'firmas_documentos' no existe. Creando...";
            
            $create_firmas = "
                CREATE TABLE firmas_documentos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    documento_id INT NOT NULL,
                    usuario_id INT NOT NULL,
                    rol VARCHAR(50) NOT NULL,
                    firmado BOOLEAN DEFAULT FALSE,
                    fecha_firma TIMESTAMP NULL,
                    observaciones TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
            
            if ($conn->query($create_firmas)) {
                $output[] = "✅ Tabla 'firmas_documentos' creada exitosamente";
            } else {
                $output[] = "❌ Error creando tabla 'firmas_documentos': " . $conn->error;
            }
        } else {
            $output[] = "✅ Tabla 'firmas_documentos' existe";
        }
        
        // 4. Crear datos de prueba si no existen
        $output[] = "🧪 Verificando datos de prueba...";
        
        // Verificar si hay participantes
        $check_participants = "SELECT COUNT(*) as count FROM participantes";
        $result = $conn->query($check_participants);
        $participant_count = $result->fetch_assoc()['count'];
        
        if ($participant_count == 0) {
            $output[] = "👤 No hay participantes. Creando participante de prueba...";
            
            $create_participant = "
                INSERT INTO participantes (nombres, apellidos, cedula, email, telefono, password, activo)
                VALUES ('Juan Carlos', 'Pérez González', '12345678', 'juan.perez@email.com', '809-555-0123', MD5('12345678'), 1)
            ";
            
            if ($conn->query($create_participant)) {
                $output[] = "✅ Participante de prueba creado";
            } else {
                $output[] = "❌ Error creando participante: " . $conn->error;
            }
        }
        
        // Verificar si hay cursos
        $check_courses = "SELECT COUNT(*) as count FROM cursos";
        $result = $conn->query($check_courses);
        $course_count = $result->fetch_assoc()['count'];
        
        if ($course_count == 0) {
            $output[] = "📚 No hay cursos. Creando curso de prueba...";
            
            // Primero verificar si hay escuelas
            $check_schools = "SELECT COUNT(*) as count FROM escuelas";
            $result = $conn->query($check_schools);
            $school_count = $result->fetch_assoc()['count'];
            
            if ($school_count == 0) {
                $output[] = "🏫 No hay escuelas. Creando escuela de prueba...";
                
                $create_school = "
                    INSERT INTO escuelas (nombre, direccion, telefono, email, activo)
                    VALUES ('Escuela de Prueba', 'Dirección de Prueba', '809-555-0001', 'escuela@prueba.com', 1)
                ";
                
                if ($conn->query($create_school)) {
                    $output[] = "✅ Escuela de prueba creada";
                } else {
                    $output[] = "❌ Error creando escuela: " . $conn->error;
                }
            }
            
            // Crear curso
            $create_course = "
                INSERT INTO cursos (nombre, descripcion, duracion_horas, escuela_id, activo)
                SELECT 'Curso de Capacitación Básica', 'Curso de prueba para el sistema', 40, id, 1
                FROM escuelas LIMIT 1
            ";
            
            if ($conn->query($create_course)) {
                $output[] = "✅ Curso de prueba creado";
            } else {
                $output[] = "❌ Error creando curso: " . $conn->error;
            }
        }
        
        // Crear matrícula de prueba
        $check_matriculas_data = "SELECT COUNT(*) as count FROM matriculas";
        $result = $conn->query($check_matriculas_data);
        $matricula_count = $result->fetch_assoc()['count'];
        
        if ($matricula_count == 0) {
            $output[] = "📝 No hay matrículas. Creando matrícula de prueba...";
            
            $create_matricula = "
                INSERT INTO matriculas (participante_id, curso_id, fecha_inicio, fecha_finalizacion, calificacion, estado)
                SELECT 
                    p.id as participante_id,
                    c.id as curso_id,
                    DATE_SUB(CURDATE(), INTERVAL 30 DAY) as fecha_inicio,
                    DATE_SUB(CURDATE(), INTERVAL 5 DAY) as fecha_finalizacion,
                    85.5 as calificacion,
                    'completado' as estado
                FROM participantes p
                CROSS JOIN cursos c
                WHERE p.cedula = '12345678'
                LIMIT 1
            ";
            
            if ($conn->query($create_matricula)) {
                $output[] = "✅ Matrícula de prueba creada";
                
                // Crear documento de prueba
                $create_document = "
                    INSERT INTO documentos (participante_id, curso_id, tipo, estado, fecha_completado)
                    SELECT 
                        m.participante_id,
                        m.curso_id,
                        'certificado' as tipo,
                        'completado' as estado,
                        NOW() as fecha_completado
                    FROM matriculas m
                    WHERE m.calificacion >= 70
                    LIMIT 1
                ";
                
                if ($conn->query($create_document)) {
                    $output[] = "✅ Documento de prueba creado";
                } else {
                    $output[] = "❌ Error creando documento: " . $conn->error;
                }
            } else {
                $output[] = "❌ Error creando matrícula: " . $conn->error;
            }
        }
        
        // 5. Mostrar estadísticas finales
        $output[] = "📊 Estadísticas finales:";
        
        $stats_queries = [
            'Participantes' => "SELECT COUNT(*) as count FROM participantes",
            'Cursos' => "SELECT COUNT(*) as count FROM cursos",
            'Matrículas' => "SELECT COUNT(*) as count FROM matriculas",
            'Documentos' => "SELECT COUNT(*) as count FROM documentos"
        ];
        
        foreach ($stats_queries as $name => $query) {
            $result = $conn->query($query);
            if ($result) {
                $count = $result->fetch_assoc()['count'];
                $output[] = "   • $name: $count";
            }
        }
        
        $output[] = "🎉 ¡Estructura de base de datos reparada exitosamente!";
        
    } catch (Exception $e) {
        $output[] = "❌ Error durante la reparación: " . $e->getMessage();
    }
    
    return $output;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reparación de Estructura de Base de Datos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .output-line {
            font-family: 'Courier New', monospace;
            margin: 2px 0;
            padding: 2px 5px;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🔧 Reparación de Estructura de Base de Datos</h4>
                    </div>
                    <div class="card-body">
                        <div class="bg-dark text-light p-3 rounded" style="max-height: 500px; overflow-y: auto;">
                            <?php
                            $results = fixDatabaseStructure();
                            foreach ($results as $line) {
                                $class = 'info';
                                if (strpos($line, '✅') !== false) $class = 'success';
                                elseif (strpos($line, '❌') !== false) $class = 'error';
                                elseif (strpos($line, '⚠️') !== false) $class = 'warning';
                                
                                echo "<div class='output-line $class'>$line</div>";
                            }
                            ?>
                        </div>
                        
                        <hr>
                        
                        <div class="alert alert-success">
                            <h5>🎯 Credenciales de Prueba:</h5>
                            <ul class="mb-0">
                                <li><strong>Usuario:</strong> <code>juan.perez@email.com</code> o <code>12345678</code></li>
                                <li><strong>Contraseña:</strong> <code>12345678</code></li>
                            </ul>
                        </div>
                        
                        <div class="mt-3">
                            <a href="participante-login.php" class="btn btn-success btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Ir al Login de Participantes
                            </a>
                            <a href="test-connection.php" class="btn btn-info ms-2">
                                <i class="fas fa-database"></i> Probar Conexión
                            </a>
                            <button onclick="location.reload()" class="btn btn-secondary ms-2">
                                <i class="fas fa-redo"></i> Ejecutar Nuevamente
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
