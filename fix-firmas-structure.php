<?php
require_once 'config/database.php';

function fixFirmasStructure() {
    $conn = getMySQLiConnection();
    $output = [];
    
    try {
        $output[] = "🔧 Reparando estructura de tabla firmas_documentos...";
        
        // Verificar si existe la columna 'firmado'
        $check_column = "SHOW COLUMNS FROM firmas_documentos LIKE 'firmado'";
        $result = $conn->query($check_column);
        
        if ($result->num_rows == 0) {
            $output[] = "➕ Agregando columna 'firmado'...";
            
            $add_column = "ALTER TABLE firmas_documentos ADD COLUMN firmado BOOLEAN DEFAULT FALSE AFTER rol";
            
            if ($conn->query($add_column)) {
                $output[] = "✅ Columna 'firmado' agregada exitosamente";
                
                // Actualizar registros existentes
                $update_existing = "UPDATE firmas_documentos SET firmado = TRUE WHERE fecha_firma IS NOT NULL";
                
                if ($conn->query($update_existing)) {
                    $output[] = "✅ Registros existentes actualizados";
                } else {
                    $output[] = "⚠️ Error actualizando registros: " . $conn->error;
                }
            } else {
                $output[] = "❌ Error agregando columna: " . $conn->error;
            }
        } else {
            $output[] = "ℹ️ Columna 'firmado' ya existe";
        }
        
        // Crear algunas firmas de prueba si no existen
        $check_firmas = "SELECT COUNT(*) as count FROM firmas_documentos";
        $result = $conn->query($check_firmas);
        $firma_count = $result->fetch_assoc()['count'];
        
        if ($firma_count == 0) {
            $output[] = "📝 Creando firmas de prueba...";
            
            // Crear firmas para documentos existentes
            $create_firmas = "
                INSERT INTO firmas_documentos (documento_id, usuario_id, rol, firmado, fecha_firma)
                SELECT 
                    d.id as documento_id,
                    1 as usuario_id,
                    'coordinador' as rol,
                    TRUE as firmado,
                    NOW() as fecha_firma
                FROM documentos d
                WHERE d.estado = 'completado'
                LIMIT 5
            ";
            
            if ($conn->query($create_firmas)) {
                $output[] = "✅ Firmas de prueba creadas";
            } else {
                $output[] = "⚠️ Error creando firmas: " . $conn->error;
            }
        }
        
        // Mostrar estadísticas
        $output[] = "📊 Estadísticas de firmas:";
        
        $stats_query = "
            SELECT 
                COUNT(*) as total_firmas,
                COUNT(CASE WHEN firmado = TRUE THEN 1 END) as firmas_completadas,
                COUNT(CASE WHEN firmado = FALSE THEN 1 END) as firmas_pendientes
            FROM firmas_documentos
        ";
        
        $result = $conn->query($stats_query);
        if ($result) {
            $stats = $result->fetch_assoc();
            $output[] = "   • Total de firmas: " . $stats['total_firmas'];
            $output[] = "   • Firmas completadas: " . $stats['firmas_completadas'];
            $output[] = "   • Firmas pendientes: " . $stats['firmas_pendientes'];
        }
        
        $output[] = "🎉 ¡Estructura de firmas reparada exitosamente!";
        
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
    <title>Reparación de Estructura de Firmas</title>
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
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">🔧 Reparación de Estructura de Firmas</h4>
                    </div>
                    <div class="card-body">
                        <div class="bg-dark text-light p-3 rounded" style="max-height: 400px; overflow-y: auto;">
                            <?php
                            $results = fixFirmasStructure();
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
