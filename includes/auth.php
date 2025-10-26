<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        if (!$this->db) {
            error_log("Auth: Database connection failed");
        }
    }
    
    public function login($username, $password) {
        if (!$this->db) {
            error_log("Login failed: No database connection");
            return false;
        }
        
        $username = Security::sanitize($username);
        
        try {
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
                    $_SESSION['login_time'] = time();
                    
                    // Update last login
                    $this->updateLastLogin($row['id']);
                    return true;
                }
            }
            return false;
            
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    private function updateLastLogin($owner_id) {
        if (!$this->db) return false;
        
        try {
            $query = "UPDATE owners SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $owner_id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
            return false;
        }
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            return false;
        }
        
        // Check session expiration (8 hours)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 28800) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    public function logout() {
        // Unset all session variables
        $_SESSION = array();
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
    
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header("Location: ../login.php");
            exit;
        }
    }
    
    public function requireOwner() {
        $this->requireAuth();
        
        if ($_SESSION['user_type'] !== 'owner') {
            header("Location: ../login.php");
            exit;
        }
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['owner_id'],
                'username' => $_SESSION['username'],
                'user_type' => $_SESSION['user_type']
            ];
        }
        return null;
    }
    
    public function changePassword($current_password, $new_password) {
        if (!$this->db || !$this->isLoggedIn()) {
            return false;
        }
        
        try {
            // Verify current password
            $query = "SELECT password FROM owners WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $_SESSION['owner_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (Security::verifyPassword($current_password, $row['password'])) {
                    // Update to new password
                    $new_hashed_password = Security::hashPassword($new_password);
                    $update_query = "UPDATE owners SET password = :password WHERE id = :id";
                    $update_stmt = $this->db->prepare($update_query);
                    $update_stmt->bindParam(":password", $new_hashed_password);
                    $update_stmt->bindParam(":id", $_SESSION['owner_id']);
                    
                    return $update_stmt->execute();
                }
            }
            return false;
            
        } catch(PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            return false;
        }
    }
    
    public function resellerLogin($username, $password) {
        if (!$this->db) {
            error_log("Reseller login failed: No database connection");
            return false;
        }
        
        $username = Security::sanitize($username);
        
        try {
            $query = "SELECT * FROM resellers WHERE username = :username AND is_active = true LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (Security::verifyPassword($password, $row['password'])) {
                    $_SESSION['reseller_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['user_type'] = 'reseller';
                    $_SESSION['loggedin'] = true;
                    $_SESSION['login_time'] = time();
                    $_SESSION['credits'] = $row['credits'];
                    
                    return true;
                }
            }
            return false;
            
        } catch(PDOException $e) {
            error_log("Reseller login error: " . $e->getMessage());
            return false;
        }
    }
    
    public function isReseller() {
        return $this->isLoggedIn() && $_SESSION['user_type'] === 'reseller';
    }
    
    public function isOwner() {
        return $this->isLoggedIn() && $_SESSION['user_type'] === 'owner';
    }
    
    public function getResellerCredits() {
        if ($this->isReseller() && isset($_SESSION['credits'])) {
            return $_SESSION['credits'];
        }
        return 0;
    }
    
    public function validateSession() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Refresh session time
        $_SESSION['login_time'] = time();
        return true;
    }
    
    public function checkPermission($action) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user_type = $_SESSION['user_type'];
        
        // Define permissions
        $permissions = [
            'owner' => [
                'manage_users', 'manage_resellers', 'system_settings', 
                'maintenance_control', 'view_reports', 'manage_credits'
            ],
            'reseller' => [
                'manage_own_users', 'view_own_stats'
            ]
        ];
        
        return isset($permissions[$user_type]) && in_array($action, $permissions[$user_type]);
    }
    
    public function getSessionInfo() {
        if ($this->isLoggedIn()) {
            return [
                'user_id' => $_SESSION['user_type'] === 'owner' ? $_SESSION['owner_id'] : $_SESSION['reseller_id'],
                'username' => $_SESSION['username'],
                'user_type' => $_SESSION['user_type'],
                'login_time' => $_SESSION['login_time'],
                'session_duration' => time() - $_SESSION['login_time']
            ];
        }
        return null;
    }
}

// Helper function to check if user is authenticated
function is_authenticated() {
    $auth = new Auth();
    return $auth->isLoggedIn();
}

// Helper function to require authentication
function require_auth() {
    $auth = new Auth();
    $auth->requireAuth();
}

// Helper function to get current user
function current_user() {
    $auth = new Auth();
    return $auth->getCurrentUser();
}
?>
