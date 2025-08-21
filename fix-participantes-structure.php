<?php
require_once 'config/database.php';

echo "<h2>🔧 Reparación de Estructura de Participantes</h2>";

try {
    $conn = getConnection();
    
    echo "<h3>1. Verificando estructura actual...</h3>";
    
    // Verificar columnas existentes
    $result = $conn->query("SHOW COLUMNS FROM participantes");
    $existing_columns = [];
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>2. Agregando columnas faltantes...</h3>";
    
    // Columnas necesarias para autenticación
    $required_columns = [
        'password' => "VARCHAR(255) NULL",
        'activo' => "BOOLEAN DEFAULT TRUE",
        'ultimo_acceso' => "TIMESTAMP NULL",
        'token_reset' => "VARCHAR(255) NULL",
        'token_reset_expira' => "TIMESTAMP NULL"
    ];
    
    foreach ($required_columns as $column => $definition) {
        if (!in_array($column, $existing_columns)) {
            try {
                $sql = "ALTER TABLE participantes ADD COLUMN $column $definition";
                $conn->query($sql);
                echo "✅ Columna '$column' agregada exitosamente<br>";
            } catch (Exception $e) {
                echo "❌ Error agregando '$column': " . $e->getMessage() . "<br>";
            }
        } else {
            echo "ℹ️ Columna '$column' ya existe<br>";
        }
    }
    
    echo "<h3>3. Creando índices...</h3>";
    
    // Crear índices
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_participantes_email ON participantes(email)",
        "CREATE INDEX IF NOT EXISTS idx_participantes_cedula ON participantes(cedula)"
    ];
    
    foreach ($indexes as $index_sql) {
        try {
            $conn->query($index_sql);
            echo "✅ Índice creado exitosamente<br>";
        } catch (Exception $e) {
            echo "ℹ️ Índice ya existe o error: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h3>4. Configurando contraseñas por defecto...</h3>";
    
    // Configurar contraseñas por defecto
    $sql = "UPDATE participantes SET password = MD5(cedula), activo = TRUE WHERE password IS NULL OR password = ''";
    $result = $conn->query($sql);
    
    if ($result) {
        echo "✅ Contraseñas configuradas exitosamente<br>";
    } else {
        echo "❌ Error configurando contraseñas: " . $conn->error . "<br>";
    }
    
    echo "<h3>5. Verificación final...</h3>";
    
    // Verificar resultados
    $result = $conn->query("SELECT COUNT(*) as total, COUNT(password) as con_password, COUNT(CASE WHEN activo = 1 THEN 1 END) as activos FROM participantes");
    $stats = $result->fetch_assoc();
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>📊 Estadísticas:</strong><br>";
    echo "Total de participantes: " . $stats['total'] . "<br>";
    echo "Con contraseña configurada: " . $stats['con_password'] . "<br>";
    echo "Participantes activos: " . $stats['activos'] . "<br>";
    echo "</div>";
    
    // Mostrar algunos participantes de ejemplo
    echo "<h3>6. Participantes de ejemplo:</h3>";
    $result = $conn->query("SELECT id, nombres, apellidos, cedula, email, CASE WHEN password IS NOT NULL THEN 'Configurada' ELSE 'Sin configurar' END as password_status, activo FROM participantes LIMIT 5");
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Nombres</th><th>Apellidos</th><th>Cédula</th><th>Email</th><th>Password</th><th>Activo</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['nombres'] . "</td>";
            echo "<td>" . $row['apellidos'] . "</td>";
            echo "<td>" . $row['cedula'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td>" . $row['password_status'] . "</td>";
            echo "<td>" . ($row['activo'] ? 'Sí' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>🎉 ¡Configuración completada exitosamente!</h4>";
        echo "<p><strong>Los participantes pueden ahora acceder con:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Usuario:</strong> Su email o número de cédula</li>";
        echo "<li><strong>Contraseña:</strong> Su número de cédula</li>";
        echo "</ul>";
        echo "<p><a href='participante-login.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Ir al Login de Participantes</a></p>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "⚠️ No se encontraron participantes en la base de datos. Necesitas agregar participantes primero.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}
?>
