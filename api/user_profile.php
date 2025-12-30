<?php
// User Profile API Endpoint
// Returns user profile data and statistics

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth/session_config.php';
require_once __DIR__ . '/../auth/helpers.php';
require_once __DIR__ . '/../db_config.php';

header('Content-Type: application/json');

// Debug logging
error_log('User profile accessed - Session ID: ' . session_id());
error_log('Is authenticated: ' . (is_authenticated() ? 'yes' : 'no'));
error_log('User ID: ' . get_current_user_id());

// Require authentication
if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Not authenticated',
        'debug' => [
            'session_id' => session_id(),
            'session_data' => $_SESSION
        ]
    ]);
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
    $band_count = 0;
    $venue_count = 0;
    $resource_count = 0;
    $band_edit_count = 0;
    $venue_edit_count = 0;
    $resource_edit_count = 0;

    // Check if bands table has submitted_by column
    $band_stmt = $conn->prepare("SELECT COUNT(*) as count FROM bands WHERE submitted_by = ?");
    if ($band_stmt) {
        $band_stmt->bind_param('i', $user_id);
        $band_stmt->execute();
        $result = $band_stmt->get_result();
        if ($result) {
            $band_count = $result->fetch_assoc()['count'];
        }
        $band_stmt->close();
    }

    // Check if venues table has submitted_by column
    $venue_stmt = $conn->prepare("SELECT COUNT(*) as count FROM venues WHERE submitted_by = ?");
    if ($venue_stmt) {
        $venue_stmt->bind_param('i', $user_id);
        $venue_stmt->execute();
        $result = $venue_stmt->get_result();
        if ($result) {
            $venue_count = $result->fetch_assoc()['count'];
        }
        $venue_stmt->close();
    }

    // Check if resources table exists and has submitted_by column
    $resource_stmt = $conn->prepare("SELECT COUNT(*) as count FROM resources WHERE submitted_by = ?");
    if ($resource_stmt) {
        $resource_stmt->bind_param('i', $user_id);
        if ($resource_stmt->execute()) {
            $result = $resource_stmt->get_result();
            if ($result) {
                $resource_count = $result->fetch_assoc()['count'];
            }
        }
        $resource_stmt->close();
    }

    // Get edit counts (where user edited but didn't originally submit)
    $band_edit_stmt = $conn->prepare("SELECT COUNT(*) as count FROM bands WHERE edited_by = ? AND edited_by != submitted_by");
    if ($band_edit_stmt) {
        $band_edit_stmt->bind_param('i', $user_id);
        if ($band_edit_stmt->execute()) {
            $result = $band_edit_stmt->get_result();
            if ($result) {
                $band_edit_count = $result->fetch_assoc()['count'];
            }
        }
        $band_edit_stmt->close();
    }

    $venue_edit_stmt = $conn->prepare("SELECT COUNT(*) as count FROM venues WHERE edited_by = ? AND edited_by != submitted_by");
    if ($venue_edit_stmt) {
        $venue_edit_stmt->bind_param('i', $user_id);
        if ($venue_edit_stmt->execute()) {
            $result = $venue_edit_stmt->get_result();
            if ($result) {
                $venue_edit_count = $result->fetch_assoc()['count'];
            }
        }
        $venue_edit_stmt->close();
    }

    $resource_edit_stmt = $conn->prepare("SELECT COUNT(*) as count FROM resources WHERE edited_by = ? AND edited_by != submitted_by");
    if ($resource_edit_stmt) {
        $resource_edit_stmt->bind_param('i', $user_id);
        if ($resource_edit_stmt->execute()) {
            $result = $resource_edit_stmt->get_result();
            if ($result) {
                $resource_edit_count = $result->fetch_assoc()['count'];
            }
        }
        $resource_edit_stmt->close();
    }

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
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load user profile',
        'debug' => $e->getMessage() // Remove this in production
    ]);
}
?>
