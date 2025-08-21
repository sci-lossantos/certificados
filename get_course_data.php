<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Configurar headers para JSON y evitar cache
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Permitir acceso a roles que pueden editar cursos
$auth->requireRole(['Escuela', 'Director de Escuela', 'Administrador General']);

// Registrar la solicitud para debugging
error_log("get_course_data.php - Request: " . json_encode($_GET));

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$course_id) {
    echo json_encode(['success' => false, 'message' => 'ID de curso no válido']);
    exit();
}

try {
    // Obtener datos completos del curso
    $query = "SELECT c.*, 
                     e.nombre as escuela_nombre,
                     CONCAT(u.nombres, ' ', u.apellidos) as coordinador_nombre
              FROM cursos c 
              LEFT JOIN escuelas e ON c.escuela_id = e.id 
              LEFT JOIN usuarios u ON c.coordinador_id = u.id 
              WHERE c.id = ? AND c.activo = 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$course_id]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log para debugging
    error_log("get_course_data.php - Query result: " . json_encode($curso));
    
    if ($curso) {
        // Formatear fechas para inputs HTML
        if (!empty($curso['fecha_inicio'])) {
            $curso['fecha_inicio'] = date('Y-m-d', strtotime($curso['fecha_inicio']));
        }
        if (!empty($curso['fecha_fin'])) {
            $curso['fecha_fin'] = date('Y-m-d', strtotime($curso['fecha_fin']));
        }
        
        // Asegurar que todos los campos tengan valores
        $curso['descripcion'] = $curso['descripcion'] ?? '';
        $curso['contenido_tematico'] = $curso['contenido_tematico'] ?? '';
        $curso['duracion_horas'] = $curso['duracion_horas'] ?? '';
        
        echo json_encode(['success' => true, 'curso' => $curso]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Curso no encontrado o inactivo']);
    }
} catch (Exception $e) {
    error_log("Error en get_course_data.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al obtener datos del curso: ' . $e->getMessage()]);
}
?>
