<?php
require_once 'config/database.php';

class Functions {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function getDashboardStats() {
        $stats = [];
        
        // Total users
        $query = "SELECT COUNT(*) FROM app_users";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats['total_users'] = $stmt->fetchColumn();
        
        // Total resellers
        $query = "SELECT COUNT(*) FROM resellers";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats['total_resellers'] = $stmt->fetchColumn();
        
        // Active users (not expired, not banned)
        $query = "SELECT COUNT(*) FROM app_users WHERE expires_at > NOW() AND is_banned = false";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats['active_users'] = $stmt->fetchColumn();
        
        // Banned users
        $query = "SELECT COUNT(*) FROM app_users WHERE is_banned = true";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats['banned_users'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    public function getMaintenanceStatus() {
        $query = "SELECT * FROM maintenance ORDER BY updated_at DESC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['is_active' => false, 'message' => ''];
    }
    
    public function updateMaintenance($is_active, $message) {
        $query = "INSERT INTO maintenance (is_active, message, updated_by) 
                  VALUES (:is_active, :message, :updated_by)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":is_active", $is_active, PDO::PARAM_BOOL);
        $stmt->bindParam(":message", $message);
        $stmt->bindParam(":updated_by", $_SESSION['owner_id']);
        
        return $stmt->execute();
    }

    public function createUser($username, $password, $days, $created_by, $creator_type) {
        // Check if username already exists
        $check_query = "SELECT id FROM app_users WHERE username = :username";
        $check_stmt = $this->db->prepare($check_query);
        $check_stmt->bindParam(":username", $username);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            return false;
        }
        
        $hashed_password = Security::hashPassword($password);
        $expires_at = date('Y-m-d H:i:s', strtotime("+$days days"));
        
        $query = "INSERT INTO app_users (username, password, days, expires_at, created_by, creator_type) 
                  VALUES (:username, :password, :days, :expires_at, :created_by, :creator_type)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":days", $days);
        $stmt->bindParam(":expires_at", $expires_at);
        $stmt->bindParam(":created_by", $created_by);
        $stmt->bindParam(":creator_type", $creator_type);
        
        return $stmt->execute();
    }

    public function getAllUsers() {
        $query = "SELECT * FROM app_users ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchUsers($search_term) {
        $query = "SELECT * FROM app_users WHERE username ILIKE :search_term OR id::text LIKE :search_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $search_pattern = "%$search_term%";
        $stmt->bindParam(":search_term", $search_pattern);
        $stmt->bindParam(":search_id", $search_pattern);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function banUser($user_id) {
        $query = "UPDATE app_users SET is_banned = true WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }

    public function unbanUser($user_id) {
        $query = "UPDATE app_users SET is_banned = false WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }

    public function deleteUser($user_id) {
        $query = "DELETE FROM app_users WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }

    public function createReseller($username, $password, $credits, $created_by) {
        // Check if username already exists
        $check_query = "SELECT id FROM resellers WHERE username = :username";
        $check_stmt = $this->db->prepare($check_query);
        $check_stmt->bindParam(":username", $username);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            return false;
        }
        
        $hashed_password = Security::hashPassword($password);
        
        $query = "INSERT INTO resellers (username, password, credits, created_by) 
                  VALUES (:username, :password, :credits, :created_by)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":credits", $credits);
        $stmt->bindParam(":created_by", $created_by);
        
        return $stmt->execute();
    }

    public function getAllResellers() {
        $query = "SELECT r.*, o.username as created_by_name 
                  FROM resellers r 
                  LEFT JOIN owners o ON r.created_by = o.id 
                  ORDER BY r.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateResellerCredits($reseller_id, $credits) {
        $query = "UPDATE resellers SET credits = :credits WHERE id = :reseller_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":credits", $credits);
        $stmt->bindParam(":reseller_id", $reseller_id);
        
        return $stmt->execute();
    }

    public function deleteReseller($reseller_id) {
        // First delete all users created by this reseller
        $delete_users = "DELETE FROM app_users WHERE created_by = :reseller_id AND creator_type = 'reseller'";
        $stmt1 = $this->db->prepare($delete_users);
        $stmt1->bindParam(":reseller_id", $reseller_id);
        $stmt1->execute();
        
        // Then delete the reseller
        $query = "DELETE FROM resellers WHERE id = :reseller_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":reseller_id", $reseller_id);
        
        return $stmt->execute();
    }

    public function getResellerUserCount($reseller_id) {
        $query = "SELECT COUNT(*) FROM app_users WHERE created_by = :reseller_id AND creator_type = 'reseller'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":reseller_id", $reseller_id);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }

    public function getAppSettings() {
        $query = "SELECT * FROM app_settings ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['api_key' => ''];
    }

    public function regenerateApiKey() {
        $new_api_key = Security::generateApiKey();
        
        $query = "INSERT INTO app_settings (api_key) VALUES (:api_key)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":api_key", $new_api_key);
        
        return $stmt->execute();
    }

    public function updateOwnerPassword($owner_id, $current_password, $new_password) {
        $query = "SELECT password FROM owners WHERE id = :owner_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":owner_id", $owner_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (Security::verifyPassword($current_password, $row['password'])) {
                $new_hashed_password = Security::hashPassword($new_password);
                
                $update_query = "UPDATE owners SET password = :password WHERE id = :owner_id";
                $update_stmt = $this->db->prepare($update_query);
                $update_stmt->bindParam(":password", $new_hashed_password);
                $update_stmt->bindParam(":owner_id", $owner_id);
                
                return $update_stmt->execute();
            }
        }
        
        return false;
    }

    public function getExpiredUsers() {
        $query = "SELECT COUNT(*) FROM app_users WHERE expires_at < NOW() AND is_banned = false";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }

    public function getRecentUsers($limit = 5) {
        $query = "SELECT username, created_at, expires_at FROM app_users ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>