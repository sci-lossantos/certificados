<?php
header('Content-Type: application/json');
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $curso_id = $_GET['curso_id'] ?? '';
    
    if (!$curso_id) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de curso no proporcionado',
            'participantes' => []
        ]);
        exit;
    }
    
    // Obtener participantes matriculados en el curso específico
    $query = "SELECT DISTINCT p.id, p.nombres, p.apellidos, p.cedula, p.email
              FROM participantes p
              INNER JOIN matriculas m ON p.id = m.participante_id
              WHERE m.curso_id = ? AND p.activo = 1
              ORDER BY p.nombres, p.apellidos";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$curso_id]);
    $participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Participantes cargados correctamente',
        'participantes' => $participantes,
        'total' => count($participantes)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar participantes: ' . $e->getMessage(),
        'participantes' => []
    ]);
}
?>
