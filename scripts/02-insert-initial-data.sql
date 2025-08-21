-- Insertar roles iniciales
INSERT INTO roles (nombre, descripcion) VALUES
('Administrador General', 'Administrador con acceso completo al sistema'),
('Dirección Nacional', 'Director Nacional con capacidad de firma final'),
('Educación DNBC', 'Personal de educación para revisión y aprobación'),
('Escuela', 'Personal administrativo de escuela'),
('Director de Escuela', 'Director de escuela con capacidad de firma'),
('Coordinador', 'Coordinador de cursos con capacidad de calificación'),
('Participante', 'Bombero participante en cursos');

-- Insertar usuario administrador inicial
INSERT INTO usuarios (nombres, apellidos, cedula, email, password_hash, rol_id) VALUES
('Administrador', 'Sistema', '00000000', 'admin@esiboc.gov.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Insertar escuela ejemplo
INSERT INTO escuelas (nombre, codigo, direccion, telefono, email) VALUES
('Escuela Nacional de Bomberos', 'ENB001', 'Bogotá D.C., Colombia', '+57 1 234 5678', 'escuela@bomberos.gov.co');
