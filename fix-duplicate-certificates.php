<?php
require_once 'config/database.php';

echo "<h2>Eliminando certificados duplicados y previniendo futuros duplicados</h2>";

try {
    $conn = getMySQLiConnection();
    
    // 1. Identificar certificados duplicados
    echo "<h3>1. Identificando certificados duplicados:</h3>";
    
    $duplicates_sql = "
        SELECT 
            participante_id, 
            curso_id, 
            COUNT(*) as total_certificados,
            GROUP_CONCAT(id ORDER BY fecha_generacion ASC) as ids,
            MIN(fecha_generacion) as primera_fecha
        FROM documentos 
        WHERE tipo = 'certificado' 
        GROUP BY participante_id, curso_id 
        HAVING COUNT(*) > 1
    ";
    
    $result = $conn->query($duplicates_sql);
    $duplicates = $result->fetch_all(MYSQLI_ASSOC);
    
    if (empty($duplicates)) {
        echo "<p style='color: green;'>✓ No se encontraron certificados duplicados</p>";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>Participante ID</th><th>Curso ID</th><th>Total Certificados</th><th>IDs de Documentos</th><th>Primera Fecha</th></tr>";
        
        foreach ($duplicates as $dup) {
            echo "<tr>";
            echo "<td>" . $dup['participante_id'] . "</td>";
            echo "<td>" . $dup['curso_id'] . "</td>";
            echo "<td>" . $dup['total_certificados'] . "</td>";
            echo "<td>" . $dup['ids'] . "</td>";
            echo "<td>" . $dup['primera_fecha'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 2. Eliminar duplicados (mantener el más antiguo)
        echo "<h3>2. Eliminando certificados duplicados:</h3>";
        
        foreach ($duplicates as $dup) {
            $ids = explode(',', $dup['ids']);
            $keep_id = array_shift($ids); // Mantener el primero (más antiguo)
            $delete_ids = $ids; // Eliminar el resto
            
            if (!empty($delete_ids)) {
                $delete_ids_str = implode(',', $delete_ids);
                
                // Eliminar firmas de los documentos duplicados
                $delete_firmas_sql = "DELETE FROM firmas_documentos WHERE documento_id IN ($delete_ids_str)";
                $conn->query($delete_firmas_sql);
                
                // Eliminar los documentos duplicados
                $delete_docs_sql = "DELETE FROM documentos WHERE id IN ($delete_ids_str)";
                if ($conn->query($delete_docs_sql)) {
                    echo "<p style='color: green;'>✓ Eliminados " . count($delete_ids) . " certificados duplicados para participante " . $dup['participante_id'] . " curso " . $dup['curso_id'] . "</p>";
                } else {
                    echo "<p style='color: red;'>✗ Error eliminando duplicados: " . $conn->error . "</p>";
                }
            }
        }
    }
    
    // 3. Crear índice único para prevenir futuros duplicados
    echo "<h3>3. Creando índice único para prevenir duplicados:</h3>";
    
    // Verificar si ya existe el índice
    $check_index_sql = "SHOW INDEX FROM documentos WHERE Key_name = 'unique_participant_course_certificate'";
    $index_result = $conn->query($check_index_sql);
    
    if ($index_result->num_rows == 0) {
        $create_index_sql = "
            ALTER TABLE documentos 
            ADD UNIQUE INDEX unique_participant_course_certificate (participante_id, curso_id, tipo)
        ";
        
        if ($conn->query($create_index_sql)) {
            echo "<p style='color: green;'>✓ Índice único creado exitosamente</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creando índice único: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ El índice único ya existe</p>";
    }
    
    // 4. Verificar estado final
    echo "<h3>4. Estado final de certificados:</h3>";
    
    $final_check_sql = "
        SELECT 
            p.nombres, 
            p.apellidos, 
            c.nombre as curso_nombre,
            COUNT(d.id) as total_certificados
        FROM documentos d
        JOIN participantes p ON d.participante_id = p.id
        JOIN cursos c ON d.curso_id = c.id
        WHERE d.tipo = 'certificado'
        GROUP BY d.participante_id, d.curso_id
        ORDER BY p.apellidos, p.nombres
    ";
    
    $result = $conn->query($final_check_sql);
    
    echo "<table border='1'>";
    echo "<tr><th>Participante</th><th>Curso</th><th>Certificados</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $color = $row['total_certificados'] > 1 ? 'color: red;' : 'color: green;';
        echo "<tr style='$color'>";
        echo "<td>" . $row['nombres'] . " " . $row['apellidos'] . "</td>";
        echo "<td>" . $row['curso_nombre'] . "</td>";
        echo "<td>" . $row['total_certificados'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Proceso completado exitosamente</h3>";
    echo "<p><strong>Nota:</strong> Ahora el sistema no permitirá generar certificados duplicados para el mismo participante y curso.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
