<?php
require_once 'config/database.php';

function fixTipoFirmaColumn() {
    $conn = getMySQLiConnection();
    $output = [];
    
    try {
        $output[] = "🔧 Reparando columnas de firma en tabla usuarios...";
        
        // 1. Verificar estructura actual de usuarios
        $output[] = "👥 Verificando estructura de tabla usuarios...";
        
        $describe_query = "DESCRIBE usuarios";
        $result = $conn->query($describe_query);
        
        if ($result) {
            $existing_columns = [];
            while ($row = $result->fetch_assoc()) {
                $existing_columns[] = $row['Field'];
            }
            $output[] = "ℹ️ Columnas existentes: " . implode(', ', $existing_columns);
        }
        
        // 2. Definir columnas necesarias para firmas
        $required_columns = [
            'tipo_firma' => "ENUM('texto', 'imagen') DEFAULT 'texto'",
            'firma_digital' => "TEXT NULL"
        ];
        
        // 3. Agregar columnas faltantes
        foreach ($required_columns as $column => $definition) {
            if (!in_array($column, $existing_columns)) {
                $output[] = "➕ Agregando columna '$column'...";
                
                $alter_query = "ALTER TABLE usuarios ADD COLUMN $column $definition";
                
                if ($conn->query($alter_query)) {
                    $output[] = "✅ Columna '$column' agregada exitosamente";
                } else {
                    $output[] = "❌ Error agregando '$column': " . $conn->error;
                }
            } else {
                $output[] = "ℹ️ Columna '$column' ya existe";
            }
        }
        
        // 4. Actualizar usuarios existentes con valores por defecto
        $output[] = "🔄 Actualizando usuarios existentes...";
        
        $update_query = "
            UPDATE usuarios 
            SET 
                tipo_firma = COALESCE(tipo_firma, 'texto'),
                firma_digital = COALESCE(firma_digital, CONCAT(COALESCE(nombres, ''), ' ', COALESCE(apellidos, '')))
            WHERE tipo_firma IS NULL OR firma_digital IS NULL OR firma_digital = ''
        ";
        
        if ($conn->query($update_query)) {
            $affected = $conn->affected_rows;
            $output[] = "✅ $affected usuarios actualizados con firmas por defecto";
        } else {
            $output[] = "❌ Error actualizando usuarios: " . $conn->error;
        }
        
        // 5. Verificar estructura final
        $output[] = "🔍 Verificando estructura final...";
        
        $final_check = "SELECT nombres, apellidos, tipo_firma, firma_digital FROM usuarios LIMIT 3";
        $result = $conn->query($final_check);
        
        if ($result) {
            $output[] = "📋 Usuarios de ejemplo:";
            while ($row = $result->fetch_assoc()) {
                $nombre = $row['nombres'] . ' ' . $row['apellidos'];
                $tipo = $row['tipo_firma'] ?? 'NULL';
                $firma = substr($row['firma_digital'] ?? 'NULL', 0, 30) . '...';
                $output[] = "   • $nombre - Tipo: $tipo - Firma: $firma";
            }
        }
        
        // 6. Contar usuarios con firmas configuradas
        $count_query = "SELECT COUNT(*) as count FROM usuarios WHERE tipo_firma IS NOT NULL AND firma_digital IS NOT NULL";
        $result = $conn->query($count_query);
        $users_with_signatures = $result ? $result->fetch_assoc()['count'] : 0;
        
        $output[] = "📊 Usuarios con firmas configuradas: $users_with_signatures";
        
        // 7. Probar consulta que causaba error
        $output[] = "🧪 Probando consulta con tipo_firma...";
        
        $test_query = "SELECT COUNT(*) as count FROM usuarios WHERE tipo_firma = 'texto'";
        $result = $conn->query($test_query);
        
        if ($result !== false) {
            $count = $result->fetch_assoc()['count'];
            $output[] = "✅ Consulta exitosa - Usuarios con firma de texto: $count";
        } else {
            $output[] = "❌ Error en consulta de prueba: " . $conn->error;
        }
        
        $output[] = "🎉 ¡Columnas de firma reparadas exitosamente!";
        
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
    <title>Reparación de Columnas de Firma</title>
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
                        <h4 class="mb-0"><i class="fas fa-signature me-2"></i>Reparación de Columnas de Firma</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Este script agrega las columnas necesarias para el manejo de firmas digitales.
                        </div>
                        
                        <div class="bg-dark text-light p-3 rounded" style="max-height: 600px; overflow-y: auto;">
                            <?php
                            $results = fixTipoFirmaColumn();
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
                            <h5><i class="fas fa-check-circle me-2"></i>Próximos Pasos:</h5>
                            <ul class="mb-0">
                                <li>Las columnas de firma han sido agregadas</li>
                                <li>Los usuarios existentes tienen firmas por defecto</li>
                                <li>Ahora puedes configurar firmas personalizadas</li>
                            </ul>
                        </div>
                        
                        <div class="mt-3">
                            <a href="documentos.php" class="btn btn-success btn-lg">
                                <i class="fas fa-file-alt me-2"></i>Ir a Documentos
                            </a>
                            <a href="configurar-firma.php" class="btn btn-primary ms-2">
                                <i class="fas fa-signature me-2"></i>Configurar Firmas
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
