<?php
require_once 'config/database.php';

function setupCompleteDatabase() {
    $conn = getMySQLiConnection();
    $output = [];
    
    try {
        $output[] = "🔧 Configuración completa de la base de datos...";
        
        // 1. Verificar y crear tabla usuarios si no existe
        $output[] = "👥 Verificando tabla usuarios...";
        $create_usuarios = "
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                rol ENUM('admin', 'coordinador', 'director', 'instructor') DEFAULT 'coordinador',
                activo BOOLEAN DEFAULT TRUE,
                escuela_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        
        if ($conn->query($create_usuarios)) {
            $output[] = "✅ Tabla usuarios verificada";
        } else {
            $output[] = "❌ Error con tabla usuarios: " . $conn->error;
        }
        
        // 2. Insertar usuario de prueba si no existe
        $check_user = "SELECT COUNT(*) as count FROM usuarios WHERE email = 'admin@dnbc.com'";
        $result = $conn->query($check_user);
        $user_count = $result->fetch_assoc()['count'];
        
        if ($user_count == 0) {
            $output[] = "👤 Creando usuario administrador...";
            $insert_user = "
                INSERT INTO usuarios (nombre, email, password, rol, activo) 
                VALUES ('Administrador DNBC', 'admin@dnbc.com', MD5('admin123'), 'admin', TRUE)
            ";
            
            if ($conn->query($insert_user)) {
                $output[] = "✅ Usuario administrador creado";
            } else {
                $output[] = "❌ Error creando usuario: " . $conn->error;
            }
        }
        
        // 3. Verificar tabla escuelas
        $output[] = "🏫 Verificando tabla escuelas...";
        $create_escuelas = "
            CREATE TABLE IF NOT EXISTS escuelas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(200) NOT NULL,
                codigo VARCHAR(50) UNIQUE,
                direccion TEXT,
                telefono VARCHAR(20),
                email VARCHAR(100),
                director_nombre VARCHAR(100),
                activa BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        if ($conn->query($create_escuelas)) {
            $output[] = "✅ Tabla escuelas verificada";
        }
        
        // 4. Insertar escuela de prueba
        $check_school = "SELECT COUNT(*) as count FROM escuelas";
        $result = $conn->query($check_school);
        $school_count = $result->fetch_assoc()['count'];
        
        if ($school_count == 0) {
            $output[] = "🏫 Creando escuela de prueba...";
            $insert_school = "
                INSERT INTO escuelas (nombre, codigo, direccion, director_nombre, activa) 
                VALUES ('Escuela de Capacitación DNBC', 'DNBC001', 'Dirección de la Escuela', 'Director de Prueba', TRUE)
            ";
            
            if ($conn->query($insert_school)) {
                $output[] = "✅ Escuela de prueba creada";
            }
        }
        
        // 5. Verificar tabla cursos
        $output[] = "📚 Verificando tabla cursos...";
        $create_cursos = "
            CREATE TABLE IF NOT EXISTS cursos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(200) NOT NULL,
                descripcion TEXT,
                duracion_horas INT DEFAULT 40,
                escuela_id INT NOT NULL,
                activo BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (escuela_id) REFERENCES escuelas(id)
            )
        ";
        
        if ($conn->query($create_cursos)) {
            $output[] = "✅ Tabla cursos verificada";
        }
        
        // 6. Insertar curso de prueba
        $check_course = "SELECT COUNT(*) as count FROM cursos";
        $result = $conn->query($check_course);
        $course_count = $result->fetch_assoc()['count'];
        
        if ($course_count == 0) {
            $output[] = "📖 Creando curso de prueba...";
            $insert_course = "
                INSERT INTO cursos (nombre, descripcion, duracion_horas, escuela_id, activo) 
                VALUES ('Curso de Capacitación Básica', 'Curso introductorio de capacitación', 40, 1, TRUE)
            ";
            
            if ($conn->query($insert_course)) {
                $output[] = "✅ Curso de prueba creado";
            }
        }
        
        // 7. Verificar tabla participantes
        $output[] = "👨‍🎓 Verificando tabla participantes...";
        $alter_participantes = "
            ALTER TABLE participantes 
            ADD COLUMN IF NOT EXISTS password VARCHAR(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS activo BOOLEAN DEFAULT TRUE,
            ADD COLUMN IF NOT EXISTS ultimo_acceso DATETIME NULL
        ";
        
        if ($conn->query($alter_participantes)) {
            $output[] = "✅ Tabla participantes actualizada";
        }
        
        // 8. Actualizar contraseñas de participantes
        $update_passwords = "
            UPDATE participantes 
            SET password = MD5(cedula), activo = TRUE 
            WHERE password IS NULL OR password = ''
        ";
        
        if ($conn->query($update_passwords)) {
            $output[] = "✅ Contraseñas de participantes actualizadas";
        }
        
        // 9. Verificar tabla matriculas
        $output[] = "📋 Verificando tabla matriculas...";
        $alter_matriculas = "
            ALTER TABLE matriculas 
            ADD COLUMN IF NOT EXISTS fecha_inicio DATE DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS fecha_finalizacion DATE DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS calificacion DECIMAL(5,2) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS estado ENUM('inscrito', 'en_curso', 'completado', 'retirado') DEFAULT 'inscrito',
            ADD COLUMN IF NOT EXISTS observaciones TEXT DEFAULT NULL
        ";
        
        if ($conn->query($alter_matriculas)) {
            $output[] = "✅ Tabla matriculas actualizada";
        }
        
        // 10. Crear matrículas de prueba
        $check_matriculas = "SELECT COUNT(*) as count FROM matriculas";
        $result = $conn->query($check_matriculas);
        $matricula_count = $result->fetch_assoc()['count'];
        
        if ($matricula_count == 0) {
            $output[] = "📝 Creando matrículas de prueba...";
            $insert_matriculas = "
                INSERT INTO matriculas (participante_id, curso_id, fecha_inicio, fecha_finalizacion, calificacion, estado)
                SELECT 
                    p.id as participante_id,
                    1 as curso_id,
                    DATE_SUB(NOW(), INTERVAL 30 DAY) as fecha_inicio,
                    DATE_SUB(NOW(), INTERVAL 1 DAY) as fecha_finalizacion,
                    85.5 as calificacion,
                    'completado' as estado
                FROM participantes p
                LIMIT 5
            ";
            
            if ($conn->query($insert_matriculas)) {
                $output[] = "✅ Matrículas de prueba creadas";
            }
        }
        
        // 11. Verificar tabla documentos
        $output[] = "📄 Verificando tabla documentos...";
        $create_documentos = "
            CREATE TABLE IF NOT EXISTS documentos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                participante_id INT NOT NULL,
                curso_id INT NOT NULL,
                tipo ENUM('certificado', 'acta', 'informe', 'directorio') DEFAULT 'certificado',
                estado ENUM('pendiente', 'en_proceso', 'completado', 'rechazado') DEFAULT 'pendiente',
                fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                fecha_completado DATETIME NULL,
                generado_por INT NULL,
                observaciones TEXT NULL,
                FOREIGN KEY (participante_id) REFERENCES participantes(id),
                FOREIGN KEY (curso_id) REFERENCES cursos(id),
                FOREIGN KEY (generado_por) REFERENCES usuarios(id)
            )
        ";
        
        if ($conn->query($create_documentos)) {
            $output[] = "✅ Tabla documentos verificada";
        }
        
        // 12. Crear documentos de prueba
        $check_docs = "SELECT COUNT(*) as count FROM documentos";
        $result = $conn->query($check_docs);
        $doc_count = $result->fetch_assoc()['count'];
        
        if ($doc_count == 0) {
            $output[] = "📋 Creando documentos de prueba...";
            $insert_docs = "
                INSERT INTO documentos (participante_id, curso_id, tipo, estado, fecha_completado, generado_por)
                SELECT 
                    m.participante_id,
                    m.curso_id,
                    'certificado' as tipo,
                    'completado' as estado,
                    NOW() as fecha_completado,
                    1 as generado_por
                FROM matriculas m
                WHERE m.estado = 'completado'
            ";
            
            if ($conn->query($insert_docs)) {
                $output[] = "✅ Documentos de prueba creados";
            }
        }
        
        // 13. Crear tabla firmas_documentos
        $output[] = "✍️ Creando tabla firmas_documentos...";
        $drop_firmas = "DROP TABLE IF EXISTS firmas_documentos";
        $conn->query($drop_firmas);
        
        $create_firmas = "
            CREATE TABLE firmas_documentos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                documento_id INT NOT NULL,
                usuario_id INT NOT NULL,
                rol VARCHAR(50) NOT NULL DEFAULT 'coordinador',
                firmado BOOLEAN DEFAULT FALSE,
                fecha_firma DATETIME NULL,
                observaciones TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE CASCADE,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                
                INDEX idx_documento_id (documento_id),
                INDEX idx_usuario_id (usuario_id),
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
                $output[] = "✅ Firmas de prueba creadas";
            }
        } else {
            $output[] = "❌ Error creando tabla firmas: " . $conn->error;
        }
        
        // Estadísticas finales
        $output[] = "📊 Estadísticas finales:";
        
        $tables = ['usuarios', 'escuelas', 'cursos', 'participantes', 'matriculas', 'documentos', 'firmas_documentos'];
        
        foreach ($tables as $table) {
            $count_query = "SELECT COUNT(*) as count FROM $table";
            $result = $conn->query($count_query);
            if ($result) {
                $count = $result->fetch_assoc()['count'];
                $output[] = "   • $table: $count registros";
            }
        }
        
        $output[] = "🎉 ¡Base de datos configurada completamente!";
        $output[] = "🎯 Credenciales de Prueba:";
        $output[] = "   Usuario: juan.perez@email.com o 12345678";
        $output[] = "   Contraseña: 12345678";
        
    } catch (Exception $e) {
        $output[] = "❌ Error durante la configuración: " . $e->getMessage();
    }
    
    return $output;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración Completa de Base de Datos</title>
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
                        <h4 class="mb-0">🔧 Configuración Completa de Base de Datos</h4>
                    </div>
                    <div class="card-body">
                        <div class="bg-dark text-light p-3 rounded" style="max-height: 500px; overflow-y: auto;">
                            <?php
                            $results = setupCompleteDatabase();
                            foreach ($results as $line) {
                                $class = 'info';
                                if (strpos($line, '✅') !== false) $class = 'success';
                                elseif (strpos($line, '❌') !== false) $class = 'error';
                                elseif (strpos($line, '⚠️') !== false) $class = 'warning';
                                
                                echo "<div class='output-line $class'>$line</div>";
                            }
                            ?>
                        </div>
                        
                        <div class="mt-3">
                            <a href="participante-login.php" class="btn btn-success">
                                <i class="fas fa-sign-in-alt"></i> Ir al Login de Participantes
                            </a>
                            <a href="login.php" class="btn btn-primary ms-2">
                                <i class="fas fa-user-shield"></i> Login Administrativo
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
