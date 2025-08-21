<?php
require_once 'config/database.php';

// Script para probar la descarga de certificados sin errores
echo "<h2>🧪 Prueba de Descarga de Certificados</h2>";

$conn = getMySQLiConnection();

// Obtener un certificado de prueba
$sql = "SELECT d.id, d.participante_id, p.cedula, p.nombres, p.apellidos 
        FROM documentos d 
        INNER JOIN participantes p ON d.participante_id = p.id 
        WHERE d.tipo = 'certificado' 
        LIMIT 1";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $cert = $result->fetch_assoc();
    echo "<p>✅ Certificado encontrado:</p>";
    echo "<ul>";
    echo "<li><strong>ID:</strong> " . $cert['id'] . "</li>";
    echo "<li><strong>Participante:</strong> " . $cert['nombres'] . " " . $cert['apellidos'] . "</li>";
    echo "<li><strong>Cédula:</strong> " . $cert['cedula'] . "</li>";
    echo "</ul>";
    
    echo "<p><a href='participante-descargar-certificado.php?id=" . $cert['id'] . "' target='_blank' class='btn btn-primary'>🔗 Probar Descarga</a></p>";
    
    echo "<p><em>Nota: Asegúrate de estar logueado como participante para probar la descarga.</em></p>";
} else {
    echo "<p>❌ No se encontraron certificados para probar.</p>";
}

// Verificar estructura de tablas relacionadas
echo "<h3>🔍 Verificación de Estructura de Tablas</h3>";

$tables_to_check = ['documentos', 'firmas_documentos', 'usuarios', 'roles', 'participantes'];

foreach ($tables_to_check as $table) {
    echo "<h4>Tabla: $table</h4>";
    $desc_sql = "DESCRIBE $table";
    $desc_result = $conn->query($desc_sql);
    
    if ($desc_result) {
        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por Defecto</th></tr>";
        
        while ($row = $desc_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ Error al obtener estructura de la tabla $table</p>";
    }
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { width: 100%; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
.btn { 
    display: inline-block; 
    padding: 10px 20px; 
    background-color: #007bff; 
    color: white; 
    text-decoration: none; 
    border-radius: 5px; 
}
.btn:hover { background-color: #0056b3; }
</style>
