<?php
class Security {
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    public static function generateApiKey() {
        return bin2hex(random_bytes(32));
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
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
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function rateLimit($key, $limit = 5, $timeout = 60) {
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        
        $current_time = time();
        $attempts = $_SESSION['rate_limits'][$key] ?? [];
        
        // Remove old attempts
        $attempts = array_filter($attempts, function($attempt) use ($current_time, $timeout) {
            return $attempt > ($current_time - $timeout);
        });
        
        if (count($attempts) >= $limit) {
            return false;
        }
        
        $attempts[] = $current_time;
        $_SESSION['rate_limits'][$key] = $attempts;
        return true;
    }
    
    public static function generateRandomString($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function encryptData($data, $key) {
        $method = 'AES-256-CBC';
        $iv_length = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    public static function decryptData($data, $key) {
        $method = 'AES-256-CBC';
        $data = base64_decode($data);
        $iv_length = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        return openssl_decrypt($encrypted, $method, $key, OPENSSL_RAW_DATA, $iv);
    }
    
    public static function validateInput($input, $type = 'string', $min_length = 1, $max_length = 255) {
        if (empty($input)) {
            return false;
        }
        
        $length = strlen($input);
        if ($length < $min_length || $length > $max_length) {
            return false;
        }
        
        switch ($type) {
            case 'username':
                return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $input);
                
            case 'password':
                return $length >= 6 && $length <= 255;
                
            case 'email':
                return self::validateEmail($input);
                
            case 'number':
                return is_numeric($input);
                
            case 'alphanumeric':
                return preg_match('/^[a-zA-Z0-9\s]+$/', $input);
                
            case 'string':
            default:
                return true;
        }
    }
    
    public static function sanitizeFileName($filename) {
        $filename = preg_replace('/[^a-zA-Z0-9\.\_\-]/', '', $filename);
        $filename = str_replace(' ', '_', $filename);
        return substr($filename, 0, 255);
    }
    
    public static function validateFileUpload($file, $allowed_types = [], $max_size = 5242880) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        if ($file['size'] > $max_size) {
            return false;
        }
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!empty($allowed_types) && !in_array($file_extension, $allowed_types)) {
            return false;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mime_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf'
        ];
        
        if (isset($allowed_mime_types[$file_extension]) && $allowed_mime_types[$file_extension] !== $mime_type) {
            return false;
        }
        
        return true;
    }
    
    public static function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (self::validateIP($ip)) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    public static function validateIP($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
    
    public static function generateSessionId() {
        return bin2hex(random_bytes(32));
    }
    
    public static function secureSessionStart() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
    
    public static function logSecurityEvent($event, $user_id = null, $details = []) {
        $log_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'user_id' => $user_id,
            'ip_address' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'details' => json_encode($details)
        ];
        
        $log_message = implode(' | ', array_map(function($key, $value) {
            return "$key: $value";
        }, array_keys($log_data), $log_data));
        
        error_log("SECURITY: " . $log_message);
        
        // Also write to security log file
        $log_file = __DIR__ . '/../logs/security.log';
        if (is_writable(dirname($log_file))) {
            file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }
    
    public static function checkSQLInjection($input) {
        $sql_patterns = [
            '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION|WHERE)\b/i',
            '/\b(OR|AND)\s+[\d\'\"]+\s*=\s*[\d\'\"]/i',
            '/\'|\"|;|--|\/\*|\*\//'
        ];
        
        foreach ($sql_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                self::logSecurityEvent('SQL_INJECTION_ATTEMPT', null, ['input' => $input]);
                return false;
            }
        }
        
        return true;
    }
    
    public static function checkXSS($input) {
        $xss_patterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe\b[^>]*>(.*?)<\/iframe>/is',
            '/<object\b[^>]*>(.*?)<\/object>/is',
            '/<embed\b[^>]*>(.*?)<\/embed>/is'
        ];
        
        foreach ($xss_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                self::logSecurityEvent('XSS_ATTEMPT', null, ['input' => $input]);
                return false;
            }
        }
        
        return true;
    }
    
    public static function generatePassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        $chars_length = strlen($chars) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $chars_length)];
        }
        
        return $password;
    }
    
    public static function validateDate($date, $format = 'Y-m-d H:i:s') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    public static function escapeLikeString($string) {
        return str_replace(['%', '_'], ['\%', '\_'], $string);
    }
    
    public static function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
?>
