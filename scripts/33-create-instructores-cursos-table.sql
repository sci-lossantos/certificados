-- Crear tabla para asignar instructores a cursos
CREATE TABLE IF NOT EXISTS instructores_cursos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    curso_id INT NOT NULL,
    instructor_id INT NOT NULL,
    tipo_instructor ENUM('coordinador', 'instructor', 'auxiliar') DEFAULT 'instructor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_instructor_curso (curso_id, instructor_id)
);

-- Insertar coordinadores existentes como instructores
INSERT IGNORE INTO instructores_cursos (curso_id, instructor_id, tipo_instructor)
SELECT c.id, c.coordinador_id, 'coordinador'
FROM cursos c
WHERE c.coordinador_id IS NOT NULL;

-- Verificar la estructura
DESCRIBE instructores_cursos;

-- Mostrar datos insertados
SELECT ic.*, 
       c.nombre as curso_nombre,
       CONCAT(u.nombres, ' ', u.apellidos) as instructor_nombre,
       ic.tipo_instructor
FROM instructores_cursos ic
JOIN cursos c ON ic.curso_id = c.id
JOIN usuarios u ON ic.instructor_id = u.id
ORDER BY c.nombre, ic.tipo_instructor;
