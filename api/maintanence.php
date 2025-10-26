<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireAuth();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
        exit;
    }
    
    $functions = new Functions();
    
    $is_active = isset($_POST['maintenance_enabled']) ? true : false;
    $message = Security::sanitize($_POST['maintenance_message'] ?? '');
    
    if ($functions->updateMaintenance($is_active, $message)) {
        echo json_encode(['success' => true, 'message' => 'Maintenance settings updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update maintenance settings']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>