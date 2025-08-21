-- Verificar y agregar columnas y constraints a la tabla escuelas
-- Primero verificamos si las columnas existen

-- Agregar columnas si no existen
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'escuelas' 
     AND COLUMN_NAME = 'director_id') = 0,
    'ALTER TABLE escuelas ADD COLUMN director_id INT NULL',
    'SELECT "La columna director_id ya existe"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'escuelas' 
     AND COLUMN_NAME = 'coordinador_id') = 0,
    'ALTER TABLE escuelas ADD COLUMN coordinador_id INT NULL',
    'SELECT "La columna coordinador_id ya existe"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar índices para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_escuelas_director ON escuelas(director_id);
CREATE INDEX IF NOT EXISTS idx_escuelas_coordinador ON escuelas(coordinador_id);

-- Agregar constraints de foreign key (solo si no existen)
SET @constraint_exists = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'escuelas' 
    AND CONSTRAINT_NAME = 'fk_escuelas_director');

SET @sql = IF(@constraint_exists = 0,
    'ALTER TABLE escuelas ADD CONSTRAINT fk_escuelas_director FOREIGN KEY (director_id) REFERENCES usuarios(id) ON DELETE SET NULL',
    'SELECT "Constraint fk_escuelas_director ya existe"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'escuelas' 
    AND CONSTRAINT_NAME = 'fk_escuelas_coordinador');

SET @sql = IF(@constraint_exists = 0,
    'ALTER TABLE escuelas ADD CONSTRAINT fk_escuelas_coordinador FOREIGN KEY (coordinador_id) REFERENCES usuarios(id) ON DELETE SET NULL',
    'SELECT "Constraint fk_escuelas_coordinador ya existe"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar la estructura final
DESCRIBE escuelas;

-- Mostrar usuarios disponibles para asignar como directores/coordinadores
SELECT 
    u.id,
    CONCAT(u.nombres, ' ', u.apellidos) as nombre_completo,
    u.email,
    r.nombre as rol,
    u.escuela_id
FROM usuarios u
JOIN roles r ON u.rol_id = r.id
WHERE r.nombre IN ('Director de Escuela', 'Coordinador', 'Escuela')
AND u.activo = 1
ORDER BY r.nombre, u.nombres;
