<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/document-workflow.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$workflow = new DocumentWorkflow($db);

// Simular que somos Director de Escuela
$_SESSION['user_role'] = 'Director de Escuela';
$_SESSION['user_id'] = 1; // Ajusta según tu ID de Director

echo "<h2>Debug: Acceso Director de Escuela</h2>";

// 1. Verificar escuela asignada
$escuela_id = $auth->getUserEscuelaId();
echo "<p><strong>Escuela ID asignada:</strong> " . ($escuela_id ?: 'NINGUNA') . "</p>";

// 2. Verificar documentos pendientes
$documentos_pendientes = $workflow->getPendingDocuments('Director de Escuela');
echo "<p><strong>Documentos pendientes encontrados:</strong> " . count($documentos_pendientes) . "</p>";

if (count($documentos_pendientes) > 0) {
    echo "<ul>";
    foreach ($documentos_pendientes as $doc) {
        echo "<li>ID: {$doc['id']}, Tipo: {$doc['tipo']}, Estado: {$doc['estado']}, Curso: {$doc['curso_nombre']}</li>";
    }
    echo "</ul>";
}

// 3. Verificar documentos de la escuela
if ($escuela_id) {
    $query = "SELECT d.*, c.nombre as curso_nombre, e.nombre as escuela_nombre
              FROM documentos d 
              JOIN cursos c ON d.curso_id = c.id 
              JOIN escuelas e ON c.escuela_id = e.id
              WHERE e.id = ?
              ORDER BY d.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$escuela_id]);
    $documentos_escuela = $stmt->fetchAll();
    
    echo "<p><strong>Documentos de la escuela:</strong> " . count($documentos_escuela) . "</p>";
    if (count($documentos_escuela) > 0) {
        echo "<ul>";
        foreach ($documentos_escuela as $doc) {
            $puede_procesar = $workflow->canProcessDocument($doc['id'], 'Director de Escuela');
            echo "<li>ID: {$doc['id']}, Tipo: {$doc['tipo']}, Estado: {$doc['estado']}, Puede procesar: " . ($puede_procesar ? 'SÍ' : 'NO') . "</li>";
        }
        echo "</ul>";
    }
}

// 4. Verificar estados que debería procesar
echo "<p><strong>Estados que Director de Escuela debería procesar:</strong></p>";
echo "<ul>";
echo "<li>firmado_coordinador (para firmar actas y certificados)</li>";
echo "<li>revisado_directorio_coordinador (para revisar directorios)</li>";
echo "</ul>";
?>
