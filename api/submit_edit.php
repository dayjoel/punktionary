<?php
// submit_edit.php - Submit edit suggestions for existing bands/venues
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../db_config.php';
    require_once __DIR__ . '/../auth/session_config.php';
    require_once __DIR__ . '/../auth/helpers.php';

    // Require authentication
    if (!is_authenticated()) {
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => 'You must be logged in to suggest edits']));
    }

    $user_id = get_current_user_id();

    $conn = get_db_connection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Validate required fields
    $entity_type = isset($_POST['entity_type']) ? $_POST['entity_type'] : 'band';
    $entity_id = isset($_POST['band_id']) ? intval($_POST['band_id']) : (isset($_POST['venue_id']) ? intval($_POST['venue_id']) : 0);

    if ($entity_id <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid entity ID']));
    }

    // Collect all changed fields
    $field_changes = [];
    $allowed_fields = ['name', 'genre', 'city', 'state', 'country', 'albums', 'links', 'active', 'logo'];

    foreach ($allowed_fields as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== '') {
            $field_changes[$field] = trim($_POST[$field]);
        }
    }

    if (empty($field_changes)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'No changes submitted']));
    }

    // Insert into pending_edits table
    $sql = "INSERT INTO pending_edits (entity_type, entity_id, submitted_by, field_changes, status, created_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $changes_json = json_encode($field_changes);
    $stmt->bind_param('ssis', $entity_type, $entity_id, $user_id, $changes_json);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Edit suggestion submitted successfully',
            'id' => $stmt->insert_id
        ]);
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log('Edit submission error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to submit edit suggestion. Please try again.'
    ]);
}
?>
