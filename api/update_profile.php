<?php
// Update Profile API Endpoint
// Allows users to update their display name and profile picture

require_once __DIR__ . '/../auth/session_config.php';
require_once __DIR__ . '/../auth/helpers.php';
require_once __DIR__ . '/../../db_config.php';

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

    $display_name = isset($_POST['display_name']) ? trim($_POST['display_name']) : null;
    $profile_picture = isset($_POST['profile_picture_url']) ? trim($_POST['profile_picture_url']) : null;

    $updated = false;

    // Update display name if provided
    if ($display_name && !empty($display_name)) {
        // Validate length
        if (strlen($display_name) > 100) {
            throw new Exception('Display name must be 100 characters or less');
        }

        $stmt = $conn->prepare("UPDATE users SET display_name = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $display_name, $user_id);
        $stmt->execute();
        $stmt->close();
        $updated = true;
    }

    // Update profile picture if provided and valid URL
    if ($profile_picture && !empty($profile_picture)) {
        if (!filter_var($profile_picture, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid profile picture URL');
        }

        $stmt = $conn->prepare("UPDATE users SET profile_picture_url = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $profile_picture, $user_id);
        $stmt->execute();
        $stmt->close();
        $updated = true;
    }

    $conn->close();

    if ($updated) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made']);
    }

} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
