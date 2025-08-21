-- Corregir el campo género para permitir valores más largos
ALTER TABLE participantes 
MODIFY COLUMN genero ENUM('M', 'F', 'Masculino', 'Femenino', 'Otro') DEFAULT NULL;
