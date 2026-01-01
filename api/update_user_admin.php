<?php
// update_user_admin.php - Update user admin status
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

    // Check if current user is admin
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    if (!$admin || !$admin['is_admin']) {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'Admin access required']));
    }

    // Validate required fields
    $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $is_admin = isset($_POST['is_admin']) ? intval($_POST['is_admin']) : 0;

    if ($target_user_id <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid user ID']));
    }

    // Prevent admin from removing their own admin status
    if ($target_user_id === $admin_id && $is_admin == 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'You cannot remove your own admin privileges']));
    }

    // Update user admin status
    $update_stmt = $conn->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
    $update_stmt->bind_param('ii', $is_admin, $target_user_id);

    if ($update_stmt->execute()) {
        $update_stmt->close();
        $conn->close();

        echo json_encode([
            'success' => true,
            'message' => 'User privileges updated successfully'
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
