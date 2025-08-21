<?php
require_once 'config/database.php';

echo "<h2>Verificando y corrigiendo estructura de la tabla usuarios</h2>";

try {
    $conn = getMySQLiConnection();
    
    // Verificar la estructura actual de la tabla usuarios
    echo "<h3>1. Verificando estructura actual de la tabla usuarios:</h3>";
    $result = $conn->query("DESCRIBE usuarios");
    $columns = [];
    
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar si existe la columna cargo
    $has_cargo = in_array('cargo', $columns);
    echo "<p><strong>¿Existe columna 'cargo'?</strong> " . ($has_cargo ? "SÍ" : "NO") . "</p>";
    
    // Si no existe, agregarla
    if (!$has_cargo) {
        echo "<h3>2. Agregando columna 'cargo' a la tabla usuarios:</h3>";
        $sql = "ALTER TABLE usuarios ADD COLUMN cargo VARCHAR(100) DEFAULT NULL AFTER apellidos";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Columna 'cargo' agregada exitosamente</p>";
        } else {
            echo "<p style='color: red;'>✗ Error al agregar columna 'cargo': " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ La columna 'cargo' ya existe</p>";
    }
    
    // Actualizar algunos cargos por defecto basados en roles
    echo "<h3>3. Actualizando cargos por defecto:</h3>";
    
    $cargo_updates = [
        "UPDATE usuarios SET cargo = 'Director Nacional' WHERE rol_id = (SELECT id FROM roles WHERE nombre = 'Dirección Nacional')",
        "UPDATE usuarios SET cargo = 'Director de Escuela' WHERE rol_id = (SELECT id FROM roles WHERE nombre = 'Director de Escuela')",
        "UPDATE usuarios SET cargo = 'Coordinador Académico' WHERE rol_id = (SELECT id FROM roles WHERE nombre = 'Coordinador')",
        "UPDATE usuarios SET cargo = 'Instructor' WHERE rol_id = (SELECT id FROM roles WHERE nombre = 'Instructor')",
        "UPDATE usuarios SET cargo = 'Administrador del Sistema' WHERE rol_id = (SELECT id FROM roles WHERE nombre = 'Admin')"
    ];
    
    foreach ($cargo_updates as $update_sql) {
        if ($conn->query($update_sql)) {
            $affected = $conn->affected_rows;
            echo "<p style='color: green;'>✓ Actualizado $affected usuario(s)</p>";
        } else {
            echo "<p style='color: red;'>✗ Error en actualización: " . $conn->error . "</p>";
        }
    }
    
    // Mostrar usuarios actualizados
    echo "<h3>4. Usuarios con sus cargos:</h3>";
    $result = $conn->query("
        SELECT u.id, u.nombres, u.apellidos, u.cargo, r.nombre as rol 
        FROM usuarios u 
        LEFT JOIN roles r ON u.rol_id = r.id 
        ORDER BY u.id
    ");
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Apellidos</th><th>Cargo</th><th>Rol</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nombres'] . "</td>";
        echo "<td>" . $row['apellidos'] . "</td>";
        echo "<td>" . ($row['cargo'] ?: 'Sin cargo') . "</td>";
        echo "<td>" . $row['rol'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Proceso completado exitosamente</h3>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
