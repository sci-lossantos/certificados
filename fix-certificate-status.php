<?php
require_once 'config/database.php';

function fixCertificateStatus() {
    $conn = getMySQLiConnection();
    $output = [];
    
    try {
        $output[] = "🔍 Verificando y corrigiendo estados de certificados...";
        
        // 1. Verificar certificados con todas las firmas pero no marcados como completados
        $check_query = "
            SELECT d.id, d.estado, d.participante_id, d.curso_id, 
                   p.nombres, p.apellidos, c.nombre as curso_nombre,
                   (SELECT COUNT(*) FROM firmas_documentos fd WHERE fd.documento_id = d.id AND fd.es_rechazo = 0) as firmas_completadas
            FROM documentos d
            JOIN participantes p ON d.participante_id = p.id
            JOIN cursos c ON d.curso_id = c.id
            WHERE d.tipo = 'certificado'
            ORDER BY d.id DESC
        ";
        
        $result = $conn->query($check_query);
        
        if ($result) {
            $output[] = "📋 Certificados encontrados: " . $result->num_rows;
            
            $to_update = [];
            $duplicates = [];
            $by_participant_course = [];
            
            while ($row = $result->fetch_assoc()) {
                $key = $row['participante_id'] . '-' . $row['curso_id'];
                
                // Registrar para detectar duplicados
                if (!isset($by_participant_course[$key])) {
                    $by_participant_course[$key] = [];
                }
                $by_participant_course[$key][] = $row;
                
                // Verificar si necesita actualización de estado
                if ($row['firmas_completadas'] >= 4 && $row['estado'] != 'completado') {
                    $to_update[] = $row;
                }
            }
            
            // Encontrar duplicados
            foreach ($by_participant_course as $key => $certs) {
                if (count($certs) > 1) {
                    // Ordenar por ID para mantener el más reciente
                    usort($certs, function($a, $b) {
                        return $b['id'] - $a['id'];
                    });
                    
                    // El primero es el más reciente, los demás son duplicados
                    for ($i = 1; $i < count($certs); $i++) {
                        $duplicates[] = $certs[$i];
                    }
                }
            }
            
            // Actualizar estados
            if (count($to_update) > 0) {
                $output[] = "🔄 Actualizando " . count($to_update) . " certificados a estado 'completado'...";
                
                foreach ($to_update as $cert) {
                    $update_sql = "UPDATE documentos SET estado = 'completado', fecha_completado = NOW() WHERE id = ?";
                    $stmt = $conn->prepare($update_sql);
                    $stmt->bind_param("i", $cert['id']);
                    
                    if ($stmt->execute()) {
                        $output[] = "✅ Certificado ID " . $cert['id'] . " actualizado para " . $cert['nombres'] . " " . $cert['apellidos'] . " - " . $cert['curso_nombre'];
                    } else {
                        $output[] = "❌ Error actualizando certificado ID " . $cert['id'] . ": " . $stmt->error;
                    }
                }
            } else {
                $output[] = "✅ No hay certificados que necesiten actualización de estado";
            }
            
            // Eliminar duplicados
            if (count($duplicates) > 0) {
                $output[] = "🗑️ Eliminando " . count($duplicates) . " certificados duplicados...";
                
                foreach ($duplicates as $dup) {
                    // Primero eliminar firmas asociadas
                    $delete_firmas = "DELETE FROM firmas_documentos WHERE documento_id = ?";
                    $stmt = $conn->prepare($delete_firmas);
                    $stmt->bind_param("i", $dup['id']);
                    $stmt->execute();
                    
                    // Luego eliminar el documento
                    $delete_doc = "DELETE FROM documentos WHERE id = ?";
                    $stmt = $conn->prepare($delete_doc);
                    $stmt->bind_param("i", $dup['id']);
                    
                    if ($stmt->execute()) {
                        $output[] = "✅ Certificado duplicado ID " . $dup['id'] . " eliminado para " . $dup['nombres'] . " " . $dup['apellidos'] . " - " . $dup['curso_nombre'];
                    } else {
                        $output[] = "❌ Error eliminando certificado ID " . $dup['id'] . ": " . $stmt->error;
                    }
                }
            } else {
                $output[] = "✅ No se encontraron certificados duplicados";
            }
            
            // Crear índice único para prevenir futuros duplicados
            $check_index = "SHOW INDEX FROM documentos WHERE Key_name = 'unique_participante_curso_tipo'";
            $result = $conn->query($check_index);
            
            if ($result->num_rows == 0) {
                $create_index = "ALTER TABLE documentos ADD CONSTRAINT unique_participante_curso_tipo UNIQUE (participante_id, curso_id, tipo)";
                
                if ($conn->query($create_index)) {
                    $output[] = "✅ Índice único creado para prevenir futuros duplicados";
                } else {
                    $output[] = "⚠️ No se pudo crear el índice único: " . $conn->error;
                }
            } else {
                $output[] = "ℹ️ El índice único ya existe";
            }
        } else {
            $output[] = "❌ Error consultando certificados: " . $conn->error;
        }
        
        $output[] = "🎉 ¡Proceso completado!";
        
    } catch (Exception $e) {
        $output[] = "❌ Error: " . $e->getMessage();
    }
    
    return $output;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reparación de Estados de Certificados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .output-line {
            font-family: 'Courier New', monospace;
            margin: 2px 0;
            padding: 2px 5px;
            font-size: 0.9em;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-certificate me-2"></i>Reparación de Estados de Certificados</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Este script corrige los estados de los certificados y elimina duplicados.
                        </div>
                        
                        <div class="bg-dark text-light p-3 rounded" style="max-height: 600px; overflow-y: auto;">
                            <?php
                            $results = fixCertificateStatus();
                            foreach ($results as $line) {
                                $class = 'info';
                                if (strpos($line, '✅') !== false) $class = 'success';
                                elseif (strpos($line, '❌') !== false) $class = 'error';
                                elseif (strpos($line, '⚠️') !== false) $class = 'warning';
                                
                                echo "<div class='output-line $class'>$line</div>";
                            }
                            ?>
                        </div>
                        
                        <div class="mt-3">
                            <a href="participante-dashboard.php" class="btn btn-success btn-lg">
                                <i class="fas fa-user-graduate me-2"></i>Dashboard Participante
                            </a>
                            <a href="documentos.php" class="btn btn-primary ms-2">
                                <i class="fas fa-file-alt me-2"></i>Gestionar Documentos
                            </a>
                            <button onclick="location.reload()" class="btn btn-secondary ms-2">
                                <i class="fas fa-redo me-2"></i>Ejecutar Nuevamente
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
