<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireAuth();
$functions = new Functions();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_user':
            $username = Security::sanitize($_POST['username']);
            $password = Security::sanitize($_POST['password']);
            $days = intval($_POST['days']);
            
            if ($functions->createUser($username, $password, $days, $_SESSION['owner_id'], 'owner')) {
                echo json_encode(['success' => true, 'message' => 'User created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create user']);
            }
            break;

        case 'ban_user':
            $user_id = intval($_POST['user_id']);
            if ($functions->banUser($user_id)) {
                echo json_encode(['success' => true, 'message' => 'User banned successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to ban user']);
            }
            break;

        case 'unban_user':
            $user_id = intval($_POST['user_id']);
            if ($functions->unbanUser($user_id)) {
                echo json_encode(['success' => true, 'message' => 'User unbanned successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to unban user']);
            }
            break;

        case 'delete_user':
            $user_id = intval($_POST['user_id']);
            if ($functions->deleteUser($user_id)) {
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
            }
            break;

        case 'add_reseller':
            $username = Security::sanitize($_POST['username']);
            $password = Security::sanitize($_POST['password']);
            $credits = intval($_POST['credits']);
            
            if ($functions->createReseller($username, $password, $credits, $_SESSION['owner_id'])) {
                echo json_encode(['success' => true, 'message' => 'Reseller created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create reseller']);
            }
            break;

        case 'update_credits':
            $reseller_id = intval($_POST['reseller_id']);
            $credits = intval($_POST['credits']);
            
            if ($functions->updateResellerCredits($reseller_id, $credits)) {
                echo json_encode(['success' => true, 'message' => 'Credits updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update credits']);
            }
            break;

        case 'delete_reseller':
            $reseller_id = intval($_POST['reseller_id']);
            if ($functions->deleteReseller($reseller_id)) {
                echo json_encode(['success' => true, 'message' => 'Reseller deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete reseller']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>