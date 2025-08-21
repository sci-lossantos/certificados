<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>🔧 Creando Matrículas de Prueba</h2>";
    
    // Verificar si ya existen matrículas
    $query_check = "SELECT COUNT(*) as total FROM matriculas";
    $stmt_check = $db->prepare($query_check);
    $stmt_check->execute();
    $existing = $stmt_check->fetch()['total'];
    
    echo "<p>📊 Matrículas existentes: $existing</p>";
    
    if ($existing == 0) {
        echo "<p>➕ Creando matrículas de prueba...</p>";
        
        // Obtener el primer curso disponible
        $query_curso = "SELECT id FROM cursos LIMIT 1";
        $stmt_curso = $db->prepare($query_curso);
        $stmt_curso->execute();
        $curso = $stmt_curso->fetch();
        
        if (!$curso) {
            echo "<p>❌ No hay cursos disponibles. Creando curso de prueba...</p>";
            
            // Crear curso de prueba
            $query_insert_curso = "INSERT INTO cursos (nombre, numero_registro, duracion_horas, escuela_id, activo) 
                                  VALUES (?, ?, ?, ?, ?)";
            $stmt_insert_curso = $db->prepare($query_insert_curso);
            $stmt_insert_curso->execute([
                'DESARROLLO DE CAPACIDADES PARA LA INSTRUCCION DE BOMBEROS',
                '890-2025',
                40,
                1, // Asumiendo que existe escuela con ID 1
                1
            ]);
            $curso_id = $db->lastInsertId();
            echo "<p>✅ Curso creado con ID: $curso_id</p>";
        } else {
            $curso_id = $curso['id'];
            echo "<p>✅ Usando curso existente con ID: $curso_id</p>";
        }
        
        // Obtener participantes
        $query_participantes = "SELECT id FROM participantes WHERE activo = 1 LIMIT 5";
        $stmt_participantes = $db->prepare($query_participantes);
        $stmt_participantes->execute();
        $participantes = $stmt_participantes->fetchAll();
        
        if (count($participantes) == 0) {
            echo "<p>❌ No hay participantes disponibles. Creando participantes de prueba...</p>";
            
            // Crear participantes de prueba
            $participantes_prueba = [
                ['Juan', 'Pérez', '12345678', 'juan.perez@email.com'],
                ['María', 'González', '87654321', 'maria.gonzalez@email.com'],
                ['Carlos', 'Rodríguez', '11223344', 'carlos.rodriguez@email.com'],
                ['Ana', 'Martínez', '44332211', 'ana.martinez@email.com'],
                ['Luis', 'López', '55667788', 'luis.lopez@email.com']
            ];
            
            foreach ($participantes_prueba as $participante) {
                $query_insert_participante = "INSERT INTO participantes (nombres, apellidos, cedula, email, password, activo) 
                                            VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_insert_participante = $db->prepare($query_insert_participante);
                $stmt_insert_participante->execute([
                    $participante[0],
                    $participante[1], 
                    $participante[2],
                    $participante[3],
                    md5($participante[2]), // Contraseña = cédula
                    1
                ]);
                
                $participante_id = $db->lastInsertId();
                
                // Crear matrícula
                $query_insert_matricula = "INSERT INTO matriculas (participante_id, curso_id, fecha_inicio, fecha_finalizacion, calificacion, estado) 
                                         VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_insert_matricula = $db->prepare($query_insert_matricula);
                $stmt_insert_matricula->execute([
                    $participante_id,
                    $curso_id,
                    date('Y-m-d', strtotime('-30 days')),
                    date('Y-m-d', strtotime('-1 day')),
                    rand(80, 100),
                    'completado'
                ]);
                
                echo "<p>✅ Participante y matrícula creados: {$participante[0]} {$participante[1]}</p>";
            }
        } else {
            echo "<p>✅ Encontrados " . count($participantes) . " participantes. Creando matrículas...</p>";
            
            // Crear matrículas para participantes existentes
            foreach ($participantes as $participante) {
                $query_insert_matricula = "INSERT INTO matriculas (participante_id, curso_id, fecha_inicio, fecha_finalizacion, calificacion, estado) 
                                         VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_insert_matricula = $db->prepare($query_insert_matricula);
                $stmt_insert_matricula->execute([
                    $participante['id'],
                    $curso_id,
                    date('Y-m-d', strtotime('-30 days')),
                    date('Y-m-d', strtotime('-1 day')),
                    rand(80, 100),
                    'completado'
                ]);
                
                echo "<p>✅ Matrícula creada para participante ID: {$participante['id']}</p>";
            }
        }
    } else {
        echo "<p>ℹ️ Ya existen matrículas en el sistema</p>";
    }
    
    // Verificar el resultado final
    $query_final = "SELECT COUNT(*) as total FROM matriculas";
    $stmt_final = $db->prepare($query_final);
    $stmt_final->execute();
    $total_final = $stmt_final->fetch()['total'];
    
    echo "<p>📊 Total de matrículas después del proceso: $total_final</p>";
    
    // Mostrar participantes por curso
    $query_test = "SELECT c.nombre as curso_nombre, COUNT(m.id) as total_matriculados
                   FROM cursos c
                   LEFT JOIN matriculas m ON c.id = m.curso_id
                   GROUP BY c.id, c.nombre";
    $stmt_test = $db->prepare($query_test);
    $stmt_test->execute();
    $cursos_info = $stmt_test->fetchAll();
    
    echo "<h3>📋 Resumen por Curso:</h3>";
    foreach ($cursos_info as $curso_info) {
        echo "<p>• {$curso_info['curso_nombre']}: {$curso_info['total_matriculados']} matriculados</p>";
    }
    
    echo "<p>🎉 ¡Proceso completado! Ahora puedes probar la carga de participantes en el modal.</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
