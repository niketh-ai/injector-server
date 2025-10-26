<?php
class Security {
    public static function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
    
    public static function generateApiKey() {
        return bin2hex(random_bytes(32));
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function rateLimit($key, $limit = 5, $timeout = 60) {
        $current_time = time();
        $attempts = $_SESSION[$key] ?? [];
        
        // Remove old attempts
        $attempts = array_filter($attempts, function($attempt) use ($current_time, $timeout) {
            return $attempt > ($current_time - $timeout);
        });
        
        if (count($attempts) >= $limit) {
            return false;
        }
        
        $attempts[] = $current_time;
        $_SESSION[$key] = $attempts;
        return true;
    }
}
?>