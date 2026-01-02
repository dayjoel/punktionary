<?php
// delete_venue_review.php - Delete a venue review (admin or own review)
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
        die(json_encode(['success' => false, 'error' => 'You must be logged in']));
    }

    $user_id = get_current_user_id();
    $account_type = get_user_account_type();
    $is_admin = $account_type >= 1;

    // Parse POST data
    parse_str(file_get_contents('php://input'), $post_data);

    if (empty($post_data['review_id']) || !is_numeric($post_data['review_id'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Valid review ID is required']));
    }

    $review_id = intval($post_data['review_id']);
    $conn = get_db_connection();

    // Get review to check ownership
    $check_stmt = $conn->prepare("SELECT user_id FROM venue_reviews WHERE id = ?");
    $check_stmt->bind_param('i', $review_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Review not found']));
    }

    $review = $result->fetch_assoc();
    $check_stmt->close();

    // Check if user can delete (admin or owner)
    if (!$is_admin && $review['user_id'] != $user_id) {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'You do not have permission to delete this review']));
    }

    // Delete review
    $delete_stmt = $conn->prepare("DELETE FROM venue_reviews WHERE id = ?");
    $delete_stmt->bind_param('i', $review_id);

    if ($delete_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Review deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete review');
    }

    $delete_stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log('Delete review error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete review'
    ]);
}
?>
