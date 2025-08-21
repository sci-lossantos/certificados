<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Verificar autenticación
if (!$auth->isLoggedIn() || $_SESSION['user_role'] !== 'Coordinador') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Obtener datos de la solicitud
$matricula_id = $_POST['matricula_id'] ?? 0;
$calificacion = $_POST['calificacion'] ?? '';
$curso_id = $_POST['curso_id'] ?? 0;

if (!$matricula_id || $calificacion === '' || !$curso_id) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

try {
    // Validar que el curso pertenece al coordinador
    $query_verify = "SELECT c.id FROM cursos c 
                    JOIN matriculas m ON m.curso_id = c.id 
                    WHERE m.id = ? AND c.id = ? AND c.coordinador_id = ?";
    $stmt_verify = $db->prepare($query_verify);
    $stmt_verify->execute([$matricula_id, $curso_id, $_SESSION['user_id']]);
    
    if (!$stmt_verify->fetch()) {
        throw new Exception('No tienes permisos para calificar este participante');
    }
    
    // Validar calificación
    $calificacion = floatval($calificacion);
    if ($calificacion < 0 || $calificacion > 5) {
        throw new Exception('La calificación debe estar entre 0 y 5');
    }
    
    // Determinar si está aprobado
    $aprobado = $calificacion >= 3.0 ? 1 : 0;
    
    // Actualizar calificación
    $query_update = "UPDATE matriculas SET calificacion = ?, aprobado = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt_update = $db->prepare($query_update);
    $stmt_update->execute([$calificacion, $aprobado, $matricula_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Calificación guardada', 
        'data' => [
            'calificacion' => $calificacion,
            'aprobado' => $aprobado
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
