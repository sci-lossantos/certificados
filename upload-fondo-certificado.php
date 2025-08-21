<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Solo administradores, directores y usuarios de escuela pueden subir fondos
$auth->requireRole(['Administrador General', 'Director de Escuela', 'Escuela']);

header('Content-Type: application/json');

// Verificar que es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que se envió un archivo
if (!isset($_FILES['fondo_certificado']) || $_FILES['fondo_certificado']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo válido']);
    exit;
}

$escuela_id = $_POST['escuela_id'] ?? null;
$pagina = $_POST['pagina'] ?? '1'; // 1 o 2

if (!$escuela_id) {
    echo json_encode(['success' => false, 'message' => 'ID de escuela no proporcionado']);
    exit;
}

// Verificar permisos de la escuela
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

$archivo = $_FILES['fondo_certificado'];

// Validar tipo de archivo
$tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
$extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

$tipo_archivo = $archivo['type'];
$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

if (!in_array($tipo_archivo, $tipos_permitidos) || !in_array($extension, $extensiones_permitidas)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo se permiten: JPG, PNG, GIF, PDF']);
    exit;
}

// Validar tamaño (máximo 10MB)
if ($archivo['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 10MB']);
    exit;
}

// Crear directorio si no existe
$directorio_fondos = 'uploads/fondos_certificados/';
if (!is_dir($directorio_fondos)) {
    if (!mkdir($directorio_fondos, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'No se pudo crear el directorio de fondos']);
        exit;
    }
}

// Generar nombre único para el archivo
$nombre_archivo = 'fondo_escuela_' . $escuela_id . '_pagina_' . $pagina . '_' . time() . '.' . $extension;
$ruta_completa = $directorio_fondos . $nombre_archivo;

// Mover archivo subido
if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']);
    exit;
}

try {
    // Actualizar configuración en base de datos
    $campo_imagen = ($pagina == '2') ? 'imagen_fondo_pagina2' : 'imagen_fondo_pagina1';
    
    // Obtener configuración actual para eliminar archivo anterior si existe
    $query_config = "SELECT $campo_imagen FROM configuracion_certificados WHERE escuela_id = ?";
    $stmt_config = $db->prepare($query_config);
    $stmt_config->execute([$escuela_id]);
    $config_actual = $stmt_config->fetch();
    
    // Eliminar archivo anterior si existe
    if ($config_actual && !empty($config_actual[$campo_imagen]) && file_exists($config_actual[$campo_imagen])) {
        unlink($config_actual[$campo_imagen]);
    }
    
    // Actualizar o insertar configuración
    $query_update = "UPDATE configuracion_certificados SET $campo_imagen = ?, usar_imagen_fondo = 1 WHERE escuela_id = ?";
    $stmt_update = $db->prepare($query_update);
    $stmt_update->execute([$ruta_completa, $escuela_id]);
    
    // Si no se actualizó ninguna fila, insertar nueva configuración
    if ($stmt_update->rowCount() == 0) {
        $query_insert = "INSERT INTO configuracion_certificados (escuela_id, $campo_imagen, usar_imagen_fondo) VALUES (?, ?, 1)";
        $stmt_insert = $db->prepare($query_insert);
        $stmt_insert->execute([$escuela_id, $ruta_completa]);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Imagen de fondo subida exitosamente',
        'ruta_imagen' => $ruta_completa,
        'nombre_archivo' => $nombre_archivo
    ]);
    
} catch (Exception $e) {
    // Si hay error en BD, eliminar archivo subido
    if (file_exists($ruta_completa)) {
        unlink($ruta_completa);
    }
    
    echo json_encode(['success' => false, 'message' => 'Error al guardar en base de datos: ' . $e->getMessage()]);
}
?>
