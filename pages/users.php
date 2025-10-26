<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireAuth();
$functions = new Functions();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $username = Security::sanitize($_POST['username']);
        $password = Security::sanitize($_POST['password']);
        $days = intval($_POST['days']);
        
        if ($functions->createUser($username, $password, $days, $_SESSION['owner_id'], 'owner')) {
            $message = "User created successfully!";
        } else {
            $error = "Failed to create user. Username might already exist.";
        }
    }
    
    if (isset($_POST['ban_user'])) {
        $user_id = intval($_POST['user_id']);
        if ($functions->banUser($user_id)) {
            $message = "User banned successfully!";
        } else {
            $error = "Failed to ban user.";
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        if ($functions->deleteUser($user_id)) {
            $message = "User deleted successfully!";
        } else {
            $error = "Failed to delete user.";
        }
    }
}

// Get all users
$users = $functions->getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Injector Manager</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard">
    <?php include '../includes/header.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Users Management</h1>
            <div class="user-info">
                <button class="btn btn-primary" onclick="openAddUserModal()">Add New User</button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>All Users</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Days Left</th>
                                <th>Expires At</th>
                                <th>Created By</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <?php 
                                        $expires = new DateTime($user['expires_at']);
                                        $now = new DateTime();
                                        $diff = $now->diff($expires);
                                        echo $diff->days > 0 ? $diff->days : 'Expired';
                                        ?>
                                    </td>
                                    <td><?php echo date('M j, Y H:i', strtotime($user['expires_at'])); ?></td>
                                    <td>
                                        <?php 
                                        if ($user['creator_type'] == 'owner') {
                                            echo 'Owner';
                                        } else {
                                            echo 'Reseller ' . $user['created_by'];
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php 
                                            echo $user['is_banned'] ? 'banned' : 
                                                (strtotime($user['expires_at']) > time() ? 'active' : 'expired'); 
                                        ?>">
                                            <?php 
                                            echo $user['is_banned'] ? 'Banned' : 
                                                (strtotime($user['expires_at']) > time() ? 'Active' : 'Expired'); 
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if (!$user['is_banned']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCsrfToken(); ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="ban_user" class="btn btn-warning btn-sm" 
                                                            onclick="return confirm('Are you sure you want to ban this user?')">
                                                        Ban
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCsrfToken(); ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="unban_user" class="btn btn-success btn-sm">
                                                        Unban
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCsrfToken(); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete_user" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New User</h3>
                <span class="close" onclick="closeAddUserModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="addUserForm">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="days">Days</label>
                        <input type="number" id="days" name="days" min="1" max="365" value="30" required>
                    </div>
                    
                    <button type="submit" name="add_user" class="btn btn-primary">Create User</button>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>