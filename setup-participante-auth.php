<?php
require_once 'config/database.php';

echo "<h2>Configuración de Autenticación para Participantes</h2>";

try {
    // Verificar estructura actual
    echo "<h3>1. Verificando estructura actual...</h3>";
    $result = $pdo->query("DESCRIBE participantes");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    $existing_columns = array_column($columns, 'Field');
    echo "Columnas existentes: " . implode(', ', $existing_columns) . "<br><br>";
    
    // Agregar columnas faltantes
    $columns_to_add = [
        'password' => 'VARCHAR(255) NULL',
        'ultimo_acceso' => 'TIMESTAMP NULL',
        'token_reset' => 'VARCHAR(255) NULL',
        'token_reset_expira' => 'TIMESTAMP NULL'
    ];
    
    echo "<h3>2. Agregando columnas faltantes...</h3>";
    foreach ($columns_to_add as $column => $definition) {
        if (!in_array($column, $existing_columns)) {
            try {
                $pdo->exec("ALTER TABLE participantes ADD COLUMN $column $definition");
                echo "✅ Columna '$column' agregada exitosamente<br>";
            } catch (PDOException $e) {
                echo "⚠️ Error agregando '$column': " . $e->getMessage() . "<br>";
            }
        } else {
            echo "ℹ️ Columna '$column' ya existe<br>";
        }
    }
    
    // Crear índices
    echo "<h3>3. Creando índices...</h3>";
    $indexes = [
        'idx_participantes_email' => 'CREATE INDEX idx_participantes_email ON participantes(email)',
        'idx_participantes_cedula' => 'CREATE INDEX idx_participantes_cedula ON participantes(cedula)'
    ];
    
    foreach ($indexes as $name => $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Índice '$name' creado exitosamente<br>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "ℹ️ Índice '$name' ya existe<br>";
            } else {
                echo "⚠️ Error creando índice '$name': " . $e->getMessage() . "<br>";
            }
        }
    }
    
    // Configurar contraseñas por defecto
    echo "<h3>4. Configurando contraseñas por defecto...</h3>";
    $stmt = $pdo->prepare("UPDATE participantes SET password = MD5(cedula) WHERE password IS NULL OR password = ''");
    $stmt->execute();
    $affected = $stmt->rowCount();
    echo "✅ $affected participantes actualizados con contraseña por defecto<br>";
    
    // Verificar resultados
    echo "<h3>5. Verificación final...</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(password) as con_password FROM participantes");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total de participantes: " . $stats['total'] . "<br>";
    echo "Con contraseña configurada: " . $stats['con_password'] . "<br>";
    
    if ($stats['total'] == $stats['con_password']) {
        echo "<div style='color: green; font-weight: bold; margin-top: 20px;'>";
        echo "🎉 ¡Configuración completada exitosamente!<br>";
        echo "Los participantes pueden ahora acceder con:<br>";
        echo "- Usuario: Su email o cédula<br>";
        echo "- Contraseña: Su número de cédula<br>";
        echo "</div>";
        
        echo "<p><a href='participante-login.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Login de Participantes</a></p>";
    } else {
        echo "<div style='color: red;'>⚠️ Algunos participantes no tienen contraseña configurada</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}
?>
