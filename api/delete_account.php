<?php
// Delete Account API Endpoint
// Allows users to permanently delete their account
// Note: Submissions are preserved (foreign key ON DELETE SET NULL)

require_once __DIR__ . '/../auth/session_config.php';
require_once __DIR__ . '/../auth/helpers.php';
require_once __DIR__ . '/../db_config.php';

header('Content-Type: application/json');

// Require authentication
if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    $user_id = get_current_user_id();
    $conn = get_db_connection();

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Delete user from database
    // Submissions will have their submitted_by set to NULL (preserved anonymously)
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    $conn->close();

    if ($affected_rows > 0) {
        // Destroy session
        $_SESSION = [];

        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destroy session on server
        session_destroy();

        echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
    } else {
        throw new Exception('User not found or already deleted');
    }

} catch (Exception $e) {
    error_log('Account deletion error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete account']);
}
?>
