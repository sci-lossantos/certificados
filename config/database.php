<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'ESIBOC-DNBC';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Función global para compatibilidad
function getConnection() {
    $database = new Database();
    return $database->getConnection();
}

// Función para MySQLi (compatibilidad con código existente)
function getMySQLiConnection() {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'ESIBOC-DNBC';
    
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}
?>
