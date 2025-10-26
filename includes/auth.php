<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function login($username, $password) {
        $username = Security::sanitize($username);
        
        $query = "SELECT * FROM owners WHERE username = :username LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (Security::verifyPassword($password, $row['password'])) {
                $_SESSION['owner_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['user_type'] = 'owner';
                $_SESSION['loggedin'] = true;
                return true;
            }
        }
        return false;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
    }
    
    public function logout() {
        $_SESSION = array();
        session_destroy();
    }
    
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header("Location: ../login.php");
            exit;
        }
    }
}
?>