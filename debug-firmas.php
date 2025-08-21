<?php
// Script para debuggear las firmas de un documento
require_once 'config/database.php';

if (!isset($_GET['doc_id'])) {
    die("Proporciona un ID de documento: ?doc_id=X");
}

$documento_id = intval($_GET['doc_id']);
$conn = getMySQLiConnection();

echo "<h2>Debug de Firmas para Documento ID: $documento_id</h2>";

// Verificar el documento
$doc_sql = "SELECT d.*, c.nombre as curso_nombre, p.nombres, p.apellidos FROM documentos d 
            INNER JOIN cursos c ON d.curso_id = c.id 
            INNER JOIN participantes p ON d.participante_id = p.id 
            WHERE d.id = ?";
$stmt = $conn->prepare($doc_sql);
$stmt->bind_param("i", $documento_id);
$stmt->execute();
$doc_result = $stmt->get_result();

if ($doc_result->num_rows === 0) {
    die("Documento no encontrado");
}

$documento = $doc_result->fetch_assoc();
echo "<h3>Documento: {$documento['curso_nombre']} - {$documento['nombres']} {$documento['apellidos']}</h3>";

// Obtener todas las firmas
$firmas_sql = "
    SELECT fd.*, 
           CONCAT(u.nombres, ' ', u.apellidos) as firmante_nombre, 
           r.nombre as firmante_cargo,
           u.id as usuario_id,
           r.id as rol_id
    FROM firmas_documentos fd
    INNER JOIN usuarios u ON fd.usuario_id = u.id
    INNER JOIN roles r ON u.rol_id = r.id
    WHERE fd.documento_id = ? AND fd.es_rechazo = 0
    ORDER BY fd.fecha_firma ASC
";

$stmt = $conn->prepare($firmas_sql);
$stmt->bind_param("i", $documento_id);
$stmt->execute();
$firmas_result = $stmt->get_result();
$firmas = $firmas_result->fetch_all(MYSQLI_ASSOC);

echo "<h3>Firmas encontradas: " . count($firmas) . "</h3>";

if (count($firmas) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Usuario ID</th><th>Nombre</th><th>Cargo</th><th>Rol ID</th><th>Fecha Firma</th><th>Clasificación</th></tr>";
    
    foreach ($firmas as $firma) {
        $cargo_lower = strtolower($firma['firmante_cargo']);
        $clasificacion = '';
        
        if (strpos($cargo_lower, 'administrador general') !== false || strpos($cargo_lower, 'director nacional') !== false) {
            $clasificacion = 'DIRECTOR NACIONAL';
        } elseif (strpos($cargo_lower, 'director de escuela') !== false) {
            $clasificacion = 'DIRECTOR DE ESCUELA';
        } elseif (strpos($cargo_lower, 'coordinador') !== false || strpos($cargo_lower, 'escuela') !== false) {
            $clasificacion = 'COORDINADOR';
        } else {
            $clasificacion = 'NO CLASIFICADO';
        }
        
        echo "<tr>";
        echo "<td>{$firma['id']}</td>";
        echo "<td>{$firma['usuario_id']}</td>";
        echo "<td>{$firma['firmante_nombre']}</td>";
        echo "<td>{$firma['firmante_cargo']}</td>";
        echo "<td>{$firma['rol_id']}</td>";
        echo "<td>{$firma['fecha_firma']}</td>";
        echo "<td><strong>$clasificacion</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No se encontraron firmas para este documento.</p>";
}

// Mostrar todos los roles disponibles
echo "<h3>Roles disponibles en el sistema:</h3>";
$roles_sql = "SELECT * FROM roles ORDER BY id";
$roles_result = $conn->query($roles_sql);

echo "<table border='1' style='border-collapse: collapse; width: 50%;'>";
echo "<tr><th>ID</th><th>Nombre</th></tr>";
while ($rol = $roles_result->fetch_assoc()) {
    echo "<tr><td>{$rol['id']}</td><td>{$rol['nombre']}</td></tr>";
}
echo "</table>";

echo "<br><a href='tcpdf-certificate.php?id=$documento_id'>Probar certificado</a>";
?>
