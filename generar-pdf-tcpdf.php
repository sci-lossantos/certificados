<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'lib/tcpdf-simple.php';

// Verificar que el usuario esté autenticado
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireRole(['Escuela', 'Coordinador', 'Administrador General']);

$documento_id = $_GET['id'] ?? '';

if (!$documento_id) {
    die('ID de documento no válido');
}

try {
    // Obtener información del documento (mismo código que antes)
    $query = "SELECT d.id, d.tipo, d.codigo_unico, d.created_at, d.estado, d.curso_id,
                    c.nombre as curso_nombre, c.numero_registro, c.fecha_inicio, c.fecha_fin,
                    e.id as escuela_id, e.nombre as escuela_nombre,
                    CONCAT(u.nombres, ' ', u.apellidos) as generado_por_nombre
            FROM documentos d 
            JOIN cursos c ON d.curso_id = c.id 
            JOIN escuelas e ON c.escuela_id = e.id
            LEFT JOIN usuarios u ON d.generado_por = u.id
            WHERE d.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$documento_id]);
    $documento = $stmt->fetch();
    
    if (!$documento) {
        die('Documento no encontrado');
    }
    
    // Obtener información institucional de la escuela
    $query_escuela = "SELECT * FROM escuelas WHERE id = ?";
    $stmt_escuela = $db->prepare($query_escuela);
    $stmt_escuela->execute([$documento['escuela_id']]);
    $escuela_info = $stmt_escuela->fetch();
    
    // Obtener participantes si es un directorio
    $participantes = [];
    if ($documento['tipo'] === 'directorio') {
        $query_participantes = "SELECT p.id, p.nombres, p.apellidos, p.cedula, p.institucion, p.email, p.telefono, p.fotografia
                               FROM participantes p 
                               JOIN matriculas m ON p.id = m.participante_id 
                               WHERE m.curso_id = ?
                               ORDER BY p.apellidos, p.nombres";
        $stmt_participantes = $db->prepare($query_participantes);
        $stmt_participantes->execute([$documento['curso_id']]);
        $participantes = $stmt_participantes->fetchAll();
    }
    
} catch (PDOException $e) {
    die('Error al obtener el documento: ' . $e->getMessage());
}

// Generar nombre de archivo
function limpiarNombreArchivo($texto) {
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    $texto = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $texto);
    $texto = preg_replace('/-+/', '-', $texto);
    $texto = trim($texto, '-');
    return $texto;
}

$tipo_documento = '';
switch ($documento['tipo']) {
    case 'acta': $tipo_documento = 'ACTA'; break;
    case 'informe': $tipo_documento = 'INFORME'; break;
    case 'certificado': $tipo_documento = 'CERTIFICADO'; break;
    case 'directorio': $tipo_documento = 'DIRECTORIO'; break;
    default: $tipo_documento = 'DOCUMENTO'; break;
}

$nombre_curso_limpio = limpiarNombreArchivo($documento['curso_nombre']);
$numero_registro = limpiarNombreArchivo($documento['numero_registro']);
$fecha_actual = date('Y-m-d');

$nombre_archivo = $tipo_documento . '_' . $nombre_curso_limpio . '_' . $numero_registro . '_' . $fecha_actual . '.pdf';

// Crear PDF
$pdf = new SimpleTCPDF($nombre_archivo);

// Agregar contenido según el tipo de documento
if ($documento['tipo'] === 'directorio') {
    // Portada
    $pdf->addText(strtoupper($escuela_info['nombre'] ?? 'ESCUELA'), 50, 750, 16, 'B');
    $pdf->addText('DIRECTORIO DE PARTICIPANTES', 50, 700, 20, 'B');
    $pdf->addText(strtoupper($documento['curso_nombre']), 50, 650, 14, 'B');
    $pdf->addText('REGISTRO: ' . $documento['numero_registro'], 50, 620, 12);
    $pdf->addText('Del ' . date('d/m/Y', strtotime($documento['fecha_inicio'])) . 
                  ' al ' . date('d/m/Y', strtotime($documento['fecha_fin'])), 50, 590, 12);
    
    // Nueva página para participantes
    $pdf->addPage();
    $pdf->addText('LISTADO DE PARTICIPANTES', 50, 750, 16, 'B');
    
    $y_position = 700;
    foreach ($participantes as $index => $participante) {
        $pdf->addText(($index + 1) . '. ' . strtoupper($participante['nombres'] . ' ' . $participante['apellidos']), 50, $y_position, 12, 'B');
        $pdf->addText('   Cedula: ' . $participante['cedula'], 50, $y_position - 15, 10);
        $pdf->addText('   Institucion: ' . strtoupper($participante['institucion'] ?? 'NO ESPECIFICADA'), 50, $y_position - 30, 10);
        $pdf->addText('   Email: ' . ($participante['email'] ?? ''), 50, $y_position - 45, 10);
        $pdf->addText('   Telefono: ' . ($participante['telefono'] ?? ''), 50, $y_position - 60, 10);
        
        $y_position -= 80;
        
        if ($y_position < 100) {
            $pdf->addPage();
            $y_position = 750;
        }
    }
    
} elseif ($documento['tipo'] === 'certificado') {
    $pdf->addText(strtoupper($escuela_info['nombre'] ?? 'ESCUELA'), 50, 750, 16, 'B');
    $pdf->addText('CERTIFICADO DE PARTICIPACION', 50, 650, 20, 'B');
    $pdf->addText('Se certifica que', 50, 600, 14);
    
    $nombre_participante = isset($documento['participante_nombre']) ? 
                          strtoupper($documento['participante_nombre']) : 
                          'TODOS LOS PARTICIPANTES';
    $pdf->addText($nombre_participante, 50, 570, 18, 'B');
    
    $texto_participacion = isset($documento['participante_nombre']) ? 'Ha participado' : 'Han participado';
    $pdf->addText($texto_participacion . ' satisfactoriamente en el curso:', 50, 530, 12);
    $pdf->addText('"' . strtoupper($documento['curso_nombre']) . '"', 50, 500, 16, 'B');
    $pdf->addText('Registro: ' . $documento['numero_registro'], 50, 470, 12);
    $pdf->addText('Realizado del ' . date('d/m/Y', strtotime($documento['fecha_inicio'])) . 
                  ' al ' . date('d/m/Y', strtotime($documento['fecha_fin'])), 50, 440, 12);
    $pdf->addText('Dado en ' . date('d \d\e F \d\e Y'), 50, 380, 12);
    
} else {
    // Otros documentos
    $pdf->addText(strtoupper($escuela_info['nombre'] ?? 'ESCUELA'), 50, 750, 16, 'B');
    
    $titulo = '';
    switch ($documento['tipo']) {
        case 'acta': $titulo = 'ACTA DE CURSO'; break;
        case 'informe': $titulo = 'INFORME DE CURSO'; break;
        default: $titulo = 'DOCUMENTO'; break;
    }
    
    $pdf->addText($titulo, 50, 700, 20, 'B');
    $pdf->addText('Curso: ' . $documento['curso_nombre'], 50, 650, 12, 'B');
    $pdf->addText('Registro: ' . $documento['numero_registro'], 50, 630, 12);
    $pdf->addText('Fechas: Del ' . date('d/m/Y', strtotime($documento['fecha_inicio'])) . 
                  ' al ' . date('d/m/Y', strtotime($documento['fecha_fin'])), 50, 610, 12);
    $pdf->addText('Generado: ' . date('d/m/Y H:i', strtotime($documento['created_at'])), 50, 590, 12);
    $pdf->addText('Generado por: ' . $documento['generado_por_nombre'], 50, 570, 12);
    
    $pdf->addText('Contenido del ' . ucfirst($documento['tipo']) . ':', 50, 530, 14, 'B');
    $pdf->addText('Este documento contiene la informacion oficial del curso mencionado.', 50, 510, 12);
    $pdf->addText('Para mas detalles, consulte el sistema ESIBOC.', 50, 490, 12);
}

// Generar y descargar PDF
$pdf->output($nombre_archivo, 'D');
?>
