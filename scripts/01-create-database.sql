-- Crear base de datos ESIBOC-DNBC
CREATE DATABASE IF NOT EXISTS `ESIBOC-DNBC` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ESIBOC-DNBC`;

-- Tabla de roles
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    telefono VARCHAR(20),
    institucion VARCHAR(100),
    genero ENUM('M', 'F', 'Otro'),
    fotografia VARCHAR(255),
    firma_digital VARCHAR(255),
    tipo_firma ENUM('imagen', 'criptografica') DEFAULT 'imagen',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

-- Tabla de escuelas
CREATE TABLE escuelas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    direccion TEXT,
    telefono VARCHAR(20),
    email VARCHAR(100),
    director_id INT,
    activa BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (director_id) REFERENCES usuarios(id)
);

-- Tabla de cursos
CREATE TABLE cursos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    numero_registro VARCHAR(50) NOT NULL UNIQUE,
    coordinador_id INT NOT NULL,
    escuela_id INT NOT NULL,
    fecha_inicio DATE,
    fecha_fin DATE,
    duracion_horas INT,
    contenido_tematico TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (coordinador_id) REFERENCES usuarios(id),
    FOREIGN KEY (escuela_id) REFERENCES escuelas(id)
);

-- Tabla de participantes
CREATE TABLE participantes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    institucion VARCHAR(100),
    genero ENUM('M', 'F', 'Otro'),
    fotografia VARCHAR(255),
    password_hash VARCHAR(255),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de matrículas
CREATE TABLE matriculas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    curso_id INT NOT NULL,
    participante_id INT NOT NULL,
    fecha_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    calificacion DECIMAL(3,1),
    aprobado BOOLEAN DEFAULT FALSE,
    observaciones TEXT,
    FOREIGN KEY (curso_id) REFERENCES cursos(id),
    FOREIGN KEY (participante_id) REFERENCES participantes(id),
    UNIQUE KEY unique_matricula (curso_id, participante_id)
);

-- Tabla de documentos (actas, informes, certificados)
CREATE TABLE documentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo ENUM('acta', 'informe', 'certificado', 'directorio') NOT NULL,
    curso_id INT NOT NULL,
    participante_id INT NULL, -- NULL para documentos generales del curso
    codigo_unico VARCHAR(100) NOT NULL UNIQUE,
    contenido TEXT,
    archivo_pdf VARCHAR(255),
    estado ENUM('generado', 'firmado_coordinador', 'firmado_director', 'aprobado_educacion', 'firmado_nacional') DEFAULT 'generado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id),
    FOREIGN KEY (participante_id) REFERENCES participantes(id)
);

-- Tabla de firmas de documentos
CREATE TABLE firmas_documentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    documento_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo_firma ENUM('coordinador', 'director_escuela', 'educacion_dnbc', 'director_nacional') NOT NULL,
    fecha_firma TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    hash_firma VARCHAR(255),
    observaciones TEXT,
    FOREIGN KEY (documento_id) REFERENCES documentos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
