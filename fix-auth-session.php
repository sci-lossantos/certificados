<?php
session_start();

echo "<h2>Reparar Sesión de Autenticación</h2>";

// Verificar si hay sesión
if (!isset($_SESSION['user_id'])) {
    echo "<h3>No hay sesión activa. Creando sesión de prueba...</h3>";
    
    // Crear sesión de prueba para administrador
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Administrador de Prueba';
    $_SESSION['user_role'] = 'Administrador General';
    $_SESSION['user_role_id'] = 1;
    
    echo "✅ Sesión de prueba creada<br>";
    echo "Usuario: " . $_SESSION['user_name'] . "<br>";
    echo "Rol: " . $_SESSION['user_role'] . "<br>";
} else {
    echo "<h3>Sesión existente:</h3>";
    echo "Usuario: " . ($_SESSION['user_name'] ?? 'Sin nombre') . "<br>";
    echo "Rol: " . ($_SESSION['user_role'] ?? 'Sin rol') . "<br>";
}

echo "<hr>";
echo "<h3>Probar acceso:</h3>";
echo "<a href='configuracion-certificados-simple.php'>Configuración Simple</a><br>";
echo "<a href='configuracion-certificados.php'>Configuración Completa</a><br>";
echo "<a href='dashboard.php'>Dashboard</a><br>";

// Verificar base de datos
echo "<hr>";
echo "<h3>Verificar Base de Datos:</h3>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar usuarios
    $query = "SELECT id, nombres, apellidos, email, rol_id FROM usuarios LIMIT 5";
    $stmt = $db->query($query);
    $usuarios = $stmt->fetchAll();
    
    echo "<h4>Usuarios en la base de datos:</h4>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol ID</th></tr>";
    foreach ($usuarios as $usuario) {
        echo "<tr>";
        echo "<td>" . $usuario['id'] . "</td>";
        echo "<td>" . $usuario['nombres'] . " " . $usuario['apellidos'] . "</td>";
        echo "<td>" . $usuario['email'] . "</td>";
        echo "<td>" . $usuario['rol_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar roles
    $query = "SELECT * FROM roles";
    $stmt = $db->query($query);
    $roles = $stmt->fetchAll();
    
    echo "<h4>Roles disponibles:</h4>";
    echo "<ul>";
    foreach ($roles as $rol) {
        echo "<li>ID: " . $rol['id'] . " - " . $rol['nombre'] . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "❌ Error de base de datos: " . $e->getMessage();
}
?>
