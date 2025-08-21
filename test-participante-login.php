<?php
require_once 'config/database.php';

echo "<h2>Prueba de Login de Participantes</h2>";

// Obtener algunos participantes de ejemplo
$stmt = $pdo->query("SELECT id, nombre, apellido, cedula, email FROM participantes LIMIT 5");
$participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Participantes de ejemplo para probar:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Cédula</th><th>Email</th><th>Contraseña por defecto</th></tr>";

foreach ($participantes as $p) {
    echo "<tr>";
    echo "<td>" . $p['id'] . "</td>";
    echo "<td>" . $p['nombre'] . " " . $p['apellido'] . "</td>";
    echo "<td>" . $p['cedula'] . "</td>";
    echo "<td>" . $p['email'] . "</td>";
    echo "<td>" . $p['cedula'] . " (su cédula)</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><strong>Para probar el login:</strong></p>";
echo "<ol>";
echo "<li>Ve a <a href='participante-login.php'>participante-login.php</a></li>";
echo "<li>Usa cualquier cédula o email de la tabla anterior</li>";
echo "<li>La contraseña es el número de cédula del participante</li>";
echo "</ol>";
?>
