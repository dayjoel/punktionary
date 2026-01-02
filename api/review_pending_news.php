<?php
// review_pending_news.php - Approve or reject pending news submissions
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

    $user_id = get_current_user_id();
    $conn = get_db_connection();

    // Parse request
    parse_str(file_get_contents('php://input'), $data);

    $news_id = isset($data['id']) ? intval($data['id']) : 0;
    $action = isset($data['action']) ? $data['action'] : '';
    $admin_notes = isset($data['notes']) ? trim($data['notes']) : '';

    if (!$news_id || !in_array($action, ['approve', 'reject'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid request']));
    }

    if ($action === 'approve') {
        // Get the pending news item
        $sql = "SELECT * FROM pending_carousel_news WHERE id = ? AND status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $news_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $news = $result->fetch_assoc();
        $stmt->close();

        if (!$news) {
            http_response_code(404);
            die(json_encode(['success' => false, 'error' => 'News item not found or already reviewed']));
        }

        // Create carousel item from the approved news
        $sql = "INSERT INTO carousel_items (title, description, image_url, link_url, display_order, active, created_by)
                VALUES (?, ?, ?, ?, 0, 1, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssi',
            $news['scraped_title'],
            $news['scraped_description'],
            $news['scraped_image_url'],
            $news['submitted_url'],
            $news['submitted_by']
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to create carousel item');
        }
        $stmt->close();

        // Update status to approved
        $sql = "UPDATE pending_carousel_news
                SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), admin_notes = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isi', $user_id, $admin_notes, $news_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'News approved and added to carousel'
        ]);

    } else {
        // Reject the news
        $sql = "UPDATE pending_carousel_news
                SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), admin_notes = ?
                WHERE id = ? AND status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isi', $user_id, $admin_notes, $news_id);

        if (!$stmt->execute()) {
            throw new Exception('Failed to reject news');
        }

        if ($stmt->affected_rows === 0) {
            http_response_code(404);
            die(json_encode(['success' => false, 'error' => 'News item not found or already reviewed']));
        }

        $stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'News rejected'
        ]);
    }

    $conn->close();

} catch (Exception $e) {
    error_log('Review pending news error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
