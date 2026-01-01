<?php
// get_pending_edits.php - Fetch pending edits for admin review
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

    // Check if user is admin or god (account_type >= 1)
    $stmt = $conn->prepare("SELECT account_type FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || $user['account_type'] < 1) {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'Admin access required']));
    }

    // Get filter status (default: pending)
    $status = isset($_GET['status']) ? $_GET['status'] : 'pending';
    $allowed_statuses = ['pending', 'approved', 'rejected'];
    if (!in_array($status, $allowed_statuses)) {
        $status = 'pending';
    }

    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = isset($_GET['per_page']) ? min(100, max(10, intval($_GET['per_page']))) : 20;
    $offset = ($page - 1) * $per_page;

    // Get date filter (for approved/rejected)
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

    // Build WHERE clause
    $where_conditions = ["pe.status = ?"];
    $params = [$status];
    $param_types = 's';

    if ($start_date && ($status === 'approved' || $status === 'rejected')) {
        $where_conditions[] = "pe.reviewed_at >= ?";
        $params[] = $start_date . ' 00:00:00';
        $param_types .= 's';
    }

    if ($end_date && ($status === 'approved' || $status === 'rejected')) {
        $where_conditions[] = "pe.reviewed_at <= ?";
        $params[] = $end_date . ' 23:59:59';
        $param_types .= 's';
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total
                  FROM pending_edits pe
                  WHERE $where_clause";

    $count_stmt = $conn->prepare($count_sql);
    if (!$count_stmt) {
        throw new Exception('Count prepare failed: ' . $conn->error);
    }

    // Bind parameters for count query
    $count_bind_params = $params;
    array_unshift($count_bind_params, $param_types);
    $tmp = [];
    foreach ($count_bind_params as $key => $value) {
        $tmp[$key] = &$count_bind_params[$key];
    }
    call_user_func_array([$count_stmt, 'bind_param'], $tmp);

    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_count = $count_result->fetch_assoc()['total'];
    $count_stmt->close();

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
                u.display_name as submitted_by_username,
                u.email as submitted_by_email,
                reviewer.display_name as reviewed_by_username
            FROM pending_edits pe
            JOIN users u ON pe.submitted_by = u.id
            LEFT JOIN users reviewer ON pe.reviewed_by = reviewer.id
            WHERE $where_clause
            ORDER BY " . ($status === 'pending' ? 'pe.created_at' : 'pe.reviewed_at') . " DESC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    // Bind parameters for main query
    $params[] = $per_page;
    $params[] = $offset;
    $param_types .= 'ii';

    array_unshift($params, $param_types);
    $tmp = [];
    foreach ($params as $key => $value) {
        $tmp[$key] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $tmp);
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

            // Decode JSON fields in band data
            if ($entity_data) {
                if (isset($entity_data['albums']) && is_string($entity_data['albums'])) {
                    $entity_data['albums'] = json_decode($entity_data['albums'], true);
                }
                if (isset($entity_data['links']) && is_string($entity_data['links'])) {
                    $entity_data['links'] = json_decode($entity_data['links'], true);
                }
            }
        } elseif ($row['entity_type'] === 'venue') {
            $entity_stmt = $conn->prepare("SELECT * FROM venues WHERE id = ?");
            $entity_stmt->bind_param('i', $row['entity_id']);
            $entity_stmt->execute();
            $entity_result = $entity_stmt->get_result();
            $entity_data = $entity_result->fetch_assoc();
            $entity_stmt->close();

            // Decode JSON fields in venue data
            if ($entity_data) {
                if (isset($entity_data['links']) && is_string($entity_data['links'])) {
                    $entity_data['links'] = json_decode($entity_data['links'], true);
                }
            }
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

    $total_pages = ceil($total_count / $per_page);

    echo json_encode([
        'success' => true,
        'edits' => $edits,
        'counts' => $counts,
        'pagination' => [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total_count,
            'total_pages' => $total_pages,
            'has_prev' => $page > 1,
            'has_next' => $page < $total_pages
        ]
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
