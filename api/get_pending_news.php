<?php
// get_pending_news.php - Get pending carousel news submissions
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../db_config.php';
    require_once __DIR__ . '/../auth/session_config.php';
    require_once __DIR__ . '/../auth/helpers.php';

    // Require admin authentication
    if (!is_authenticated() || get_user_account_type() < 1) {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'Admin access required']));
    }

    $conn = get_db_connection();

    // Get pending news items with submitter info
    $sql = "SELECT
                pcn.*,
                u.display_name as submitted_by_name,
                u.email as submitted_by_email
            FROM pending_carousel_news pcn
            LEFT JOIN users u ON pcn.submitted_by = u.id
            WHERE pcn.status = 'pending'
            ORDER BY pcn.created_at DESC";

    $result = $conn->query($sql);
    $items = [];

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);

    $conn->close();

} catch (Exception $e) {
    error_log('Get pending news error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
