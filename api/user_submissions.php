<?php
// User Submissions API Endpoint
// Returns all submissions made by the authenticated user

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

    // Get user's bands
    $band_stmt = $conn->prepare(
        "SELECT id, name, genre, city, state, created_at FROM bands
         WHERE submitted_by = ? ORDER BY created_at DESC"
    );
    $band_stmt->bind_param('i', $user_id);
    $band_stmt->execute();
    $bands_result = $band_stmt->get_result();
    $bands = [];
    while ($row = $bands_result->fetch_assoc()) {
        $bands[] = $row;
    }
    $band_stmt->close();

    // Get user's venues
    $venue_stmt = $conn->prepare(
        "SELECT id, name, type, city, state, created_at FROM venues
         WHERE submitted_by = ? ORDER BY created_at DESC"
    );
    $venue_stmt->bind_param('i', $user_id);
    $venue_stmt->execute();
    $venues_result = $venue_stmt->get_result();
    $venues = [];
    while ($row = $venues_result->fetch_assoc()) {
        $venues[] = $row;
    }
    $venue_stmt->close();

    // Get user's resources
    $resource_stmt = $conn->prepare(
        "SELECT id, name, link, description, created_at FROM resources
         WHERE submitted_by = ? ORDER BY created_at DESC"
    );
    $resource_stmt->bind_param('i', $user_id);
    $resource_stmt->execute();
    $resources_result = $resource_stmt->get_result();
    $resources = [];
    while ($row = $resources_result->fetch_assoc()) {
        $resources[] = $row;
    }
    $resource_stmt->close();

    $conn->close();

    echo json_encode([
        'success' => true,
        'bands' => $bands,
        'venues' => $venues,
        'resources' => $resources
    ]);

} catch (Exception $e) {
    error_log('User submissions error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load submissions']);
}
?>
