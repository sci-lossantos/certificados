<?php
// Script para debuggear la configuración de certificados
require_once 'participante-auth.php';
require_once 'config/database.php';

$auth = new ParticipanteAuth();
$auth->requireLogin();

if (!isset($_GET['id'])) {
    die("Proporciona un ID de documento: ?id=X");
}

$documento_id = intval($_GET['id']);
$participante_id = $_SESSION['participante_id'];

echo "<h1>Debug Configuración de Certificados</h1>";

// Obtener conexión a la base de datos
$conn = getMySQLiConnection();

// Verificar documento
$sql = "
    SELECT d.*, c.nombre as curso_nombre, c.duracion_horas, c.contenido_tematico, c.descripcion,
           p.nombres, p.apellidos, p.cedula,
           e.nombre as escuela_nombre, e.direccion as escuela_direccion, e.id as escuela_id
    FROM documentos d
    INNER JOIN cursos c ON d.curso_id = c.id
    INNER JOIN participantes p ON d.participante_id = p.id
    INNER JOIN escuelas e ON c.escuela_id = e.id
    WHERE d.id = ? AND d.participante_id = ? AND d.tipo = 'certificado'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $documento_id, $participante_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Error: No se encontró el certificado");
}

$documento = $result->fetch_assoc();

echo "<h2>Información del Documento</h2>";
echo "<pre>" . print_r($documento, true) . "</pre>";

// Verificar configuración
$config_sql = "SELECT * FROM configuracion_certificados WHERE escuela_id = ?";
$stmt_config = $conn->prepare($config_sql);
$stmt_config->bind_param("i", $documento['escuela_id']);
$stmt_config->execute();
$config_result = $stmt_config->get_result();
$config = $config_result->fetch_assoc();

echo "<h2>Configuración del Certificado</h2>";
if ($config) {
    echo "<pre>" . print_r($config, true) . "</pre>";
} else {
    echo "<p style='color: red;'>NO SE ENCONTRÓ CONFIGURACIÓN PARA LA ESCUELA ID: " . $documento['escuela_id'] . "</p>";
    
    // Crear configuración por defecto
    $insert_sql = "INSERT INTO configuracion_certificados (escuela_id) VALUES (?)";
    $stmt_insert = $conn->prepare($insert_sql);
    $stmt_insert->bind_param("i", $documento['escuela_id']);
    
    if ($stmt_insert->execute()) {
        echo "<p style='color: green;'>Configuración por defecto creada. Recarga la página.</p>";
    } else {
        echo "<p style='color: red;'>Error al crear configuración: " . $conn->error . "</p>";
    }
}

// Verificar tabla de configuración
echo "<h2>Verificar Tabla configuracion_certificados</h2>";
$check_table = "SHOW TABLES LIKE 'configuracion_certificados'";
$result_table = $conn->query($check_table);

if ($result_table->num_rows > 0) {
    echo "<p style='color: green;'>✓ Tabla configuracion_certificados existe</p>";
    
    // Mostrar estructura
    $describe = "DESCRIBE configuracion_certificados";
    $result_desc = $conn->query($describe);
    echo "<h3>Estructura de la tabla:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result_desc->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p style='color: red;'>✗ Tabla configuracion_certificados NO existe</p>";
    echo "<p>Ejecuta el script: scripts/46-create-certificate-config-table.sql</p>";
}

echo "<hr>";
echo "<a href='tcpdf-certificate-configurable.php?id=" . $documento_id . "'>Probar Certificado Configurable</a>";
?>
