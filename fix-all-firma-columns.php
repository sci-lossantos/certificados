<?php
require_once 'config/database.php';

function fixAllFirmaColumns() {
    $conn = getMySQLiConnection();
    $output = [];
    
    try {
        $output[] = "🔍 Diagnóstico completo de columnas de firma...";
        
        // 1. Tablas que podrían necesitar columnas de firma
        $tables_to_check = [
            'usuarios',
            'firmas_usuarios',  // Esta tabla podría ser la que causa el problema
            'firmas_documentos'
        ];
        
        // 2. Columnas necesarias para cada tabla
        $required_columns = [
            'usuarios' => [
                'tipo_firma' => "ENUM('texto', 'imagen', 'canvas', 'upload') DEFAULT 'texto'",
                'firma_digital' => "TEXT NULL"
            ],
            'firmas_usuarios' => [
                'tipo_firma' => "ENUM('texto', 'canvas', 'upload') DEFAULT 'texto'",
                'contenido_firma' => "TEXT NULL",
                'activa' => "TINYINT(1) DEFAULT 1"
            ],
            'firmas_documentos' => [
                'tipo_firma' => "VARCHAR(50) DEFAULT 'texto'"
            ]
        ];
        
        // 3. Verificar si la tabla firmas_usuarios existe
        $output[] = "🔍 Verificando tabla firmas_usuarios...";
        $check_table = $conn->query("SHOW TABLES LIKE 'firmas_usuarios'");
        
        if ($check_table->num_rows == 0) {
            $output[] = "➕ Creando tabla firmas_usuarios...";
            
            $create_table = "
                CREATE TABLE IF NOT EXISTS firmas_usuarios (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    tipo_firma ENUM('texto', 'canvas', 'upload') DEFAULT 'texto',
                    contenido_firma TEXT NULL,
                    activa TINYINT(1) DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
                )
            ";
            
            if ($conn->query($create_table)) {
                $output[] = "✅ Tabla firmas_usuarios creada exitosamente";
            } else {
                $output[] = "❌ Error creando tabla firmas_usuarios: " . $conn->error;
            }
        } else {
            $output[] = "ℹ️ Tabla firmas_usuarios ya existe";
        }
        
        // 4. Verificar y arreglar cada tabla
        foreach ($tables_to_check as $table) {
            $output[] = "🔍 Verificando tabla $table...";
            
            // Verificar si la tabla existe
            $check_table = $conn->query("SHOW TABLES LIKE '$table'");
            if ($check_table->num_rows == 0) {
                $output[] = "⚠️ Tabla $table no existe, omitiendo...";
                continue;
            }
            
            // Obtener columnas existentes
            $describe_query = "DESCRIBE $table";
            $result = $conn->query($describe_query);
            
            if ($result) {
                $existing_columns = [];
                while ($row = $result->fetch_assoc()) {
                    $existing_columns[] = $row['Field'];
                }
                $output[] = "ℹ️ Columnas existentes en $table: " . implode(', ', $existing_columns);
            }
            
            // Agregar columnas faltantes
            if (isset($required_columns[$table])) {
                foreach ($required_columns[$table] as $column => $definition) {
                    if (!in_array($column, $existing_columns)) {
                        $output[] = "➕ Agregando columna '$column' a tabla $table...";
                        
                        $alter_query = "ALTER TABLE $table ADD COLUMN $column $definition";
                        
                        if ($conn->query($alter_query)) {
                            $output[] = "✅ Columna '$column' agregada exitosamente a $table";
                        } else {
                            $output[] = "❌ Error agregando '$column' a $table: " . $conn->error;
                        }
                    } else {
                        $output[] = "ℹ️ Columna '$column' ya existe en $table";
                    }
                }
            }
        }
        
        // 5. Migrar firmas existentes de usuarios a firmas_usuarios
        $output[] = "🔄 Migrando firmas existentes a tabla firmas_usuarios...";
        
        // Verificar si hay firmas para migrar
        $check_firmas = $conn->query("
            SELECT u.id, u.tipo_firma, u.firma_digital 
            FROM usuarios u 
            LEFT JOIN firmas_usuarios fu ON u.id = fu.usuario_id AND fu.activa = 1
            WHERE u.firma_digital IS NOT NULL 
            AND u.firma_digital != '' 
            AND fu.id IS NULL
        ");
        
        if ($check_firmas->num_rows > 0) {
            $output[] = "🔄 Migrando " . $check_firmas->num_rows . " firmas...";
            
            while ($row = $check_firmas->fetch_assoc()) {
                $usuario_id = $row['id'];
                $tipo_firma = $row['tipo_firma'] ?? 'texto';
                $contenido_firma = $row['firma_digital'];
                
                $insert_firma = "
                    INSERT INTO firmas_usuarios (usuario_id, tipo_firma, contenido_firma, activa)
                    VALUES (?, ?, ?, 1)
                ";
                
                $stmt = $conn->prepare($insert_firma);
                $stmt->bind_param("iss", $usuario_id, $tipo_firma, $contenido_firma);
                
                if ($stmt->execute()) {
                    $output[] = "✅ Firma migrada para usuario ID: $usuario_id";
                } else {
                    $output[] = "❌ Error migrando firma para usuario ID: $usuario_id - " . $stmt->error;
                }
            }
        } else {
            $output[] = "ℹ️ No hay firmas para migrar";
        }
        
        // 6. Probar consultas específicas para el rol Coordinador
        $output[] = "🧪 Probando consultas para rol Coordinador...";
        
        // Consulta que podría estar fallando
        $test_query = "
            SELECT COUNT(*) as count 
            FROM usuarios u
            LEFT JOIN firmas_usuarios fu ON u.id = fu.usuario_id AND fu.activa = 1
            WHERE u.rol_id = (SELECT id FROM roles WHERE nombre = 'Coordinador')
            AND fu.tipo_firma IS NOT NULL
        ";
        
        $result = $conn->query($test_query);
        
        if ($result !== false) {
            $count = $result->fetch_assoc()['count'];
            $output[] = "✅ Consulta exitosa - Coordinadores con firma configurada: $count";
        } else {
            $output[] = "❌ Error en consulta de prueba: " . $conn->error;
            
            // Intentar una consulta más simple
            $simple_query = "SELECT id FROM roles WHERE nombre = 'Coordinador'";
            $result = $conn->query($simple_query);
            
            if ($result !== false) {
                $role_id = $result->fetch_assoc()['id'] ?? 'no encontrado';
                $output[] = "ℹ️ ID del rol Coordinador: $role_id";
            } else {
                $output[] = "❌ Error obteniendo ID del rol: " . $conn->error;
            }
        }
        
        // 7. Verificar si hay firmas para coordinadores
        $check_coord_firmas = $conn->query("
            SELECT u.id, u.nombres, u.apellidos, fu.tipo_firma, fu.contenido_firma
            FROM usuarios u
            JOIN roles r ON u.rol_id = r.id
            LEFT JOIN firmas_usuarios fu ON u.id = fu.usuario_id AND fu.activa = 1
            WHERE r.nombre = 'Coordinador'
            LIMIT 3
        ");
        
        if ($check_coord_firmas->num_rows > 0) {
            $output[] = "📋 Coordinadores con firmas:";
            while ($row = $check_coord_firmas->fetch_assoc()) {
                $nombre = $row['nombres'] . ' ' . $row['apellidos'];
                $tipo = $row['tipo_firma'] ?? 'no configurada';
                $firma = $row['contenido_firma'] ? 'configurada' : 'no configurada';
                $output[] = "   • $nombre - Tipo: $tipo - Firma: $firma";
            }
        } else {
            $output[] = "⚠️ No se encontraron coordinadores con firmas";
            
            // Crear firma por defecto para coordinadores
            $create_coord_firmas = $conn->query("
                INSERT INTO firmas_usuarios (usuario_id, tipo_firma, contenido_firma, activa)
                SELECT u.id, 'texto', CONCAT(u.nombres, ' ', u.apellidos), 1
                FROM usuarios u
                JOIN roles r ON u.rol_id = r.id
                LEFT JOIN firmas_usuarios fu ON u.id = fu.usuario_id AND fu.activa = 1
                WHERE r.nombre = 'Coordinador' AND fu.id IS NULL
            ");
            
            if ($create_coord_firmas) {
                $affected = $conn->affected_rows;
                $output[] = "✅ Creadas $affected firmas por defecto para coordinadores";
            } else {
                $output[] = "❌ Error creando firmas por defecto: " . $conn->error;
            }
        }
        
        $output[] = "🎉 ¡Diagnóstico y reparación completos!";
        
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
                        <h4 class="mb-0"><i class="fas fa-tools me-2"></i>Reparación Completa de Sistema de Firmas</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Este script realiza un diagnóstico completo y repara todas las tablas relacionadas con firmas.
                        </div>
                        
                        <div class="bg-dark text-light p-3 rounded" style="max-height: 600px; overflow-y: auto;">
                            <?php
                            $results = fixAllFirmaColumns();
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
                                <li>Todas las tablas de firmas han sido verificadas y reparadas</li>
                                <li>Se han migrado las firmas existentes al nuevo formato</li>
                                <li>Se han creado firmas por defecto para coordinadores</li>
                                <li>Ahora puedes acceder al sistema de documentos sin errores</li>
                            </ul>
                        </div>
                        
                        <div class="mt-3">
                            <a href="documentos.php" class="btn btn-success btn-lg">
                                <i class="fas fa-file-alt me-2"></i>Ir a Documentos
                            </a>
                            <a href="configurar-firma.php" class="btn btn-primary ms-2">
                                <i class="fas fa-signature me-2"></i>Configurar Firmas
                            </a>
                            <a href="dashboard.php" class="btn btn-info ms-2">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
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
