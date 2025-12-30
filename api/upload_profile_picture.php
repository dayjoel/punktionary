<?php
// Upload Profile Picture API Endpoint
// Handles profile picture uploads with cleanup of old images

require_once __DIR__ . '/../auth/session_config.php';
require_once __DIR__ . '/../auth/helpers.php';
require_once __DIR__ . '/../../db_config.php';

header('Content-Type: application/json');

// Require authentication
if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    $user_id = get_current_user_id();
    $conn = get_db_connection();

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Check if file was uploaded
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }

    $file = $_FILES['profile_picture'];

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($file['tmp_name']);

    if (!in_array($file_type, $allowed_types)) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
    }

    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File too large. Maximum size is 5MB.');
    }

    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/../uploads/profile_pictures';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Get user's current profile picture for cleanup
    $user_data = get_user_data($user_id);
    $old_picture = $user_data['profile_picture_url'] ?? null;

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (empty($extension)) {
        // Get extension from mime type
        $extension = match($file_type) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg'
        };
    }

    $filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
    $file_path = $upload_dir . '/' . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Failed to save uploaded file');
    }

    // Generate web-accessible URL
    $profile_picture_url = '/uploads/profile_pictures/' . $filename;

    // Update database
    $stmt = $conn->prepare("UPDATE users SET profile_picture_url = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('si', $profile_picture_url, $user_id);

    if (!$stmt->execute()) {
        // If database update fails, delete the uploaded file
        unlink($file_path);
        throw new Exception('Failed to update profile picture in database');
    }

    $stmt->close();

    // Clean up old profile picture if it exists and is a local file
    if ($old_picture && strpos($old_picture, '/uploads/profile_pictures/') === 0) {
        $old_file_path = __DIR__ . '/..' . $old_picture;
        if (file_exists($old_file_path)) {
            unlink($old_file_path);
        }
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Profile picture updated successfully',
        'profile_picture_url' => $profile_picture_url
    ]);

} catch (Exception $e) {
    error_log('Profile picture upload error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
