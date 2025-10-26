<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        // Render PostgreSQL configuration from environment variables
        $this->host = getenv('DB_HOST');
        $this->db_name = getenv('DB_NAME');
        $this->username = getenv('DB_USERNAME');
        $this->password = getenv('DB_PASSWORD');
        $this->port = getenv('DB_PORT') ?: '5432';
        
        // Fallback for local development
        if (!$this->host) {
            $this->host = 'localhost';
            $this->db_name = 'injector_manager';
            $this->username = 'admin';
            $this->password = 'niketh123';
            $this->port = '5432';
        }
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            // Don't show detailed errors in production
            if (getenv('DB_HOST')) {
                // Production - show generic error
                die("Database connection failed. Please try again later.");
            } else {
                // Development - show detailed error
                die("Connection error: " . $exception->getMessage());
            }
        }
        return $this->conn;
    }
}
?>
