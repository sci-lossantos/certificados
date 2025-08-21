<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>🔍 Diagnóstico de Configuración de Certificados</h2>";
    
    // Verificar si la tabla existe
    $query = "SHOW TABLES LIKE 'configuracion_certificados'";
    $result = $db->query($query);
    
    if ($result->rowCount() > 0) {
        echo "<p>✅ Tabla 'configuracion_certificados' existe</p>";
        
        // Verificar estructura
        $query = "DESCRIBE configuracion_certificados";
        $columns = $db->query($query)->fetchAll();
        
        echo "<h3>📋 Estructura de la tabla:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Verificar datos existentes
        $query = "SELECT * FROM configuracion_certificados";
        $configs = $db->query($query)->fetchAll();
        
        echo "<h3>📊 Configuraciones existentes (" . count($configs) . "):</h3>";
        
        if (count($configs) > 0) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Escuela ID</th></tr>";
            foreach ($configs as $config) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($config['id']) . "</td>";
                echo "<td>" . htmlspecialchars($config['nombre'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($config['descripcion'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($config['escuela_id'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ No hay configuraciones en la tabla</p>";
        }
        
    } else {
        echo "<p>❌ Tabla 'configuracion_certificados' NO existe</p>";
        echo "<p>💡 Necesitas ejecutar el script SQL para crearla</p>";
    }
    
    // Verificar otras tablas relacionadas
    echo "<h3>🔗 Verificación de tablas relacionadas:</h3>";
    
    $tables_to_check = ['cursos', 'escuelas', 'usuarios', 'roles'];
    foreach ($tables_to_check as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $result = $db->query($query);
        $status = $result->rowCount() > 0 ? "✅" : "❌";
        echo "<p>$status Tabla '$table'</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<hr>
<h3>🛠️ Acciones recomendadas:</h3>
<ol>
    <li><a href="scripts/51-fix-configuracion-certificados-structure.sql" target="_blank">📄 Ver script SQL de corrección</a></li>
    <li><a href="configuracion-certificados-simple.php">🔧 Ir a configuración simple</a></li>
    <li><a href="configuracion-certificados.php">⚙️ Ir a configuración completa</a></li>
</ol>
