<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        // Render PostgreSQL configuration
        $this->host = getenv('DB_HOST');
        $this->db_name = getenv('DB_NAME');
        $this->username = getenv('DB_USERNAME');
        $this->password = getenv('DB_PASSWORD');
        $this->port = getenv('DB_PORT') ?: '5432';
        
        error_log("DB Config - Host: " . $this->host . ", DB: " . $this->db_name);
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            error_log("Attempting connection to: " . $dsn);
            
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            error_log("Database connected successfully");
            return $this->conn;
            
        } catch(PDOException $exception) {
            error_log("Database connection failed: " . $exception->getMessage());
            // Return null instead of dying to allow graceful handling
            return null;
        }
    }
}
?>
