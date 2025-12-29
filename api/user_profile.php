<?php
// User Profile API Endpoint
// Returns user profile data and statistics

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

    // Get user data
    $user_data = get_user_data($user_id);

    if (!$user_data) {
        throw new Exception('User not found');
    }

    // Get submission counts
    $band_stmt = $conn->prepare("SELECT COUNT(*) as count FROM bands WHERE submitted_by = ?");
    $band_stmt->bind_param('i', $user_id);
    $band_stmt->execute();
    $band_count = $band_stmt->get_result()->fetch_assoc()['count'];
    $band_stmt->close();

    $venue_stmt = $conn->prepare("SELECT COUNT(*) as count FROM venues WHERE submitted_by = ?");
    $venue_stmt->bind_param('i', $user_id);
    $venue_stmt->execute();
    $venue_count = $venue_stmt->get_result()->fetch_assoc()['count'];
    $venue_stmt->close();

    $resource_stmt = $conn->prepare("SELECT COUNT(*) as count FROM resources WHERE submitted_by = ?");
    $resource_stmt->bind_param('i', $user_id);
    $resource_stmt->execute();
    $resource_count = $resource_stmt->get_result()->fetch_assoc()['count'];
    $resource_stmt->close();

    // Get edit counts (where user edited but didn't originally submit)
    $band_edit_stmt = $conn->prepare("SELECT COUNT(*) as count FROM bands WHERE edited_by = ? AND edited_by != submitted_by");
    $band_edit_stmt->bind_param('i', $user_id);
    $band_edit_stmt->execute();
    $band_edit_count = $band_edit_stmt->get_result()->fetch_assoc()['count'];
    $band_edit_stmt->close();

    $venue_edit_stmt = $conn->prepare("SELECT COUNT(*) as count FROM venues WHERE edited_by = ? AND edited_by != submitted_by");
    $venue_edit_stmt->bind_param('i', $user_id);
    $venue_edit_stmt->execute();
    $venue_edit_count = $venue_edit_stmt->get_result()->fetch_assoc()['count'];
    $venue_edit_stmt->close();

    $resource_edit_stmt = $conn->prepare("SELECT COUNT(*) as count FROM resources WHERE edited_by = ? AND edited_by != submitted_by");
    $resource_edit_stmt->bind_param('i', $user_id);
    $resource_edit_stmt->execute();
    $resource_edit_count = $resource_edit_stmt->get_result()->fetch_assoc()['count'];
    $resource_edit_stmt->close();

    $conn->close();

    // Return user profile with statistics
    echo json_encode([
        'success' => true,
        'user' => $user_data,
        'stats' => [
            'submissions' => $band_count + $venue_count + $resource_count,
            'bands_submitted' => $band_count,
            'venues_submitted' => $venue_count,
            'resources_submitted' => $resource_count,
            'edits' => $band_edit_count + $venue_edit_count + $resource_edit_count
        ]
    ]);

} catch (Exception $e) {
    error_log('User profile error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load user profile']);
}
?>
