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
    if (isset($_POST['add_reseller'])) {
        $username = Security::sanitize($_POST['username']);
        $password = Security::sanitize($_POST['password']);
        $credits = intval($_POST['credits']);
        
        if ($functions->createReseller($username, $password, $credits, $_SESSION['owner_id'])) {
            $message = "Reseller created successfully!";
        } else {
            $error = "Failed to create reseller. Username might already exist.";
        }
    }
    
    if (isset($_POST['update_credits'])) {
        $reseller_id = intval($_POST['reseller_id']);
        $credits = intval($_POST['credits']);
        
        if ($functions->updateResellerCredits($reseller_id, $credits)) {
            $message = "Credits updated successfully!";
        } else {
            $error = "Failed to update credits.";
        }
    }
    
    if (isset($_POST['delete_reseller'])) {
        $reseller_id = intval($_POST['reseller_id']);
        if ($functions->deleteReseller($reseller_id)) {
            $message = "Reseller deleted successfully!";
        } else {
            $error = "Failed to delete reseller.";
        }
    }
}

// Get all resellers
$resellers = $functions->getAllResellers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resellers Management - Injector Manager</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard">
    <?php include '../includes/header.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Resellers Management</h1>
            <div class="user-info">
                <button class="btn btn-primary" onclick="openAddResellerModal()">Add New Reseller</button>
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
                <h3>All Resellers</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Credits</th>
                                <th>Users Created</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resellers as $reseller): ?>
                                <tr>
                                    <td><?php echo $reseller['id']; ?></td>
                                    <td><?php echo htmlspecialchars($reseller['username']); ?></td>
                                    <td>
                                        <span id="credits-<?php echo $reseller['id']; ?>">
                                            <?php echo $reseller['credits']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $functions->getResellerUserCount($reseller['id']); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $reseller['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $reseller['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($reseller['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-warning btn-sm" 
                                                    onclick="openEditCreditsModal(<?php echo $reseller['id']; ?>, <?php echo $reseller['credits']; ?>)">
                                                Edit Credits
                                            </button>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCsrfToken(); ?>">
                                                <input type="hidden" name="reseller_id" value="<?php echo $reseller['id']; ?>">
                                                <button type="submit" name="delete_reseller" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Are you sure you want to delete this reseller? All their users will also be deleted.')">
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

    <!-- Add Reseller Modal -->
    <div id="addResellerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Reseller</h3>
                <span class="close" onclick="closeAddResellerModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="addResellerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="reseller_username">Username</label>
                        <input type="text" id="reseller_username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="reseller_password">Password</label>
                        <input type="password" id="reseller_password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="credits">Initial Credits</label>
                        <input type="number" id="credits" name="credits" min="0" value="100" required>
                        <small>Each credit allows creating 1 day for a user</small>
                    </div>
                    
                    <button type="submit" name="add_reseller" class="btn btn-primary">Create Reseller</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Credits Modal -->
    <div id="editCreditsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Reseller Credits</h3>
                <span class="close" onclick="closeEditCreditsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="editCreditsForm">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCsrfToken(); ?>">
                    <input type="hidden" id="edit_reseller_id" name="reseller_id">
                    
                    <div class="form-group">
                        <label for="edit_credits">Credits</label>
                        <input type="number" id="edit_credits" name="credits" min="0" required>
                        <small>Each credit allows creating 1 day for a user</small>
                    </div>
                    
                    <button type="submit" name="update_credits" class="btn btn-primary">Update Credits</button>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>