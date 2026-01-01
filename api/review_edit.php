<?php
// review_edit.php - Approve or reject edit suggestions
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

    $user_id = get_current_user_id();

    $conn = get_db_connection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Check if user is admin
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || !$user['is_admin']) {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'Admin access required']));
    }

    // Validate required fields
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $admin_notes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : null;

    if ($edit_id <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid edit ID']));
    }

    if (!in_array($action, ['approve', 'reject'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid action']));
    }

    // Fetch the pending edit
    $stmt = $conn->prepare("SELECT * FROM pending_edits WHERE id = ? AND status = 'pending'");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit = $result->fetch_assoc();
    $stmt->close();

    if (!$edit) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Edit not found or already reviewed']));
    }

    $new_status = $action === 'approve' ? 'approved' : 'rejected';

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update the pending_edits record
        $update_sql = "UPDATE pending_edits
                       SET status = ?, reviewed_by = ?, reviewed_at = NOW(), admin_notes = ?
                       WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('sisi', $new_status, $user_id, $admin_notes, $edit_id);
        $update_stmt->execute();
        $update_stmt->close();

        // If approved, apply changes to the actual entity
        if ($action === 'approve') {
            $field_changes = json_decode($edit['field_changes'], true);

            // Build UPDATE query based on entity type
            $update_fields = [];
            $update_values = [];
            $update_types = '';

            foreach ($field_changes as $field => $value) {
                $update_fields[] = "$field = ?";
                $update_values[] = $value;
                $update_types .= 's';
            }

            // Add updated_at timestamp
            $update_fields[] = "updated_at = NOW()";

            if (!empty($update_fields)) {
                $table = $edit['entity_type'] === 'band' ? 'bands' :
                        ($edit['entity_type'] === 'venue' ? 'venues' : 'resources');

                $apply_sql = "UPDATE $table SET " . implode(', ', $update_fields) . " WHERE id = ?";
                $apply_stmt = $conn->prepare($apply_sql);

                // Add entity_id to values
                $update_values[] = $edit['entity_id'];
                $update_types .= 'i';

                // Bind parameters dynamically
                $bind_params = array_merge([$update_types], $update_values);
                $tmp = [];
                foreach ($bind_params as $key => $value) {
                    $tmp[$key] = &$bind_params[$key];
                }
                call_user_func_array([$apply_stmt, 'bind_param'], $tmp);

                $apply_stmt->execute();
                $apply_stmt->close();
            }
        }

        // Commit transaction
        $conn->commit();

        $conn->close();

        echo json_encode([
            'success' => true,
            'message' => $action === 'approve' ?
                'Edit approved and applied successfully' :
                'Edit rejected successfully',
            'action' => $action
        ]);

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Review edit error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to process review. Please try again.'
    ]);
}
?>
