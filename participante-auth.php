<?php
session_start();
require_once 'config/database.php';

class ParticipanteAuth {
    private $conn;
    
    public function __construct() {
        $this->conn = getMySQLiConnection(); // Usar MySQLi para compatibilidad
    }
    
    public function login($usuario, $password) {
        try {
            // Buscar por email o cédula
            $sql = "SELECT id, nombres, apellidos, email, cedula, password, activo 
                    FROM participantes 
                    WHERE (email = ? OR cedula = ?) AND activo = 1";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("Error preparando consulta: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("ss", $usuario, $usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $participante = $result->fetch_assoc();
                
                // Verificar contraseña (MD5 por compatibilidad)
                if (md5($password) === $participante['password']) {
                    // Actualizar último acceso
                    $this->updateLastAccess($participante['id']);
                    
                    // Crear sesión
                    $_SESSION['participante_id'] = $participante['id'];
                    $_SESSION['participante_nombre'] = $participante['nombres'] . ' ' . $participante['apellidos'];
                    $_SESSION['participante_email'] = $participante['email'];
                    $_SESSION['participante_cedula'] = $participante['cedula'];
                    $_SESSION['participante_loggedin'] = true;
                    
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error en login de participante: " . $e->getMessage());
            return false;
        }
    }
    
    public function logout() {
        // Limpiar solo las variables de sesión del participante
        unset($_SESSION['participante_id']);
        unset($_SESSION['participante_nombre']);
        unset($_SESSION['participante_email']);
        unset($_SESSION['participante_cedula']);
        unset($_SESSION['participante_loggedin']);
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['participante_loggedin']) && $_SESSION['participante_loggedin'] === true;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: participante-login.php');
            exit();
        }
    }
    
    private function updateLastAccess($participante_id) {
        try {
            $sql = "UPDATE participantes SET ultimo_acceso = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $participante_id);
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log("Error actualizando último acceso: " . $e->getMessage());
        }
    }
    
    public function getParticipanteData($participante_id) {
        try {
            $sql = "SELECT * FROM participantes WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return null;
            }
            
            $stmt->bind_param("i", $participante_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error obteniendo datos del participante: " . $e->getMessage());
            return null;
        }
    }
}
?>
