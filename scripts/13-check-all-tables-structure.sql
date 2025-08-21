-- Script para verificar la estructura de todas las tablas principales
-- Esto nos ayudará a identificar qué columnas existen realmente

-- Estructura de la tabla cursos
DESCRIBE cursos;

-- Estructura de la tabla participantes  
DESCRIBE participantes;

-- Estructura de la tabla documentos
DESCRIBE documentos;

-- Estructura de la tabla escuelas
DESCRIBE escuelas;

-- Estructura de la tabla usuarios
DESCRIBE usuarios;

-- Estructura de la tabla matriculas
DESCRIBE matriculas;

-- Mostrar algunas filas de ejemplo para entender los datos
SELECT 'CURSOS' as tabla;
SELECT * FROM cursos LIMIT 2;

SELECT 'PARTICIPANTES' as tabla;
SELECT * FROM participantes LIMIT 2;

SELECT 'DOCUMENTOS' as tabla;
SELECT * FROM documentos LIMIT 2;
