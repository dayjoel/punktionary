<?php
// get_pending_news_count.php - Get count of pending carousel news
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

    // Get count of pending news
    $sql = "SELECT COUNT(*) as pending_count FROM pending_carousel_news WHERE status = 'pending'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    echo json_encode([
        'success' => true,
        'count' => (int)$row['pending_count']
    ]);

    $conn->close();

} catch (Exception $e) {
    error_log('Get pending news count error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
