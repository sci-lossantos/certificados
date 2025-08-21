<?php
session_start();

class Auth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function login($email, $password) {
        $query = "SELECT u.*, r.nombre as rol_nombre 
                  FROM usuarios u 
                  JOIN roles r ON u.rol_id = r.id 
                  WHERE u.email = ? AND u.activo = 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombres'] . ' ' . $user['apellidos'];
            $_SESSION['user_role'] = $user['rol_nombre'];
            $_SESSION['user_role_id'] = $user['rol_id'];
            return true;
        }
        return false;
    }
    
    public function logout() {
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function hasRole($roles) {
        if (!$this->isLoggedIn()) return false;
        
        if (is_string($roles)) {
            return $_SESSION['user_role'] === $roles;
        }
        
        if (is_array($roles)) {
            return in_array($_SESSION['user_role'], $roles);
        }
        
        return false;
    }
    
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header("Location: login.php");
            exit();
        }
    }
    
    public function requireRole($roles) {
        $this->requireAuth();
        if (!$this->hasRole($roles)) {
            header("Location: unauthorized.php");
            exit();
        }
    }

    public function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }

    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    public function getUserEscuelaId() {
        // Si el usuario es de tipo Escuela o Director de Escuela, obtener su escuela_id
        if (in_array($this->getUserRole(), ['Escuela', 'Director de Escuela'])) {
            $query = "SELECT escuela_id FROM usuarios WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$this->getUserId()]);
            $result = $stmt->fetch();
            return $result ? $result['escuela_id'] : null;
        }
        return null;
    }

    public function getUserName() {
        return $_SESSION['user_name'] ?? null;
    }

    public function getUserRoleId() {
        return $_SESSION['user_role_id'] ?? null;
    }
}
?>
