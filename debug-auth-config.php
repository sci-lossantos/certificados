<?php
session_start();

echo "<h2>Debug de Autenticación - Configuración Certificados</h2>";

// Mostrar información de la sesión
echo "<h3>Información de Sesión:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Verificar si hay sesión activa
echo "<h3>Estado de Autenticación:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ Usuario logueado: " . ($_SESSION['user_name'] ?? 'Sin nombre') . "<br>";
    echo "✅ Rol: " . ($_SESSION['user_role'] ?? 'Sin rol') . "<br>";
    echo "✅ ID Usuario: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "❌ No hay sesión activa<br>";
}

// Verificar roles permitidos
$roles_permitidos = ['Administrador General', 'Director de Escuela', 'Educación DNBC', 'Dirección Nacional'];
$user_role = $_SESSION['user_role'] ?? '';

echo "<h3>Verificación de Roles:</h3>";
echo "Rol actual: " . $user_role . "<br>";
echo "Roles permitidos: " . implode(', ', $roles_permitidos) . "<br>";

if (in_array($user_role, $roles_permitidos)) {
    echo "✅ Rol permitido para configuración de certificados<br>";
} else {
    echo "❌ Rol NO permitido para configuración de certificados<br>";
}

// Probar conexión a base de datos
echo "<h3>Conexión a Base de Datos:</h3>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Conexión a base de datos exitosa<br>";
    
    // Verificar tabla configuracion_certificados
    $query = "SHOW TABLES LIKE 'configuracion_certificados'";
    $result = $db->query($query);
    if ($result->rowCount() > 0) {
        echo "✅ Tabla configuracion_certificados existe<br>";
        
        // Verificar estructura de la tabla
        $query = "DESCRIBE configuracion_certificados";
        $result = $db->query($query);
        $columns = $result->fetchAll();
        
        echo "<h4>Columnas de configuracion_certificados:</h4>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>" . $column['Field'] . " (" . $column['Type'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "❌ Tabla configuracion_certificados NO existe<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Acciones:</h3>";
echo "<a href='login.php'>Ir a Login</a> | ";
echo "<a href='dashboard.php'>Ir a Dashboard</a> | ";
echo "<a href='configuracion-certificados-simple.php'>Configuración Simple</a>";
?>
