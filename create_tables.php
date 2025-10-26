<?php
require_once 'config/database.php';

// Simple authentication for setup
if (!isset($_GET['setup_key']) || $_GET['setup_key'] !== 'setup123') {
    die('Invalid setup key. Use: create_tables.php?setup_key=setup123');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create owners table
    $db->exec("CREATE TABLE IF NOT EXISTS owners (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create resellers table
    $db->exec("CREATE TABLE IF NOT EXISTS resellers (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        credits INTEGER DEFAULT 0,
        created_by INTEGER REFERENCES owners(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT true
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create maintenance table
    $db->exec("CREATE TABLE IF NOT EXISTS maintenance (
        id SERIAL PRIMARY KEY,
        is_active BOOLEAN DEFAULT false,
        message TEXT,
        updated_by INTEGER REFERENCES owners(id),
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create app_settings table
    $db->exec("CREATE TABLE IF NOT EXISTS app_settings (
        id SERIAL PRIMARY KEY,
        api_key VARCHAR(64) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert default owner
    $hashed_password = password_hash('niketh123', PASSWORD_BCRYPT);
    $db->exec("INSERT INTO owners (username, password) 
               VALUES ('admin', '$hashed_password') 
               ON CONFLICT (username) DO NOTHING");
    
    // Generate API key
    $api_key = bin2hex(random_bytes(32));
    $db->exec("INSERT INTO app_settings (api_key) VALUES ('$api_key') ON CONFLICT DO NOTHING");
    
    echo "<h1>✅ Database Setup Complete!</h1>";
    echo "<p><strong>Default Login:</strong> admin / niketh123</p>";
    echo "<p><strong>API Key:</strong> <code>$api_key</code></p>";
    echo "<p><strong>⚠️ Important:</strong> Delete this file after setup!</p>";
    
} catch(PDOException $e) {
    echo "<h1>❌ Setup Failed</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
