<?php
// update_user_admin.php - Update user account type (permission level)
// account_type: 0 = user, 1 = admin, 2 = god
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../db_config.php';
    require_once __DIR__ . '/../auth/session_config.php';
    require_once __DIR__ . '/../auth/helpers.php';

    // Require authentication and admin status
    if (!is_authenticated()) {
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => 'You must be logged in']));
    }

    $admin_id = get_current_user_id();

    $conn = get_db_connection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Check if current user is admin or god (account_type >= 1)
    $stmt = $conn->prepare("SELECT account_type FROM users WHERE id = ?");
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    if (!$admin || $admin['account_type'] < 1) {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'Admin access required']));
    }

    $current_account_type = $admin['account_type'];

    // Validate required fields
    $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $new_account_type = isset($_POST['account_type']) ? intval($_POST['account_type']) : 0;

    if ($target_user_id <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid user ID']));
    }

    // Validate account_type is 0, 1, or 2
    if ($new_account_type < 0 || $new_account_type > 2) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid account type']));
    }

    // Get target user's current account type
    $target_stmt = $conn->prepare("SELECT account_type FROM users WHERE id = ?");
    $target_stmt->bind_param('i', $target_user_id);
    $target_stmt->execute();
    $target_result = $target_stmt->get_result();
    $target_user = $target_result->fetch_assoc();
    $target_stmt->close();

    if (!$target_user) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'User not found']));
    }

    $target_current_type = $target_user['account_type'];

    // Prevent admin from modifying their own privileges
    if ($target_user_id === $admin_id) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'You cannot modify your own privileges']));
    }

    // Admins (type 1) cannot modify god accounts (type 2) or create god accounts
    if ($current_account_type < 2) {
        if ($target_current_type == 2) {
            http_response_code(403);
            die(json_encode(['success' => false, 'error' => 'You cannot modify god-tier accounts']));
        }
        if ($new_account_type == 2) {
            http_response_code(403);
            die(json_encode(['success' => false, 'error' => 'You cannot create god-tier accounts']));
        }
    }

    // Update user account type
    $update_stmt = $conn->prepare("UPDATE users SET account_type = ? WHERE id = ?");
    $update_stmt->bind_param('ii', $new_account_type, $target_user_id);

    if ($update_stmt->execute()) {
        $update_stmt->close();
        $conn->close();

        $type_names = [0 => 'user', 1 => 'admin', 2 => 'god'];
        echo json_encode([
            'success' => true,
            'message' => "User privileges updated to {$type_names[$new_account_type]}"
        ]);
    } else {
        throw new Exception('Update failed: ' . $update_stmt->error);
    }

} catch (Exception $e) {
    error_log('Update user admin error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
