<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Solo administradores, directores y usuarios de escuela pueden eliminar fondos
$auth->requireRole(['Administrador General', 'Director de Escuela', 'Escuela']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$escuela_id = $_POST['escuela_id'] ?? null;
$pagina = $_POST['pagina'] ?? '1';

if (!$escuela_id) {
    echo json_encode(['success' => false, 'message' => 'ID de escuela no proporcionado']);
    exit;
}

// Verificar permisos (mismo código que en upload-fondo-certificado.php)
$puede_editar = false;

if ($auth->hasRole('Administrador General')) {
    $puede_editar = true;
} elseif ($auth->hasRole('Director de Escuela')) {
    $query_director = "SELECT e.id FROM escuelas e 
                      JOIN usuarios u ON e.director_id = u.id 
                      WHERE u.id = ? AND e.id = ?";
    $stmt = $db->prepare($query_director);
    $stmt->execute([$_SESSION['user_id'], $escuela_id]);
    $puede_editar = $stmt->fetch() !== false;
} elseif ($auth->hasRole('Escuela')) {
    $query_escuela = "SELECT id FROM usuarios WHERE id = ? AND escuela_id = ?";
    $stmt = $db->prepare($query_escuela);
    $stmt->execute([$_SESSION['user_id'], $escuela_id]);
    $puede_editar = $stmt->fetch() !== false;
}

if (!$puede_editar) {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar esta escuela']);
    exit;
}

try {
    $campo_imagen = ($pagina == '2') ? 'imagen_fondo_pagina2' : 'imagen_fondo_pagina1';
    
    // Obtener ruta del archivo actual
    $query_config = "SELECT $campo_imagen FROM configuracion_certificados WHERE escuela_id = ?";
    $stmt_config = $db->prepare($query_config);
    $stmt_config->execute([$escuela_id]);
    $config_actual = $stmt_config->fetch();
    
    if ($config_actual && !empty($config_actual[$campo_imagen])) {
        // Eliminar archivo físico
        if (file_exists($config_actual[$campo_imagen])) {
            unlink($config_actual[$campo_imagen]);
        }
        
        // Limpiar campo en base de datos
        $query_update = "UPDATE configuracion_certificados SET $campo_imagen = NULL WHERE escuela_id = ?";
        $stmt_update = $db->prepare($query_update);
        $stmt_update->execute([$escuela_id]);
        
        echo json_encode(['success' => true, 'message' => 'Imagen de fondo eliminada exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No hay imagen de fondo para eliminar']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()]);
}
?>
