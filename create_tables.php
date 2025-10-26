<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Enable UUID extension if not exists
    $db->exec("CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\"");
    
    // Create owners table
    $db->exec("CREATE TABLE IF NOT EXISTS owners (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        last_login TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create resellers table
    $db->exec("CREATE TABLE IF NOT EXISTS resellers (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        credits INTEGER DEFAULT 0,
        max_users INTEGER DEFAULT 100,
        created_by INTEGER REFERENCES owners(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT true,
        last_active TIMESTAMP
    )");
    
    // Create app_users table
    $db->exec("CREATE TABLE IF NOT EXISTS app_users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        days INTEGER NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_by INTEGER,
        creator_type VARCHAR(10) CHECK (creator_type IN ('owner', 'reseller')),
        is_banned BOOLEAN DEFAULT false,
        ban_reason TEXT,
        last_login TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create maintenance table
    $db->exec("CREATE TABLE IF NOT EXISTS maintenance (
        id SERIAL PRIMARY KEY,
        is_active BOOLEAN DEFAULT false,
        message TEXT,
        updated_by INTEGER REFERENCES owners(id),
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create app_settings table
    $db->exec("CREATE TABLE IF NOT EXISTS app_settings (
        id SERIAL PRIMARY KEY,
        api_key VARCHAR(64) UNIQUE NOT NULL,
        app_version VARCHAR(20) DEFAULT '1.0.0',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create user_sessions table for app users
    $db->exec("CREATE TABLE IF NOT EXISTS user_sessions (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES app_users(id),
        session_token VARCHAR(64) UNIQUE NOT NULL,
        ip_address INET,
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL
    )");
    
    // Create credit_packages table
    $db->exec("CREATE TABLE IF NOT EXISTS credit_packages (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        credits INTEGER NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        is_active BOOLEAN DEFAULT true,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert default owner (username: admin, password: niketh123)
    $hashed_password = password_hash('niketh123', PASSWORD_BCRYPT);
    $db->exec("INSERT INTO owners (username, password) 
               VALUES ('admin', '$hashed_password') 
               ON CONFLICT (username) DO NOTHING");
    
    // Generate API key
    $api_key = bin2hex(random_bytes(32));
    $db->exec("INSERT INTO app_settings (api_key) VALUES ('$api_key') ON CONFLICT DO NOTHING");
    
    // Insert sample credit packages
    $db->exec("INSERT INTO credit_packages (name, credits, price) VALUES 
               ('Starter', 100, 10.00),
               ('Professional', 500, 45.00),
               ('Enterprise', 1000, 80.00)
               ON CONFLICT DO NOTHING");
    
    echo "<div style='font-family: Arial, sans-serif; padding: 20px;'>";
    echo "<h2 style='color: #27ae60;'>Database Setup Complete! 🎉</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Default Login Credentials:</strong><br>";
    echo "Username: <code>admin</code><br>";
    echo "Password: <code>niketh123</code>";
    echo "</div>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>API Key (Save this for your app):</strong><br>";
    echo "<code style='background: #fff; padding: 10px; display: block; margin: 10px 0; font-family: monospace;'>" . $api_key . "</code>";
    echo "</div>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Delete this file (<code>create_tables.php</code>) for security</li>";
    echo "<li>Login to your admin panel</li>";
    echo "<li>Configure your settings</li>";
    echo "<li>Start adding resellers and users</li>";
    echo "</ol>";
    echo "<p style='color: #e74c3c;'><strong>Security Warning:</strong> Remember to delete this file after setup!</p>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='font-family: Arial, sans-serif; padding: 20px; color: #e74c3c;'>";
    echo "<h2>Database Setup Failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "</div>";
}
?>