<?php
// get_pending_edits.php - Fetch pending edits for admin review
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

    // Get filter status (default: pending)
    $status = isset($_GET['status']) ? $_GET['status'] : 'pending';
    $allowed_statuses = ['pending', 'approved', 'rejected'];
    if (!in_array($status, $allowed_statuses)) {
        $status = 'pending';
    }

    // Fetch pending edits with user info
    $sql = "SELECT
                pe.id,
                pe.entity_type,
                pe.entity_id,
                pe.field_changes,
                pe.status,
                pe.admin_notes,
                pe.created_at,
                pe.reviewed_at,
                pe.reviewed_by,
                u.name as submitted_by_username,
                u.email as submitted_by_email,
                reviewer.name as reviewed_by_username
            FROM pending_edits pe
            JOIN users u ON pe.submitted_by = u.id
            LEFT JOIN users reviewer ON pe.reviewed_by = reviewer.id
            WHERE pe.status = ?
            ORDER BY pe.created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('s', $status);
    $stmt->execute();
    $result = $stmt->get_result();

    $edits = [];
    while ($row = $result->fetch_assoc()) {
        // Decode field_changes JSON
        $row['field_changes'] = json_decode($row['field_changes'], true);

        // Fetch original entity data based on type
        $entity_data = null;
        if ($row['entity_type'] === 'band') {
            $entity_stmt = $conn->prepare("SELECT * FROM bands WHERE id = ?");
            $entity_stmt->bind_param('i', $row['entity_id']);
            $entity_stmt->execute();
            $entity_result = $entity_stmt->get_result();
            $entity_data = $entity_result->fetch_assoc();
            $entity_stmt->close();
        } elseif ($row['entity_type'] === 'venue') {
            $entity_stmt = $conn->prepare("SELECT * FROM venues WHERE id = ?");
            $entity_stmt->bind_param('i', $row['entity_id']);
            $entity_stmt->execute();
            $entity_result = $entity_stmt->get_result();
            $entity_data = $entity_result->fetch_assoc();
            $entity_stmt->close();
        }

        $row['original_data'] = $entity_data;
        $edits[] = $row;
    }

    $stmt->close();

    // Get counts for each status
    $counts_sql = "SELECT status, COUNT(*) as count FROM pending_edits GROUP BY status";
    $counts_result = $conn->query($counts_sql);
    $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    while ($count_row = $counts_result->fetch_assoc()) {
        $counts[$count_row['status']] = (int)$count_row['count'];
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'edits' => $edits,
        'counts' => $counts
    ]);

} catch (Exception $e) {
    error_log('Get pending edits error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
