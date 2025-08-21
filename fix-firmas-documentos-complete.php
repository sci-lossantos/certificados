<?php
require_once 'config/database.php';

function fixFirmasDocumentosComplete() {
    $conn = getMySQLiConnection();
    $output = [];
    
    try {
        $output[] = "🔧 Reparación completa de tabla firmas_documentos...";
        
        // 1. Verificar estructura actual
        $output[] = "📋 Verificando estructura actual...";
        
        $describe_query = "DESCRIBE firmas_documentos";
        $result = $conn->query($describe_query);
        
        if ($result) {
            $existing_columns = [];
            while ($row = $result->fetch_assoc()) {
                $existing_columns[] = $row['Field'];
            }
            $output[] = "ℹ️ Columnas existentes: " . implode(', ', $existing_columns);
        }
        
        // 2. Definir columnas necesarias
        $required_columns = [
            'accion' => "ENUM('firma', 'revision') DEFAULT 'firma'",
            'es_rechazo' => "TINYINT(1) DEFAULT 0",
            'motivo_rechazo' => "TEXT NULL",
            'fecha_rechazo' => "DATETIME NULL"
        ];
        
        // 3. Agregar columnas faltantes
        foreach ($required_columns as $column => $definition) {
            if (!in_array($column, $existing_columns)) {
                $output[] = "➕ Agregando columna '$column'...";
                
                $alter_query = "ALTER TABLE firmas_documentos ADD COLUMN $column $definition";
                
                if ($conn->query($alter_query)) {
                    $output[] = "✅ Columna '$column' agregada exitosamente";
                } else {
                    $output[] = "❌ Error agregando '$column': " . $conn->error;
                }
            } else {
                $output[] = "ℹ️ Columna '$column' ya existe";
            }
        }
        
        // 4. Actualizar registros existentes
        $output[] = "🔄 Actualizando registros existentes...";
        
        $update_query = "
            UPDATE firmas_documentos 
            SET 
                accion = COALESCE(accion, 'firma'),
                es_rechazo = COALESCE(es_rechazo, 0)
            WHERE accion IS NULL OR es_rechazo IS NULL
        ";
        
        if ($conn->query($update_query)) {
            $affected = $conn->affected_rows;
            $output[] = "✅ $affected registros actualizados";
        } else {
            $output[] = "❌ Error actualizando registros: " . $conn->error;
        }
        
        // 5. Crear índices
        $output[] = "📊 Creando índices...";
        
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_documento_id ON firmas_documentos(documento_id)",
            "CREATE INDEX IF NOT EXISTS idx_usuario_id ON firmas_documentos(usuario_id)",
            "CREATE INDEX IF NOT EXISTS idx_es_rechazo ON firmas_documentos(es_rechazo)",
            "CREATE INDEX IF NOT EXISTS idx_firmado ON firmas_documentos(firmado)"
        ];
        
        foreach ($indexes as $index_query) {
            if ($conn->query($index_query)) {
                $output[] = "✅ Índice creado";
            } else {
                $output[] = "⚠️ Índice ya existe o error: " . $conn->error;
            }
        }
        
        // 6. Verificar que hay documentos para crear firmas
        $check_docs = "SELECT COUNT(*) as count FROM documentos WHERE estado = 'completado'";
        $result = $conn->query($check_docs);
        $doc_count = $result ? $result->fetch_assoc()['count'] : 0;
        
        $output[] = "📄 Documentos completados encontrados: $doc_count";
        
        // 7. Crear firmas de prueba si no existen
        if ($doc_count > 0) {
            $output[] = "✍️ Creando firmas de prueba...";
            
            $insert_firmas = "
                INSERT IGNORE INTO firmas_documentos (documento_id, usuario_id, rol, accion, firmado, fecha_firma, es_rechazo)
                SELECT 
                    d.id as documento_id,
                    1 as usuario_id,
                    'coordinador' as rol,
                    'firma' as accion,
                    TRUE as firmado,
                    NOW() as fecha_firma,
                    0 as es_rechazo
                FROM documentos d
                WHERE d.estado = 'completado'
                AND NOT EXISTS (
                    SELECT 1 FROM firmas_documentos fd 
                    WHERE fd.documento_id = d.id
                )
            ";
            
            if ($conn->query($insert_firmas)) {
                $affected = $conn->affected_rows;
                $output[] = "✅ $affected firmas de prueba creadas";
            } else {
                $output[] = "❌ Error creando firmas: " . $conn->error;
            }
        }
        
        // 8. Verificar estructura final
        $output[] = "🔍 Verificando estructura final...";
        
        $final_check = "DESCRIBE firmas_documentos";
        $result = $conn->query($final_check);
        
        if ($result) {
            $output[] = "📋 Estructura final de firmas_documentos:";
            while ($row = $result->fetch_assoc()) {
                $output[] = "   • {$row['Field']} ({$row['Type']})";
            }
        }
        
        // 9. Estadísticas finales
        $count_query = "SELECT COUNT(*) as count FROM firmas_documentos";
        $result = $conn->query($count_query);
        $total_firmas = $result ? $result->fetch_assoc()['count'] : 0;
        
        $output[] = "📊 Total de firmas en la tabla: $total_firmas";
        
        // 10. Probar consulta problemática
        $output[] = "🧪 Probando consulta que causaba error...";
        
        $test_query = "
            SELECT fd.accion, r.nombre as rol_nombre
            FROM firmas_documentos fd
            JOIN usuarios u ON fd.usuario_id = u.id
            JOIN roles r ON u.rol_id = r.id
            WHERE fd.documento_id = 1 AND fd.es_rechazo = 0
            LIMIT 1
        ";
        
        $result = $conn->query($test_query);
        if ($result !== false) {
            $output[] = "✅ Consulta de prueba ejecutada exitosamente";
        } else {
            $output[] = "⚠️ Error en consulta de prueba: " . $conn->error;
        }
        
        $output[] = "🎉 ¡Tabla firmas_documentos reparada completamente!";
        
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
    <title>Reparación Completa de Firmas</title>
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
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-signature me-2"></i>Reparación Completa de Firmas</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Este script agrega todas las columnas necesarias a la tabla firmas_documentos.
                        </div>
                        
                        <div class="bg-dark text-light p-3 rounded" style="max-height: 600px; overflow-y: auto;">
                            <?php
                            $results = fixFirmasDocumentosComplete();
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
                            <a href="documentos.php" class="btn btn-success btn-lg">
                                <i class="fas fa-file-alt me-2"></i>Ir a Documentos
                            </a>
                            <a href="participante-login.php" class="btn btn-info ms-2">
                                <i class="fas fa-sign-in-alt me-2"></i>Login Participantes
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
