<?php
// File: backend/auth.php
require_once 'db.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function login($username, $password) {
        $query = "SELECT u.*, o.name as outlet_name FROM users u 
                  LEFT JOIN outlets o ON u.outlet_id = o.id 
                  WHERE u.username = :username AND u.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['outlet_id'] = $user['outlet_id'];
                $_SESSION['outlet_name'] = $user['outlet_name'];
                
                return [
                    'success' => true,
                    'user_type' => $user['user_type'],
                    'outlet_id' => $user['outlet_id']
                ];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    public function logout() {
        session_destroy();
        return ['success' => true];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin';
    }
    
    public function isOutlet() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'outlet';
    }
}
?>