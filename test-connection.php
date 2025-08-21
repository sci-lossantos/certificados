<?php
require_once 'config/database.php';

function testConnection() {
    $results = [];
    
    // Probar conexión PDO
    try {
        $pdo_conn = getConnection();
        if ($pdo_conn) {
            $results[] = [
                'type' => 'success',
                'message' => '✅ Conexión PDO exitosa'
            ];
            
            // Verificar versión de MySQL
            $stmt = $pdo_conn->query("SELECT VERSION() as version");
            $version = $stmt->fetch();
            $results[] = [
                'type' => 'info',
                'message' => '📊 Versión de MySQL (PDO): ' . $version['version']
            ];
            
            // Verificar tablas
            $stmt = $pdo_conn->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $results[] = [
                'type' => 'info',
                'message' => '📋 Tablas encontradas (PDO): ' . count($tables)
            ];
            
            // Listar algunas tablas
            $table_list = implode(', ', array_slice($tables, 0, 5));
            $results[] = [
                'type' => 'info',
                'message' => '📑 Primeras tablas: ' . $table_list . (count($tables) > 5 ? '...' : '')
            ];
        }
    } catch (PDOException $e) {
        $results[] = [
            'type' => 'danger',
            'message' => '❌ Error de conexión PDO: ' . $e->getMessage()
        ];
    }
    
    // Probar conexión MySQLi
    try {
        $mysqli_conn = getMySQLiConnection();
        if ($mysqli_conn) {
            $results[] = [
                'type' => 'success',
                'message' => '✅ Conexión MySQLi exitosa'
            ];
            
            // Verificar versión de MySQL
            $result = $mysqli_conn->query("SELECT VERSION() as version");
            $version = $result->fetch_assoc();
            $results[] = [
                'type' => 'info',
                'message' => '📊 Versión de MySQL (MySQLi): ' . $version['version']
            ];
            
            // Verificar tablas
            $result = $mysqli_conn->query("SHOW TABLES");
            $tables = [];
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $tables[] = $row[0];
            }
            $results[] = [
                'type' => 'info',
                'message' => '📋 Tablas encontradas (MySQLi): ' . count($tables)
            ];
            
            // Listar algunas tablas
            $table_list = implode(', ', array_slice($tables, 0, 5));
            $results[] = [
                'type' => 'info',
                'message' => '📑 Primeras tablas: ' . $table_list . (count($tables) > 5 ? '...' : '')
            ];
        }
    } catch (Exception $e) {
        $results[] = [
            'type' => 'danger',
            'message' => '❌ Error de conexión MySQLi: ' . $e->getMessage()
        ];
    }
    
    return $results;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Conexión a Base de Datos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🔌 Prueba de Conexión a Base de Datos</h4>
                    </div>
                    <div class="card-body">
                        <h5>Resultados:</h5>
                        <?php
                        $results = testConnection();
                        foreach ($results as $result) {
                            echo "<div class='alert alert-{$result['type']}'>{$result['message']}</div>";
                        }
                        ?>
                        <hr>
                        <div class="mt-3">
                            <a href="fix-participantes-structure-v2.php" class="btn btn-primary">Reparar Estructura de Participantes</a>
                            <a href="participante-login.php" class="btn btn-success ms-2">Ir al Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
