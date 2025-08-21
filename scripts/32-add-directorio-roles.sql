-- Agregar campos para coordinadores e instructores en los cursos
ALTER TABLE cursos 
ADD COLUMN coordinador_id INT NULL,
ADD COLUMN instructor_principal_id INT NULL,
ADD COLUMN logistica_id INT NULL;

-- Agregar foreign keys
ALTER TABLE cursos 
ADD CONSTRAINT fk_cursos_coordinador 
FOREIGN KEY (coordinador_id) REFERENCES usuarios(id),
ADD CONSTRAINT fk_cursos_instructor_principal 
FOREIGN KEY (instructor_principal_id) REFERENCES usuarios(id),
ADD CONSTRAINT fk_cursos_logistica 
FOREIGN KEY (logistica_id) REFERENCES usuarios(id);

-- Crear tabla para instructores adicionales del curso
CREATE TABLE IF NOT EXISTS curso_instructores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo_instructor ENUM('principal', 'auxiliar', 'invitado') DEFAULT 'auxiliar',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_curso_instructor (curso_id, usuario_id)
);

-- Insertar algunos datos de ejemplo
INSERT INTO curso_instructores (curso_id, usuario_id, tipo_instructor) VALUES
(1, 2, 'principal'),
(1, 3, 'auxiliar');
