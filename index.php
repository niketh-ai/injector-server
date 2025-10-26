<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$auth->requireAuth();
$functions = new Functions();

// Get statistics
$stats = $functions->getDashboardStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Injector Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dashboard">
    <?php include 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <div class="user-info">
                Welcome, <?php echo $_SESSION['username']; ?> | 
                <a href="login.php?logout=1" class="btn btn-sm btn-danger">Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['total_users']; ?></span>
                <span class="stat-label">Total Users</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['total_resellers']; ?></span>
                <span class="stat-label">Total Resellers</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['active_users']; ?></span>
                <span class="stat-label">Active Users</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['banned_users']; ?></span>
                <span class="stat-label">Banned Users</span>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Maintenance Control</h3>
            </div>
            <div class="card-body">
                <?php
                $maintenance = $functions->getMaintenanceStatus();
                ?>
                <form id="maintenanceForm">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="maintenance_enabled" <?php echo $maintenance['is_active'] ? 'checked' : ''; ?>>
                            Enable Maintenance Mode
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>Maintenance Message</label>
                        <textarea name="maintenance_message" rows="4" placeholder="Enter maintenance message..."><?php echo htmlspecialchars($maintenance['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Maintenance</button>
                </form>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>