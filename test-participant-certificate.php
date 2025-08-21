<?php
// Script para probar la descarga de certificados del participante
require_once 'participante-auth.php';
require_once 'config/database.php';

echo "<h1>Test de Certificados del Participante</h1>";

// Verificar si hay sesión activa
if (!isset($_SESSION['participante_id'])) {
    echo "<p style='color: red;'>No hay sesión activa. <a href='participante-login.php'>Iniciar sesión</a></p>";
    exit();
}

$participante_id = $_SESSION['participante_id'];
echo "<p>Participante ID: " . $participante_id . "</p>";

// Obtener conexión
$conn = getMySQLiConnection();

// Verificar datos del participante
$participante_sql = "SELECT * FROM participantes WHERE id = ?";
$stmt = $conn->prepare($participante_sql);
$stmt->bind_param("i", $participante_id);
$stmt->execute();
$participante = $stmt->get_result()->fetch_assoc();

if ($participante) {
    echo "<h2>Datos del Participante:</h2>";
    echo "<p>Nombre: " . $participante['nombres'] . " " . $participante['apellidos'] . "</p>";
    echo "<p>Cédula: " . $participante['cedula'] . "</p>";
} else {
    echo "<p style='color: red;'>No se encontró el participante</p>";
    exit();
}

// Obtener certificados disponibles
$certificados_sql = "
    SELECT 
        d.id,
        d.tipo,
        d.estado,
        d.codigo_unico,
        d.fecha_generacion,
        c.nombre as curso_nombre,
        c.duracion_horas,
        e.nombre as escuela_nombre,
        (SELECT COUNT(*) FROM firmas_documentos fd WHERE fd.documento_id = d.id AND fd.es_rechazo = 0) as firmas_completadas
    FROM documentos d
    INNER JOIN cursos c ON d.curso_id = c.id
    INNER JOIN escuelas e ON c.escuela_id = e.id
    WHERE d.participante_id = ? AND d.tipo = 'certificado'
    ORDER BY d.fecha_generacion DESC
";

$stmt = $conn->prepare($certificados_sql);
$stmt->bind_param("i", $participante_id);
$stmt->execute();
$result = $stmt->get_result();
$certificados = $result->fetch_all(MYSQLI_ASSOC);

echo "<h2>Certificados Disponibles:</h2>";

if (empty($certificados)) {
    echo "<p>No hay certificados disponibles para este participante.</p>";
    
    // Verificar si hay matrículas
    $matriculas_sql = "SELECT COUNT(*) as total FROM matriculas WHERE participante_id = ?";
    $stmt = $conn->prepare($matriculas_sql);
    $stmt->bind_param("i", $participante_id);
    $stmt->execute();
    $matriculas_count = $stmt->get_result()->fetch_assoc()['total'];
    
    echo "<p>Matrículas encontradas: " . $matriculas_count . "</p>";
    
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Curso</th><th>Escuela</th><th>Estado</th><th>Firmas</th><th>Acciones</th></tr>";
    
    foreach ($certificados as $cert) {
        echo "<tr>";
        echo "<td>" . $cert['id'] . "</td>";
        echo "<td>" . htmlspecialchars($cert['curso_nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($cert['escuela_nombre']) . "</td>";
        echo "<td>" . $cert['estado'] . "</td>";
        echo "<td>" . $cert['firmas_completadas'] . "/4</td>";
        echo "<td>";
        
        // Verificar si tiene todas las firmas
        if ($cert['firmas_completadas'] >= 4) {
            echo "<a href='tcpdf-certificate-configurable.php?id=" . $cert['id'] . "' target='_blank' style='color: green;'>✓ Descargar</a>";
        } else {
            echo "<span style='color: orange;'>⏳ En proceso</span>";
        }
        
        echo " | <a href='debug-certificate-config.php?id=" . $cert['id'] . "' target='_blank'>Debug</a>";
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Verificar configuración de certificados
echo "<h2>Configuración de Certificados:</h2>";
$config_sql = "SELECT * FROM configuracion_certificados";
$result = $conn->query($config_sql);

if ($result && $result->num_rows > 0) {
    echo "<p>Configuraciones encontradas: " . $result->num_rows . "</p>";
    while ($config = $result->fetch_assoc()) {
        echo "<p>Escuela ID: " . $config['escuela_id'] . " - Título: " . htmlspecialchars($config['titulo_principal']) . "</p>";
    }
} else {
    echo "<p style='color: red;'>No hay configuraciones de certificados. <a href='fix-certificate-config.php'>Crear configuraciones</a></p>";
}

echo "<hr>";
echo "<p><a href='participante-dashboard.php'>← Volver al Dashboard</a></p>";
?>
