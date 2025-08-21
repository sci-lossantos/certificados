<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'lib/fpdf.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verificar parámetros
if (!isset($_POST['tipo_documento']) || !isset($_POST['curso_id'])) {
    die('Error: Parámetros faltantes');
}

$tipo_documento = $_POST['tipo_documento'];
$curso_id = intval($_POST['curso_id']);
$participante_id = isset($_POST['participante_id']) ? intval($_POST['participante_id']) : null;

// Obtener conexión a la base de datos
$conn = getMySQLiConnection();

try {
    // Verificar si ya existe un certificado para este participante y curso
    if ($tipo_documento === 'certificado' && $participante_id) {
        $check_existing_sql = "
            SELECT id FROM documentos 
            WHERE tipo = 'certificado' 
            AND curso_id = ? 
            AND participante_id = ?
        ";
        
        $check_stmt = $conn->prepare($check_existing_sql);
        $check_stmt->bind_param("ii", $curso_id, $participante_id);
        $check_stmt->execute();
        $existing_result = $check_stmt->get_result();
        
        if ($existing_result->num_rows > 0) {
            $existing_cert = $existing_result->fetch_assoc();
            // Redirigir al certificado existente en lugar de crear uno nuevo
            header('Location: ver-documento.php?id=' . $existing_cert['id']);
            exit();
        }
    }
    
    // Generar código único
    $codigo_unico = strtoupper($tipo_documento) . '-' . date('Y') . '-' . str_pad($curso_id, 4, '0', STR_PAD_LEFT) . '-' . uniqid();
    
    // Insertar documento en la base de datos
    $insert_sql = "
        INSERT INTO documentos (tipo, curso_id, participante_id, codigo_unico, estado, generado_por, fecha_generacion) 
        VALUES (?, ?, ?, ?, 'generado', ?, NOW())
    ";
    
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("siisi", $tipo_documento, $curso_id, $participante_id, $codigo_unico, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $documento_id = $conn->insert_id;
        
        // Redirigir al documento generado
        header('Location: ver-documento.php?id=' . $documento_id);
        exit();
    } else {
        throw new Exception('Error al generar el documento: ' . $conn->error);
    }
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
