<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Verificando y corrigiendo permisos de usuarios de escuela</h2>";

try {
    // 1. Verificar que existan los roles necesarios
    echo "<h3>1. Verificando roles...</h3>";
    
    $roles_necesarios = ['Coordinador', 'Director de Escuela', 'Participante', 'Escuela', 'Dirección Nacional', 'Educación DNBC'];
    
    foreach ($roles_necesarios as $rol) {
        $query = "SELECT id FROM roles WHERE nombre = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$rol]);
        $existe = $stmt->fetch();
        
        if (!$existe) {
            $query_insert = "INSERT INTO roles (nombre, descripcion) VALUES (?, ?)";
            $stmt_insert = $db->prepare($query_insert);
            $stmt_insert->execute([$rol, "Rol $rol"]);
            echo "✅ Rol '$rol' creado<br>";
        } else {
            echo "✅ Rol '$rol' existe<br>";
        }
    }
    
    // 2. Verificar que la tabla usuarios tenga la columna escuela_id
    echo "<h3>2. Verificando estructura de usuarios...</h3>";
    
    $query = "SHOW COLUMNS FROM usuarios LIKE 'escuela_id'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        $query_alter = "ALTER TABLE usuarios ADD COLUMN escuela_id INT NULL, ADD FOREIGN KEY (escuela_id) REFERENCES escuelas(id)";
        $db->exec($query_alter);
        echo "✅ Columna escuela_id agregada a usuarios<br>";
    } else {
        echo "✅ Columna escuela_id existe en usuarios<br>";
    }
    
    // 3. Verificar que existan escuelas
    echo "<h3>3. Verificando escuelas...</h3>";
    
    $query = "SELECT COUNT(*) as total FROM escuelas";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['total'] == 0) {
        // Crear una escuela de ejemplo
        $query_insert = "INSERT INTO escuelas (nombre, codigo, direccion, telefono, email, activa) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt_insert = $db->prepare($query_insert);
        $stmt_insert->execute([
            'Escuela de Bomberos Central',
            'EBC001',
            'Calle Principal 123',
            '555-0123',
            'central@bomberos.gov.co'
        ]);
        echo "✅ Escuela de ejemplo creada<br>";
    } else {
        echo "✅ Existen {$result['total']} escuelas<br>";
    }
    
    // 4. Asignar usuarios de tipo "Escuela" a escuelas si no tienen asignación
    echo "<h3>4. Asignando usuarios a escuelas...</h3>";
    
    $query = "SELECT u.id, u.nombres, u.apellidos, r.nombre as rol_nombre 
              FROM usuarios u 
              JOIN roles r ON u.rol_id = r.id 
              WHERE r.nombre = 'Escuela' AND u.escuela_id IS NULL";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $usuarios_sin_escuela = $stmt->fetchAll();
    
    if (count($usuarios_sin_escuela) > 0) {
        // Obtener la primera escuela disponible
        $query_escuela = "SELECT id FROM escuelas WHERE activa = 1 LIMIT 1";
        $stmt_escuela = $db->prepare($query_escuela);
        $stmt_escuela->execute();
        $escuela = $stmt_escuela->fetch();
        
        if ($escuela) {
            foreach ($usuarios_sin_escuela as $usuario) {
                $query_update = "UPDATE usuarios SET escuela_id = ? WHERE id = ?";
                $stmt_update = $db->prepare($query_update);
                $stmt_update->execute([$escuela['id'], $usuario['id']]);
                echo "✅ Usuario {$usuario['nombres']} {$usuario['apellidos']} asignado a escuela<br>";
            }
        }
    } else {
        echo "✅ Todos los usuarios de escuela tienen asignación<br>";
    }
    
    // 5. Verificar permisos de acceso
    echo "<h3>5. Verificando permisos...</h3>";
    
    $query = "SELECT u.id, u.nombres, u.apellidos, r.nombre as rol_nombre, u.escuela_id
              FROM usuarios u 
              JOIN roles r ON u.rol_id = r.id 
              WHERE r.nombre IN ('Escuela', 'Coordinador', 'Director de Escuela', 'Participante')
              ORDER BY r.nombre, u.nombres";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $usuarios = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Rol</th><th>Escuela ID</th><th>Estado</th></tr>";
    
    foreach ($usuarios as $usuario) {
        $estado = "✅ OK";
        if (in_array($usuario['rol_nombre'], ['Escuela', 'Director de Escuela']) && !$usuario['escuela_id']) {
            $estado = "❌ Sin escuela asignada";
        }
        
        echo "<tr>";
        echo "<td>{$usuario['id']}</td>";
        echo "<td>{$usuario['nombres']} {$usuario['apellidos']}</td>";
        echo "<td>{$usuario['rol_nombre']}</td>";
        echo "<td>" . ($usuario['escuela_id'] ?? 'NULL') . "</td>";
        echo "<td>$estado</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Verificación completada</h3>";
    echo "<p><a href='usuarios.php'>Ir a Gestión de Usuarios</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
    echo "<p>Detalles: " . $e->getTraceAsString() . "</p>";
}
?>
