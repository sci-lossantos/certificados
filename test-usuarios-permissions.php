<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Prueba de Permisos de Usuarios</h2>";

// Simular diferentes roles
$roles_a_probar = ['Administrador General', 'Escuela', 'Coordinador'];

foreach ($roles_a_probar as $rol) {
    echo "<h3>Probando rol: $rol</h3>";
    
    // Buscar un usuario con este rol
    $query = "SELECT u.*, r.nombre as rol_nombre 
              FROM usuarios u 
              JOIN roles r ON u.rol_id = r.id 
              WHERE r.nombre = ? 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$rol]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        echo "Usuario encontrado: {$usuario['nombres']} {$usuario['apellidos']}<br>";
        
        // Simular sesión
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_name'] = $usuario['nombres'] . ' ' . $usuario['apellidos'];
        $_SESSION['user_role'] = $usuario['rol_nombre'];
        $_SESSION['user_role_id'] = $usuario['rol_id'];
        
        $auth = new Auth($db);
        
        // Probar permisos
        echo "¿Puede acceder a usuarios.php? ";
        if ($auth->hasRole(['Administrador General', 'Escuela'])) {
            echo "✅ SÍ<br>";
            
            // Determinar qué puede gestionar
            if ($rol === 'Administrador General') {
                $roles_permitidos = ['Escuela', 'Dirección Nacional', 'Educación DNBC'];
                echo "Puede gestionar: " . implode(', ', $roles_permitidos) . "<br>";
            } elseif ($rol === 'Escuela') {
                $roles_permitidos = ['Coordinador', 'Director de Escuela', 'Participante'];
                echo "Puede gestionar: " . implode(', ', $roles_permitidos) . "<br>";
                
                $escuela_id = $auth->getUserEscuelaId();
                echo "Escuela ID: " . ($escuela_id ?? 'NULL') . "<br>";
            }
        } else {
            echo "❌ NO<br>";
        }
        
        echo "<br>";
    } else {
        echo "❌ No se encontró usuario con rol $rol<br><br>";
    }
}

echo "<h3>Estructura de la base de datos:</h3>";

// Verificar estructura
$tables = ['usuarios', 'roles', 'escuelas'];
foreach ($tables as $table) {
    echo "<h4>Tabla: $table</h4>";
    try {
        $query = "DESCRIBE $table";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}

echo "<p><a href='fix-usuarios-escuela-permissions.php'>Ejecutar corrección de permisos</a></p>";
echo "<p><a href='usuarios.php'>Ir a Gestión de Usuarios</a></p>";
?>
