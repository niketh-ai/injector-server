<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$auth->requireAuth();
$functions = new Functions();

// Get dashboard statistics
$stats = $functions->getDashboardStats();
$maintenance = $functions->getMaintenanceStatus();
$recentUsers = $functions->getRecentUsers(5);
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
            <h1>Dashboard Overview</h1>
            <div class="user-info">
                Welcome, <strong><?php echo $_SESSION['username']; ?></strong> | 
                <a href="login.php?logout=1" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
            </div>
        </div>

        <!-- Maintenance Alert -->
        <?php if ($maintenance['is_active']): ?>
        <div class="alert alert-warning">
            <strong>⚠️ Maintenance Mode Active:</strong> <?php echo htmlspecialchars($maintenance['message']); ?>
        </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['total_users']; ?></span>
                <span class="stat-label">Total Users</span>
                <div class="stat-trend">All registered users</div>
            </div>
            
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['active_users']; ?></span>
                <span class="stat-label">Active Users</span>
                <div class="stat-trend">Currently active subscriptions</div>
            </div>
            
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['total_resellers']; ?></span>
                <span class="stat-label">Resellers</span>
                <div class="stat-trend">Total reseller accounts</div>
            </div>
            
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['banned_users']; ?></span>
                <span class="stat-label">Banned Users</span>
                <div class="stat-trend">Suspended accounts</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Maintenance Control -->
            <div class="card">
                <div class="card-header">
                    <h3>🛠️ Maintenance Control</h3>
                </div>
                <div class="card-body">
                    <form id="maintenanceForm">
                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCsrfToken(); ?>">
                        
                        <div class="form-group">
                            <label class="toggle-switch">
                                <input type="checkbox" name="maintenance_enabled" <?php echo $maintenance['is_active'] ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                                <span class="toggle-label">Enable Maintenance Mode</span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>Maintenance Message</label>
                            <textarea name="maintenance_message" rows="3" placeholder="Enter maintenance message that will show in the app..."><?php echo htmlspecialchars($maintenance['message'] ?? ''); ?></textarea>
                            <small>This message will be displayed to app users when maintenance is active.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Maintenance Settings</button>
                    </form>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3>⚡ Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="pages/users.php" class="quick-action-btn">
                            <span class="icon">👥</span>
                            <span class="label">Manage Users</span>
                        </a>
                        
                        <a href="pages/resellers.php" class="quick-action-btn">
                            <span class="icon">🤝</span>
                            <span class="label">Manage Resellers</span>
                        </a>
                        
                        <a href="pages/settings.php" class="quick-action-btn">
                            <span class="icon">⚙️</span>
                            <span class="label">Settings</span>
                        </a>
                        
                        <button class="quick-action-btn" onclick="exportUserData()">
                            <span class="icon">📊</span>
                            <span class="label">Export Data</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Recently Created Users</h3>
                <a href="pages/users.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentUsers)): ?>
                    <p class="text-muted">No users created yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Created</th>
                                    <th>Expires</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <?php 
                                            $expires = new DateTime($user['expires_at']);
                                            $now = new DateTime();
                                            if ($expires < $now) {
                                                echo '<span class="text-danger">Expired</span>';
                                            } else {
                                                echo date('M j, Y', strtotime($user['expires_at']));
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php 
                                                echo (strtotime($user['expires_at']) > time()) ? 'active' : 'expired';
                                            ?>">
                                                <?php echo (strtotime($user['expires_at']) > time()) ? 'Active' : 'Expired'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Status -->
        <div class="card">
            <div class="card-header">
                <h3>🖥️ System Status</h3>
            </div>
            <div class="card-body">
                <div class="system-status">
                    <div class="status-item">
                        <span class="status-indicator online"></span>
                        <span class="status-label">Web Server</span>
                        <span class="status-value">Online</span>
                    </div>
                    <div class="status-item">
                        <span class="status-indicator online"></span>
                        <span class="status-label">Database</span>
                        <span class="status-value">Connected</span>
                    </div>
                    <div class="status-item">
                        <span class="status-indicator <?php echo $maintenance['is_active'] ? 'maintenance' : 'online'; ?>"></span>
                        <span class="status-label">API Status</span>
                        <span class="status-value"><?php echo $maintenance['is_active'] ? 'Maintenance' : 'Operational'; ?></span>
                    </div>
                    <div class="status-item">
                        <span class="status-indicator online"></span>
                        <span class="status-label">Last Updated</span>
                        <span class="status-value"><?php echo date('M j, Y H:i:s'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        // Maintenance form handling
        document.getElementById('maintenanceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.textContent = 'Updating...';
            submitBtn.disabled = true;
            
            fetch('api/maintenance.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Maintenance settings updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Network error: ' + error, 'error');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });

        function exportUserData() {
            showToast('Export feature coming soon!', 'success');
        }

        // Auto-refresh stats every 30 seconds
        setInterval(() => {
            // You can add real-time stats update here
            console.log('Auto-refreshing dashboard...');
        }, 30000);
    </script>
</body>
</html>