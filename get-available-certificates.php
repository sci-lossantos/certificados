<?php
// Script para obtener certificados disponibles para prueba
require_once 'participante-auth.php';
require_once 'config/database.php';

header('Content-Type: application/json');

$auth = new ParticipanteAuth();

// Verificar si hay un participante logueado
if (!isset($_SESSION['participante_id'])) {
    echo json_encode(['error' => 'No hay participante logueado']);
    exit();
}

$participante_id = $_SESSION['participante_id'];
$conn = getMySQLiConnection();

// Obtener certificados disponibles
$sql = "
    SELECT 
        d.id,
        d.codigo_unico,
        c.nombre as curso_nombre,
        e.nombre as escuela_nombre,
        d.estado,
        (SELECT COUNT(*) FROM firmas_documentos fd WHERE fd.documento_id = d.id AND fd.es_rechazo = 0) as firmas_completadas
    FROM documentos d
    INNER JOIN cursos c ON d.curso_id = c.id
    INNER JOIN escuelas e ON c.escuela_id = e.id
    WHERE d.participante_id = ? AND d.tipo = 'certificado'
    ORDER BY d.fecha_generacion DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $participante_id);
$stmt->execute();
$result = $stmt->get_result();
$certificados = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'participante_id' => $participante_id,
    'certificados' => $certificados,
    'total' => count($certificados)
]);

$conn->close();
?>
