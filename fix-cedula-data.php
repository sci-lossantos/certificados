<?php
// Script para verificar y corregir datos de cédula en la base de datos
require_once 'config/database.php';

echo "<h1>Verificación y Corrección de Datos de Cédula</h1>";

$conn = getMySQLiConnection();

// Verificar datos actuales en participantes
echo "<h2>📋 Datos actuales en la tabla participantes:</h2>";
$sql = "SELECT id, nombres, apellidos, cedula FROM participantes LIMIT 10";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nombres</th><th>Apellidos</th><th>Cédula</th><th>Tipo</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $tipo_cedula = is_numeric($row['cedula']) ? "Numérica" : "Texto";
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nombres'] . "</td>";
        echo "<td>" . $row['apellidos'] . "</td>";
        echo "<td>" . $row['cedula'] . "</td>";
        echo "<td>" . $tipo_cedula . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No se encontraron participantes.</p>";
}

// Buscar cédulas problemáticas
echo "<h2>🔍 Cédulas con caracteres no numéricos:</h2>";
$sql = "SELECT id, nombres, apellidos, cedula FROM participantes WHERE cedula REGEXP '[^0-9]'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nombres</th><th>Apellidos</th><th>Cédula Original</th><th>Cédula Limpia</th><th>Acción</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $cedula_limpia = preg_replace('/[^0-9]/', '', $row['cedula']);
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nombres'] . "</td>";
        echo "<td>" . $row['apellidos'] . "</td>";
        echo "<td>" . $row['cedula'] . "</td>";
        echo "<td>" . $cedula_limpia . "</td>";
        echo "<td>";
        
        if (!empty($cedula_limpia) && is_numeric($cedula_limpia)) {
            // Actualizar la cédula
            $update_sql = "UPDATE participantes SET cedula = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $cedula_limpia, $row['id']);
            
            if ($stmt->execute()) {
                echo "✅ Corregida";
            } else {
                echo "❌ Error al corregir";
            }
        } else {
            echo "⚠️ Requiere revisión manual";
        }
        
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>✅ No se encontraron cédulas con caracteres problemáticos.</p>";
}

// Verificar documentos con participantes problemáticos
echo "<h2>📄 Documentos que podrían tener problemas:</h2>";
$sql = "
    SELECT d.id, d.tipo, p.nombres, p.apellidos, p.cedula, c.nombre as curso_nombre
    FROM documentos d
    INNER JOIN participantes p ON d.participante_id = p.id
    INNER JOIN cursos c ON d.curso_id = c.id
    WHERE d.tipo = 'certificado'
    ORDER BY d.id DESC
    LIMIT 5
";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID Doc</th><th>Participante</th><th>Cédula</th><th>Curso</th><th>Enlace de Prueba</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nombres'] . " " . $row['apellidos'] . "</td>";
        echo "<td>" . $row['cedula'] . "</td>";
        echo "<td>" . $row['curso_nombre'] . "</td>";
        echo "<td><a href='tcpdf-certificate.php?id=" . $row['id'] . "' target='_blank'>Probar Descarga</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No se encontraron documentos.</p>";
}

echo "<h2>✅ Proceso Completado</h2>";
echo "<p>Las cédulas han sido verificadas y corregidas automáticamente donde fue posible.</p>";
?>
