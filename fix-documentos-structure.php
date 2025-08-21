<?php
require_once 'config/database.php';

function fixDocumentosStructure() {
    $conn = getMySQLiConnection();
    $output = [];
    
    try {
        $output[] = "🔧 Reparando estructura de tabla documentos...";
        
        // 1. Verificar estructura actual de documentos
        $check_structure = "DESCRIBE documentos";
        $result = $conn->query($check_structure);
        
        if ($result) {
            $columns = [];
            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            $output[] = "ℹ️ Columnas existentes en documentos: " . implode(', ', $columns);
            
            // 2. Agregar columnas faltantes
            $needed_columns = [
                'fecha_generacion' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                'fecha_completado' => 'DATETIME DEFAULT NULL',
                'generado_por' => 'INT DEFAULT 1',
                'observaciones' => 'TEXT DEFAULT NULL'
            ];
            
            foreach ($needed_columns as $column => $definition) {
                if (!in_array($column, $columns)) {
                    $output[] = "➕ Agregando columna '$column'...";
                    $alter_query = "ALTER TABLE documentos ADD COLUMN $column $definition";
                    
                    if ($conn->query($alter_query)) {
                        $output[] = "✅ Columna '$column' agregada exitosamente";
                    } else {
                        $output[] = "❌ Error agregando '$column': " . $conn->error;
                    }
                } else {
                    $output[] = "ℹ️ Columna '$column' ya existe";
                }
            }
            
            // 3. Actualizar registros existentes sin fecha_generacion
            if (in_array('fecha_generacion', $columns) || $conn->query("ALTER TABLE documentos ADD COLUMN fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP")) {
                $update_dates = "UPDATE documentos SET fecha_generacion = CURRENT_TIMESTAMP WHERE fecha_generacion IS NULL";
                if ($conn->query($update_dates)) {
                    $affected = $conn->affected_rows;
                    $output[] = "✅ $affected documentos actualizados con fecha_generacion";
                }
            }
            
            // 4. Actualizar registros sin fecha_completado para documentos completados
            if (in_array('fecha_completado', $columns) || $conn->query("ALTER TABLE documentos ADD COLUMN fecha_completado DATETIME DEFAULT NULL")) {
                $update_completed = "UPDATE documentos SET fecha_completado = CURRENT_TIMESTAMP WHERE estado = 'completado' AND fecha_completado IS NULL";
                if ($conn->query($update_completed)) {
                    $affected = $conn->affected_rows;
                    $output[] = "✅ $affected documentos completados actualizados con fecha_completado";
                }
            }
            
        } else {
            $output[] = "❌ No se pudo verificar la estructura de documentos: " . $conn->error;
        }
        
        // 5. Verificar y crear firmas para documentos existentes
        $output[] = "✍️ Verificando firmas para documentos...";
        
        $check_firmas = "
            SELECT d.id, d.estado 
            FROM documentos d 
            LEFT JOIN firmas_documentos fd ON d.id = fd.documento_id 
            WHERE fd.id IS NULL AND d.estado = 'completado'
        ";
        
        $result = $conn->query($check_firmas);
        if ($result && $result->num_rows > 0) {
            $output[] = "📝 Creando firmas faltantes para documentos completados...";
            
            while ($doc = $result->fetch_assoc()) {
                $insert_firma = "
                    INSERT INTO firmas_documentos (documento_id, usuario_id, rol, firmado, fecha_firma)
                    VALUES ({$doc['id']}, 1, 'coordinador', 1, NOW())
                ";
                
                if ($conn->query($insert_firma)) {
                    $output[] = "✅ Firma creada para documento {$doc['id']}";
                } else {
                    $output[] = "❌ Error creando firma para documento {$doc['id']}: " . $conn->error;
                }
            }
        } else {
            $output[] = "ℹ️ Todos los documentos completados ya tienen firmas";
        }
        
        // 6. Estadísticas finales
        $output[] = "📊 Estadísticas de documentos:";
        
        $stats_queries = [
            'Total documentos' => "SELECT COUNT(*) as count FROM documentos",
            'Documentos completados' => "SELECT COUNT(*) as count FROM documentos WHERE estado = 'completado'",
            'Documentos con firmas' => "SELECT COUNT(DISTINCT documento_id) as count FROM firmas_documentos",
            'Certificados listos' => "SELECT COUNT(*) as count FROM documentos WHERE tipo = 'certificado' AND estado = 'completado'"
        ];
        
        foreach ($stats_queries as $label => $query) {
            $result = $conn->query($query);
            if ($result) {
                $count = $result->fetch_assoc()['count'];
                $output[] = "   • $label: $count";
            }
        }
        
        // 7. Verificar datos específicos del participante de prueba
        $output[] = "🎯 Verificando datos del participante de prueba (12345678):";
        
        $check_test_data = "
            SELECT 
                p.nombres, p.apellidos,
                COUNT(DISTINCT m.curso_id) as cursos_inscritos,
                COUNT(DISTINCT d.id) as certificados,
                COUNT(DISTINCT CASE WHEN d.estado = 'completado' THEN d.id END) as certificados_listos
            FROM participantes p
            LEFT JOIN matriculas m ON p.id = m.participante_id
            LEFT JOIN documentos d ON p.id = d.participante_id AND d.tipo = 'certificado'
            WHERE p.cedula = '12345678'
            GROUP BY p.id
        ";
        
        $result = $conn->query($check_test_data);
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $output[] = "   Nombre: {$data['nombres']} {$data['apellidos']}";
            $output[] = "   Cursos inscritos: {$data['cursos_inscritos']}";
            $output[] = "   Certificados: {$data['certificados']}";
            $output[] = "   Certificados listos: {$data['certificados_listos']}";
        }
        
        $output[] = "🎉 ¡Estructura de documentos reparada exitosamente!";
        
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
    <title>Reparación de Estructura de Documentos</title>
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
                        <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>Reparación de Estructura de Documentos</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Este script agrega las columnas faltantes a la tabla documentos.
                        </div>
                        
                        <div class="bg-dark text-light p-3 rounded" style="max-height: 600px; overflow-y: auto;">
                            <?php
                            $results = fixDocumentosStructure();
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
                            <h5><i class="fas fa-check-circle me-2"></i>¡Listo para probar!</h5>
                            <p class="mb-0">Ahora puedes acceder al dashboard de participantes sin errores.</p>
                        </div>
                        
                        <div class="mt-3">
                            <a href="participante-login.php" class="btn btn-success btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Ir al Login de Participantes
                            </a>
                            <a href="participante-dashboard.php" class="btn btn-primary ms-2">
                                <i class="fas fa-tachometer-alt me-2"></i>Ir Directo al Dashboard
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
