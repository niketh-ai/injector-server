<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireAuth();
$functions = new Functions();

$message = '';
$error = '';

// Get current settings
$settings = $functions->getAppSettings();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['regenerate_api_key'])) {
        if ($functions->regenerateApiKey()) {
            $message = "API Key regenerated successfully!";
            $settings = $functions->getAppSettings(); // Refresh settings
        } else {
            $error = "Failed to regenerate API key.";
        }
    }
    
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } elseif ($functions->updateOwnerPassword($_SESSION['owner_id'], $current_password, $new_password)) {
            $message = "Password updated successfully!";
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Injector Manager</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard">
    <?php include '../includes/header.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Settings</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>API Settings</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>API Key</label>
                    <div class="api-key-container">
                        <input type="text" id="apiKey" value="<?php echo htmlspecialchars($settings['api_key']); ?>" readonly class="api-key-input">
                        <button type="button" class="btn btn-warning" onclick="copyApiKey()">Copy</button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCsrfToken(); ?>">
                            <button type="submit" name="regenerate_api_key" class="btn btn-danger" 
                                    onclick="return confirm('Are you sure you want to regenerate the API key? This will break any existing app connections until updated.')">
                                Regenerate
                            </button>
                        </form>
                    </div>
                    <small>Use this API key in your injector app for authentication.</small>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Change Password</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" name="update_password" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>System Information</h3>
            </div>
            <div class="card-body">
                <div class="system-info">
                    <div class="info-item">
                        <strong>PHP Version:</strong> <?php echo phpversion(); ?>
                    </div>
                    <div class="info-item">
                        <strong>Database:</strong> PostgreSQL
                    </div>
                    <div class="info-item">
                        <strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                    </div>
                    <div class="info-item">
                        <strong>Total Users:</strong> <?php echo $functions->getDashboardStats()['total_users']; ?>
                    </div>
                    <div class="info-item">
                        <strong>Total Resellers:</strong> <?php echo $functions->getDashboardStats()['total_resellers']; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>