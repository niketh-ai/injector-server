<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/security.php';

class AppAPI {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    private function authenticateApiKey() {
        $headers = apache_request_headers();
        $api_key = $headers['X-API-Key'] ?? $_GET['api_key'] ?? '';
        
        if (empty($api_key)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'API key required']);
            exit;
        }
        
        $query = "SELECT api_key FROM app_settings ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row || !hash_equals($row['api_key'], $api_key)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid API key']);
            exit;
        }
        
        return true;
    }
    
    public function checkMaintenance() {
        $this->authenticateApiKey();
        
        $query = "SELECT * FROM maintenance ORDER BY updated_at DESC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $maintenance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'maintenance' => $maintenance ?: ['is_active' => false, 'message' => '']
        ]);
    }
    
    public function userLogin() {
        $this->authenticateApiKey();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $username = Security::sanitize($input['username'] ?? '');
        $password = Security::sanitize($input['password'] ?? '');
        
        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username and password required']);
            return;
        }
        
        // Check maintenance first
        $query = "SELECT * FROM maintenance ORDER BY updated_at DESC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $maintenance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($maintenance && $maintenance['is_active']) {
            echo json_encode([
                'success' => false, 
                'maintenance' => true,
                'message' => $maintenance['message'] ?: 'Maintenance in progress'
            ]);
            return;
        }
        
        // Check user credentials
        $query = "SELECT * FROM app_users WHERE username = :username AND is_banned = false LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                // Check if account is expired
                if (strtotime($user['expires_at']) < time()) {
                    echo json_encode(['success' => false, 'message' => 'Account has expired']);
                    return;
                }
                
                echo json_encode([
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'expires_at' => $user['expires_at']
                    ]
                ]);
                return;
            }
        }
        
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
    
    public function getUserInfo() {
        $this->authenticateApiKey();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = intval($input['user_id'] ?? 0);
        
        $query = "SELECT id, username, expires_at, is_banned FROM app_users WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    }
}

// Handle API requests
$api = new AppAPI();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check_maintenance':
        $api->checkMaintenance();
        break;
    case 'user_login':
        $api->userLogin();
        break;
    case 'get_user_info':
        $api->getUserInfo();
        break;
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Invalid API action']);
        break;
}
?>