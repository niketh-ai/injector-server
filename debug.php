<?php
echo "<h1>Debug Information</h1>";

// Check PHP version
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check PostgreSQL extension
echo "<p>PostgreSQL Extension: " . (extension_loaded('pgsql') ? 'Loaded' : 'NOT LOADED') . "</p>";
echo "<p>PDO PostgreSQL: " . (extension_loaded('pdo_pgsql') ? 'Loaded' : 'NOT LOADED') . "</p>";

// Check environment variables
echo "<h2>Environment Variables</h2>";
echo "<p>DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET') . "</p>";
echo "<p>DB_NAME: " . (getenv('DB_NAME') ?: 'NOT SET') . "</p>";
echo "<p>DB_USERNAME: " . (getenv('DB_USERNAME') ?: 'NOT SET') . "</p>";
echo "<p>DB_PORT: " . (getenv('DB_PORT') ?: 'NOT SET') . "</p>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<p style='color: green;'>✅ Database connected successfully!</p>";
        
        // Check if tables exist
        $tables = ['owners', 'resellers', 'app_users', 'maintenance', 'app_settings'];
        foreach ($tables as $table) {
            $stmt = $conn->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = '$table')");
            $exists = $stmt->fetchColumn() ? '✅' : '❌';
            echo "<p>Table $table: $exists</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// Check file permissions
echo "<h2>File Permissions</h2>";
$files = ['config/database.php', 'includes/auth.php', '.htaccess'];
foreach ($files as $file) {
    echo "<p>$file: " . (file_exists($file) ? 'Exists' : 'Missing') . "</p>";
}
?>
