<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Configuración de Certificados - Diagnóstico y Reparación</h2>";

try {
    // Verificar si existe la tabla configuracion_certificados
    $query_check = "SHOW TABLES LIKE 'configuracion_certificados'";
    $stmt_check = $db->prepare($query_check);
    $stmt_check->execute();
    $table_exists = $stmt_check->fetch();
    
    if (!$table_exists) {
        echo "<p style='color: orange;'>La tabla configuracion_certificados no existe. Creándola...</p>";
        
        // Crear la tabla
        $create_table = "
        CREATE TABLE configuracion_certificados (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(255) NOT NULL DEFAULT 'Configuración DNBC',
            descripcion TEXT,
            activo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $db->exec($create_table);
        echo "<p style='color: green;'>✅ Tabla configuracion_certificados creada exitosamente</p>";
    } else {
        echo "<p style='color: green;'>✅ La tabla configuracion_certificados ya existe</p>";
    }
    
    // Verificar estructura actual
    $query_structure = "DESCRIBE configuracion_certificados";
    $stmt_structure = $db->prepare($query_structure);
    $stmt_structure->execute();
    $columns = $stmt_structure->fetchAll();
    
    echo "<h3>Estructura actual de la tabla:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar si hay datos
    $query_data = "SELECT COUNT(*) as total FROM configuracion_certificados";
    $stmt_data = $db->prepare($query_data);
    $stmt_data->execute();
    $count = $stmt_data->fetch();
    
    echo "<h3>Datos existentes:</h3>";
    echo "<p>Total de configuraciones: " . $count['total'] . "</p>";
    
    if ($count['total'] == 0) {
        echo "<p style='color: orange;'>No hay configuraciones. Insertando configuración por defecto...</p>";
        
        // Insertar configuración básica
        $insert_config = "
        INSERT INTO configuracion_certificados (nombre, descripcion, activo) 
        VALUES ('Configuración Estándar DNBC', 'Configuración por defecto para certificados de la Dirección Nacional de Bomberos de Colombia', 1)";
        
        $db->exec($insert_config);
        echo "<p style='color: green;'>✅ Configuración por defecto insertada</p>";
    } else {
        // Mostrar configuraciones existentes
        $query_configs = "SELECT * FROM configuracion_certificados";
        $stmt_configs = $db->prepare($query_configs);
        $stmt_configs->execute();
        $configs = $stmt_configs->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Activo</th><th>Creado</th></tr>";
        
        foreach ($configs as $config) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($config['id']) . "</td>";
            echo "<td>" . htmlspecialchars($config['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($config['descripcion'] ?? 'Sin descripción') . "</td>";
            echo "<td>" . ($config['activo'] ? 'Sí' : 'No') . "</td>";
            echo "<td>" . htmlspecialchars($config['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<p style='color: green; font-weight: bold;'>✅ Configuración de certificados lista para usar</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<p><a href="configuracion-certificados.php">→ Ir a Configuración de Certificados</a></p>
<p><a href="usuarios.php">← Volver a Gestión de Usuarios</a></p>
