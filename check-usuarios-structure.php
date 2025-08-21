<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h1>Diagnóstico de Estructura de Usuarios</h1>";

try {
    // Verificar estructura de la tabla usuarios
    echo "<h2>Estructura de la tabla usuarios</h2>";
    $query = "DESCRIBE usuarios";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "<h3>Columnas de la tabla usuarios:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Mostrar algunos usuarios de ejemplo
    $query_users = "SELECT u.*, r.nombre as rol_nombre FROM usuarios u LEFT JOIN roles r ON u.rol_id = r.id LIMIT 5";
    $stmt_users = $db->prepare($query_users);
    $stmt_users->execute();
    $users = $stmt_users->fetchAll();
    
    echo "<h3>Usuarios de ejemplo:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nombres</th><th>Apellidos</th><th>Email</th><th>Rol</th><th>Activo</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['nombres'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($user['apellidos'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['rol_nombre'] ?? 'Sin rol') . "</td>";
        echo "<td>" . htmlspecialchars($user['activo'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar roles
    $query_roles = "SELECT * FROM roles";
    $stmt_roles = $db->prepare($query_roles);
    $stmt_roles->execute();
    $roles = $stmt_roles->fetchAll();
    
    echo "<h3>Roles disponibles:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Descripción</th></tr>";
    
    foreach ($roles as $rol) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($rol['id']) . "</td>";
        echo "<td>" . htmlspecialchars($rol['nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($rol['descripcion'] ?? 'Sin descripción') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<p><a href="usuarios.php">← Volver a Gestión de Usuarios</a></p>
