<?php
require_once 'config/database.php';

function fixDatabaseFinal() {
    $conn = getMySQLiConnection();
    $output = [];
    
    try {
        $output[] = "🔧 Reparación final de la base de datos...";
        
        // 1. Verificar estructura actual de usuarios
        $output[] = "👥 Verificando estructura de tabla usuarios...";
        
        $check_usuarios_structure = "DESCRIBE usuarios";
        $result = $conn->query($check_usuarios_structure);
        
        if ($result) {
            $columns = [];
            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            $output[] = "ℹ️ Columnas existentes en usuarios: " . implode(', ', $columns);
            
            // Verificar si necesitamos agregar columnas
            if (!in_array('nombres', $columns) && in_array('nombre', $columns)) {
                $output[] = "🔄 La tabla usa 'nombre' en lugar de 'nombres'";
            }
        }
        
        // 2. Insertar usuario administrador usando la estructura existente
        $check_admin = "SELECT COUNT(*) as count FROM usuarios WHERE email LIKE '%admin%' OR cedula = '00000000'";
        $result = $conn->query($check_admin);
        $admin_count = $result ? $result->fetch_assoc()['count'] : 0;
        
        if ($admin_count == 0) {
            $output[] = "👤 Creando usuario administrador...";
            
            // Intentar con diferentes estructuras
            $insert_queries = [
                "INSERT INTO usuarios (nombres, apellidos, cedula, email, password_hash, rol_id, activo) 
                 VALUES ('Admin', 'Sistema', '00000000', 'admin@dnbc.com', MD5('admin123'), 1, 1)",
                
                "INSERT INTO usuarios (nombre, email, password, rol, activo) 
                 VALUES ('Admin Sistema', 'admin@dnbc.com', MD5('admin123'), 'admin', 1)",
                
                "INSERT INTO usuarios (nombres, apellidos, email, password_hash, activo) 
                 VALUES ('Admin', 'Sistema', 'admin@dnbc.com', MD5('admin123'), 1)"
            ];
            
            $success = false;
            foreach ($insert_queries as $query) {
                if ($conn->query($query)) {
                    $output[] = "✅ Usuario administrador creado exitosamente";
                    $success = true;
                    break;
                } else {
                    $output[] = "⚠️ Intento fallido: " . $conn->error;
                }
            }
            
            if (!$success) {
                $output[] = "❌ No se pudo crear usuario administrador";
            }
        } else {
            $output[] = "ℹ️ Usuario administrador ya existe";
        }
        
        // 3. Verificar y arreglar tabla participantes
        $output[] = "👨‍🎓 Verificando tabla participantes...";
        
        $check_participantes = "DESCRIBE participantes";
        $result = $conn->query($check_participantes);
        
        if ($result) {
            $columns = [];
            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            
            // Agregar columnas faltantes
            $needed_columns = [
                'password' => 'VARCHAR(255) DEFAULT NULL',
                'activo' => 'BOOLEAN DEFAULT TRUE',
                'ultimo_acceso' => 'DATETIME DEFAULT NULL'
            ];
            
            foreach ($needed_columns as $column => $definition) {
                if (!in_array($column, $columns)) {
                    $output[] = "➕ Agregando columna '$column' a participantes...";
                    $alter_query = "ALTER TABLE participantes ADD COLUMN $column $definition";
                    
                    if ($conn->query($alter_query)) {
                        $output[] = "✅ Columna '$column' agregada";
                    } else {
                        $output[] = "❌ Error agregando '$column': " . $conn->error;
                    }
                } else {
                    $output[] = "ℹ️ Columna '$column' ya existe";
                }
            }
        }
        
        // 4. Actualizar contraseñas de participantes
        $output[] = "🔐 Configurando contraseñas de participantes...";
        
        $update_passwords = "UPDATE participantes SET password = MD5(cedula), activo = 1 WHERE password IS NULL OR password = ''";
        
        if ($conn->query($update_passwords)) {
            $affected = $conn->affected_rows;
            $output[] = "✅ $affected participantes actualizados con contraseñas";
        } else {
            $output[] = "❌ Error actualizando contraseñas: " . $conn->error;
        }
        
        // 5. Verificar tabla matriculas
        $output[] = "📋 Verificando tabla matriculas...";
        
        $check_matriculas = "DESCRIBE matriculas";
        $result = $conn->query($check_matriculas);
        
        if ($result) {
            $columns = [];
            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            
            $needed_columns = [
                'fecha_inicio' => 'DATE DEFAULT NULL',
                'fecha_finalizacion' => 'DATE DEFAULT NULL',
                'calificacion' => 'DECIMAL(5,2) DEFAULT NULL',
                'estado' => "ENUM('inscrito', 'en_curso', 'completado', 'retirado') DEFAULT 'inscrito'",
                'observaciones' => 'TEXT DEFAULT NULL'
            ];
            
            foreach ($needed_columns as $column => $definition) {
                if (!in_array($column, $columns)) {
                    $output[] = "➕ Agregando columna '$column' a matriculas...";
                    $alter_query = "ALTER TABLE matriculas ADD COLUMN $column $definition";
                    
                    if ($conn->query($alter_query)) {
                        $output[] = "✅ Columna '$column' agregada";
                    } else {
                        $output[] = "❌ Error agregando '$column': " . $conn->error;
                    }
                }
            }
        }
        
        // 6. Crear datos de prueba en matriculas si no existen
        $check_matriculas_data = "SELECT COUNT(*) as count FROM matriculas";
        $result = $conn->query($check_matriculas_data);
        $matricula_count = $result ? $result->fetch_assoc()['count'] : 0;
        
        if ($matricula_count == 0) {
            $output[] = "📝 Creando matrículas de prueba...";
            
            // Obtener IDs de participantes y cursos
            $get_participants = "SELECT id FROM participantes LIMIT 3";
            $get_courses = "SELECT id FROM cursos LIMIT 1";
            
            $participants = $conn->query($get_participants);
            $courses = $conn->query($get_courses);
            
            if ($participants && $courses && $participants->num_rows > 0 && $courses->num_rows > 0) {
                $course_id = $courses->fetch_assoc()['id'];
                
                while ($participant = $participants->fetch_assoc()) {
                    $insert_matricula = "
                        INSERT INTO matriculas (participante_id, curso_id, fecha_inicio, fecha_finalizacion, calificacion, estado)
                        VALUES (
                            {$participant['id']}, 
                            $course_id, 
                            DATE_SUB(NOW(), INTERVAL 30 DAY), 
                            DATE_SUB(NOW(), INTERVAL 1 DAY), 
                            85.5, 
                            'completado'
                        )
                    ";
                    
                    if ($conn->query($insert_matricula)) {
                        $output[] = "✅ Matrícula creada para participante {$participant['id']}";
                    }
                }
            }
        }
        
        // 7. Verificar tabla documentos
        $output[] = "📄 Verificando tabla documentos...";
        
        $check_documentos = "SHOW TABLES LIKE 'documentos'";
        $result = $conn->query($check_documentos);
        
        if ($result->num_rows == 0) {
            $output[] = "📄 Creando tabla documentos...";
            
            $create_documentos = "
                CREATE TABLE documentos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    participante_id INT NOT NULL,
                    curso_id INT NOT NULL,
                    tipo ENUM('certificado', 'acta', 'informe', 'directorio') DEFAULT 'certificado',
                    estado ENUM('pendiente', 'en_proceso', 'completado', 'rechazado') DEFAULT 'completado',
                    fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                    fecha_completado DATETIME DEFAULT CURRENT_TIMESTAMP,
                    generado_por INT DEFAULT 1,
                    observaciones TEXT NULL,
                    INDEX idx_participante (participante_id),
                    INDEX idx_curso (curso_id),
                    INDEX idx_tipo (tipo),
                    INDEX idx_estado (estado)
                )
            ";
            
            if ($conn->query($create_documentos)) {
                $output[] = "✅ Tabla documentos creada";
            } else {
                $output[] = "❌ Error creando documentos: " . $conn->error;
            }
        }
        
        // 8. Crear documentos de prueba
        $check_docs = "SELECT COUNT(*) as count FROM documentos";
        $result = $conn->query($check_docs);
        $doc_count = $result ? $result->fetch_assoc()['count'] : 0;
        
        if ($doc_count == 0) {
            $output[] = "📋 Creando documentos de prueba...";
            
            $insert_docs = "
                INSERT INTO documentos (participante_id, curso_id, tipo, estado, fecha_completado)
                SELECT 
                    m.participante_id,
                    m.curso_id,
                    'certificado' as tipo,
                    'completado' as estado,
                    NOW() as fecha_completado
                FROM matriculas m
                WHERE m.calificacion >= 70
            ";
            
            if ($conn->query($insert_docs)) {
                $affected = $conn->affected_rows;
                $output[] = "✅ $affected documentos de prueba creados";
            }
        }
        
        // 9. Recrear tabla firmas_documentos
        $output[] = "✍️ Recreando tabla firmas_documentos...";
        
        $drop_firmas = "DROP TABLE IF EXISTS firmas_documentos";
        $conn->query($drop_firmas);
        
        $create_firmas = "
            CREATE TABLE firmas_documentos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                documento_id INT NOT NULL,
                usuario_id INT DEFAULT 1,
                rol VARCHAR(50) DEFAULT 'coordinador',
                firmado BOOLEAN DEFAULT TRUE,
                fecha_firma DATETIME DEFAULT CURRENT_TIMESTAMP,
                observaciones TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_documento (documento_id),
                INDEX idx_firmado (firmado)
            )
        ";
        
        if ($conn->query($create_firmas)) {
            $output[] = "✅ Tabla firmas_documentos creada";
            
            // Crear firmas de prueba
            $insert_firmas = "
                INSERT INTO firmas_documentos (documento_id, usuario_id, rol, firmado, fecha_firma)
                SELECT 
                    d.id as documento_id,
                    1 as usuario_id,
                    'coordinador' as rol,
                    TRUE as firmado,
                    NOW() as fecha_firma
                FROM documentos d
                WHERE d.estado = 'completado'
            ";
            
            if ($conn->query($insert_firmas)) {
                $affected = $conn->affected_rows;
                $output[] = "✅ $affected firmas de prueba creadas";
            }
        }
        
        // 10. Estadísticas finales
        $output[] = "📊 Estadísticas finales:";
        
        $tables = ['usuarios', 'participantes', 'matriculas', 'documentos', 'firmas_documentos'];
        
        foreach ($tables as $table) {
            $count_query = "SELECT COUNT(*) as count FROM $table";
            $result = $conn->query($count_query);
            if ($result) {
                $count = $result->fetch_assoc()['count'];
                $output[] = "   • $table: $count registros";
            }
        }
        
        // Verificar participante de prueba específico
        $check_test_user = "SELECT nombres, apellidos, cedula, email FROM participantes WHERE cedula = '12345678' LIMIT 1";
        $result = $conn->query($check_test_user);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $output[] = "🎯 Participante de prueba encontrado:";
            $output[] = "   Nombre: {$user['nombres']} {$user['apellidos']}";
            $output[] = "   Cédula: {$user['cedula']}";
            $output[] = "   Email: {$user['email']}";
        }
        
        $output[] = "🎉 ¡Base de datos reparada exitosamente!";
        $output[] = "🔑 Credenciales de prueba:";
        $output[] = "   Usuario: juan.perez@email.com o 12345678";
        $output[] = "   Contraseña: 12345678";
        
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
    <title>Reparación Final de Base de Datos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .output-line {
            font-family: 'Courier New', monospace;
            margin: 2px 0;
            padding: 2px 5px;
            font-size: 0.9em;
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
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-database me-2"></i>Reparación Final de Base de Datos</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Este script adapta la estructura existente de la base de datos para que funcione correctamente.
                        </div>
                        
                        <div class="bg-dark text-light p-3 rounded" style="max-height: 600px; overflow-y: auto;">
                            <?php
                            $results = fixDatabaseFinal();
                            foreach ($results as $line) {
                                $class = 'info';
                                if (strpos($line, '✅') !== false) $class = 'success';
                                elseif (strpos($line, '❌') !== false) $class = 'error';
                                elseif (strpos($line, '⚠️') !== false) $class = 'warning';
                                
                                echo "<div class='output-line $class'>$line</div>";
                            }
                            ?>
                        </div>
                        
                        <div class="alert alert-success mt-3">
                            <h5><i class="fas fa-key me-2"></i>Credenciales de Prueba:</h5>
                            <ul class="mb-0">
                                <li><strong>Usuario:</strong> <code>juan.perez@email.com</code> o <code>12345678</code></li>
                                <li><strong>Contraseña:</strong> <code>12345678</code></li>
                            </ul>
                        </div>
                        
                        <div class="mt-3">
                            <a href="participante-login.php" class="btn btn-success btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Ir al Login de Participantes
                            </a>
                            <a href="test-connection.php" class="btn btn-info ms-2">
                                <i class="fas fa-database me-2"></i>Probar Conexión
                            </a>
                            <button onclick="location.reload()" class="btn btn-secondary ms-2">
                                <i class="fas fa-redo me-2"></i>Ejecutar Nuevamente
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
