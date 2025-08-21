<?php
require_once 'config/database.php';

function fixEsRechazoColumn() {
    $conn = getMySQLiConnection();
    $output = [];
    
    try {
        $output[] = "🔧 Reparando columna 'es_rechazo' en firmas_documentos...";
        
        // 1. Verificar si la columna existe
        $check_column = "
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'firmas_documentos'
            AND COLUMN_NAME = 'es_rechazo'
        ";
        
        $result = $conn->query($check_column);
        $column_exists = $result ? $result->fetch_assoc()['count'] : 0;
        
        if ($column_exists == 0) {
            $output[] = "➕ Agregando columna 'es_rechazo'...";
            
            $add_column = "ALTER TABLE firmas_documentos ADD COLUMN es_rechazo TINYINT(1) DEFAULT 0 AFTER observaciones";
            
            if ($conn->query($add_column)) {
                $output[] = "✅ Columna 'es_rechazo' agregada exitosamente";
            } else {
                $output[] = "❌ Error agregando columna: " . $conn->error;
                return $output;
            }
        } else {
            $output[] = "ℹ️ La columna 'es_rechazo' ya existe";
        }
        
        // 2. Actualizar registros existentes
        $output[] = "🔄 Actualizando registros existentes...";
        
        $update_existing = "UPDATE firmas_documentos SET es_rechazo = 0 WHERE es_rechazo IS NULL";
        
        if ($conn->query($update_existing)) {
            $affected = $conn->affected_rows;
            $output[] = "✅ $affected registros actualizados";
        } else {
            $output[] = "❌ Error actualizando registros: " . $conn->error;
        }
        
        // 3. Verificar estructura final
        $output[] = "📋 Verificando estructura final...";
        
        $describe_table = "DESCRIBE firmas_documentos";
        $result = $conn->query($describe_table);
        
        if ($result) {
            $output[] = "📊 Estructura de firmas_documentos:";
            while ($row = $result->fetch_assoc()) {
                $null_info = $row['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
                $default_info = $row['Default'] ? "DEFAULT '{$row['Default']}'" : '';
                $output[] = "   • {$row['Field']}: {$row['Type']} $null_info $default_info";
            }
        }
        
        // 4. Mostrar estadísticas
        $output[] = "📊 Estadísticas de firmas:";
        
        $stats_query = "
            SELECT 
                COUNT(*) as total_firmas,
                SUM(CASE WHEN es_rechazo = 1 THEN 1 ELSE 0 END) as rechazos,
                SUM(CASE WHEN es_rechazo = 0 THEN 1 ELSE 0 END) as aprobaciones
            FROM firmas_documentos
        ";
        
        $result = $conn->query($stats_query);
        if ($result) {
            $stats = $result->fetch_assoc();
            $output[] = "   • Total firmas: {$stats['total_firmas']}";
            $output[] = "   • Rechazos: {$stats['rechazos']}";
            $output[] = "   • Aprobaciones: {$stats['aprobaciones']}";
        }
        
        // 5. Verificar que el sistema de documentos funcione
        $output[] = "🧪 Probando consulta de documentos...";
        
        $test_query = "
            SELECT COUNT(*) as count
            FROM documentos d 
            WHERE d.id NOT IN (
                SELECT DISTINCT documento_id 
                FROM firmas_documentos 
                WHERE es_rechazo = 1
            )
        ";
        
        $result = $conn->query($test_query);
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            $output[] = "✅ Consulta de documentos funciona correctamente";
            $output[] = "   • Documentos no rechazados: $count";
        } else {
            $output[] = "❌ Error en consulta de prueba: " . $conn->error;
        }
        
        $output[] = "🎉 ¡Columna 'es_rechazo' configurada exitosamente!";
        $output[] = "✅ El sistema de documentos debería funcionar ahora";
        
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
    <title>Reparación Columna es_rechazo</title>
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
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="fas fa-wrench me-2"></i>Reparación Columna es_rechazo</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Este script agrega la columna 'es_rechazo' faltante en la tabla firmas_documentos.
                        </div>
                        
                        <div class="bg-dark text-light p-3 rounded" style="max-height: 600px; overflow-y: auto;">
                            <?php
                            $results = fixEsRechazoColumn();
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
                            <h5><i class="fas fa-check-circle me-2"></i>¡Listo!</h5>
                            <p class="mb-0">Ahora puedes acceder al sistema de documentos sin errores.</p>
                        </div>
                        
                        <div class="mt-3">
                            <a href="documentos.php" class="btn btn-success btn-lg">
                                <i class="fas fa-file-alt me-2"></i>Ir a Documentos
                            </a>
                            <a href="dashboard.php" class="btn btn-primary ms-2">
                                <i class="fas fa-tachometer-alt me-2"></i>Ir al Dashboard
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
