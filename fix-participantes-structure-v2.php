<?php
require_once 'config/database.php';

function fixParticipantesStructure() {
    $conn = getMySQLiConnection();
    $output = [];
    
    // Verificar si la tabla participantes existe
    $check_table = "SHOW TABLES LIKE 'participantes'";
    $result = $conn->query($check_table);
    
    if ($result->num_rows == 0) {
        $output[] = "La tabla 'participantes' no existe. Creando tabla...";
        
        // Crear la tabla participantes
        $create_table = "
            CREATE TABLE participantes (
                id INT(11) NOT NULL AUTO_INCREMENT,
                nombres VARCHAR(100) NOT NULL,
                apellidos VARCHAR(100) NOT NULL,
                cedula VARCHAR(20) NOT NULL,
                email VARCHAR(100) NOT NULL,
                telefono VARCHAR(20) DEFAULT NULL,
                direccion VARCHAR(200) DEFAULT NULL,
                fecha_nacimiento DATE DEFAULT NULL,
                genero ENUM('M','F','O') DEFAULT NULL,
                password VARCHAR(255) DEFAULT NULL,
                activo TINYINT(1) DEFAULT 1,
                ultimo_acceso DATETIME DEFAULT NULL,
                fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY cedula (cedula),
                UNIQUE KEY email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        if ($conn->query($create_table) === TRUE) {
            $output[] = "Tabla 'participantes' creada con éxito.";
        } else {
            $output[] = "Error al crear la tabla: " . $conn->error;
            return $output;
        }
    } else {
        $output[] = "La tabla 'participantes' ya existe. Verificando estructura...";
        
        // Verificar si existe la columna password
        $check_password = "SHOW COLUMNS FROM participantes LIKE 'password'";
        $result = $conn->query($check_password);
        
        if ($result->num_rows == 0) {
            $output[] = "La columna 'password' no existe. Agregando...";
            $add_password = "ALTER TABLE participantes ADD COLUMN password VARCHAR(255) DEFAULT NULL";
            
            if ($conn->query($add_password) === TRUE) {
                $output[] = "Columna 'password' agregada con éxito.";
            } else {
                $output[] = "Error al agregar la columna 'password': " . $conn->error;
            }
        } else {
            $output[] = "La columna 'password' ya existe.";
        }
        
        // Verificar si existe la columna activo
        $check_activo = "SHOW COLUMNS FROM participantes LIKE 'activo'";
        $result = $conn->query($check_activo);
        
        if ($result->num_rows == 0) {
            $output[] = "La columna 'activo' no existe. Agregando...";
            $add_activo = "ALTER TABLE participantes ADD COLUMN activo TINYINT(1) DEFAULT 1";
            
            if ($conn->query($add_activo) === TRUE) {
                $output[] = "Columna 'activo' agregada con éxito.";
            } else {
                $output[] = "Error al agregar la columna 'activo': " . $conn->error;
            }
        } else {
            $output[] = "La columna 'activo' ya existe.";
        }
        
        // Verificar si existe la columna ultimo_acceso
        $check_ultimo_acceso = "SHOW COLUMNS FROM participantes LIKE 'ultimo_acceso'";
        $result = $conn->query($check_ultimo_acceso);
        
        if ($result->num_rows == 0) {
            $output[] = "La columna 'ultimo_acceso' no existe. Agregando...";
            $add_ultimo_acceso = "ALTER TABLE participantes ADD COLUMN ultimo_acceso DATETIME DEFAULT NULL";
            
            if ($conn->query($add_ultimo_acceso) === TRUE) {
                $output[] = "Columna 'ultimo_acceso' agregada con éxito.";
            } else {
                $output[] = "Error al agregar la columna 'ultimo_acceso': " . $conn->error;
            }
        } else {
            $output[] = "La columna 'ultimo_acceso' ya existe.";
        }
    }
    
    // Configurar contraseñas para participantes existentes
    $output[] = "Configurando contraseñas para participantes existentes...";
    $update_passwords = "UPDATE participantes SET password = MD5(cedula) WHERE password IS NULL";
    
    if ($conn->query($update_passwords) === TRUE) {
        $rows_affected = $conn->affected_rows;
        $output[] = "Contraseñas configuradas para $rows_affected participantes.";
    } else {
        $output[] = "Error al configurar contraseñas: " . $conn->error;
    }
    
    // Mostrar algunos participantes de ejemplo
    $output[] = "Participantes disponibles para pruebas:";
    $sample_participants = "SELECT id, nombres, apellidos, cedula, email FROM participantes LIMIT 5";
    $result = $conn->query($sample_participants);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = "ID: {$row['id']}, Nombre: {$row['nombres']} {$row['apellidos']}, Cédula: {$row['cedula']}, Email: {$row['email']}";
        }
    } else {
        $output[] = "No hay participantes registrados.";
        
        // Crear un participante de prueba
        $output[] = "Creando participante de prueba...";
        $create_test = "
            INSERT INTO participantes (nombres, apellidos, cedula, email, telefono, password, activo)
            VALUES ('Juan', 'Pérez', '12345678', 'juan.perez@email.com', '3001234567', MD5('12345678'), 1)
        ";
        
        if ($conn->query($create_test) === TRUE) {
            $output[] = "Participante de prueba creado con éxito.";
            $output[] = "Usuario: juan.perez@email.com o 12345678";
            $output[] = "Contraseña: 12345678";
        } else {
            $output[] = "Error al crear participante de prueba: " . $conn->error;
        }
    }
    
    return $output;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reparación de Estructura de Participantes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🔧 Reparación de Estructura de Participantes</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        $results = fixParticipantesStructure();
                        foreach ($results as $line) {
                            echo "<p>$line</p>";
                        }
                        ?>
                        <hr>
                        <div class="mt-3">
                            <a href="participante-login.php" class="btn btn-primary">Ir al Login de Participantes</a>
                            <a href="create-test-participant.php" class="btn btn-success ms-2">Crear Participante de Prueba</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
