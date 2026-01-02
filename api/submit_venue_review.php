<?php
// submit_venue_review.php - Submit venue review
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
        die(json_encode(['success' => false, 'error' => 'You must be logged in to submit a review']));
    }

    $user_id = get_current_user_id();
    $conn = get_db_connection();

    // Validate required fields
    if (empty($_POST['venue_id']) || !is_numeric($_POST['venue_id'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Valid venue ID is required']));
    }

    if (empty($_POST['rating']) || !is_numeric($_POST['rating'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Rating is required']));
    }

    $venue_id = intval($_POST['venue_id']);
    $rating = intval($_POST['rating']);
    $review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : null;

    // Validate rating range
    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5']));
    }

    // Check if venue exists
    $venue_check = $conn->prepare("SELECT id FROM venues WHERE id = ?");
    $venue_check->bind_param('i', $venue_id);
    $venue_check->execute();
    if ($venue_check->get_result()->num_rows === 0) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Venue not found']));
    }
    $venue_check->close();

    // Check if user has already reviewed this venue
    $check_stmt = $conn->prepare("SELECT id FROM venue_reviews WHERE venue_id = ? AND user_id = ?");
    $check_stmt->bind_param('ii', $venue_id, $user_id);
    $check_stmt->execute();
    $existing = $check_stmt->get_result();

    if ($existing->num_rows > 0) {
        // Update existing review
        $sql = "UPDATE venue_reviews SET rating = ?, review_text = ?, updated_at = NOW() WHERE venue_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isii', $rating, $review_text, $venue_id, $user_id);
    } else {
        // Insert new review
        $sql = "INSERT INTO venue_reviews (venue_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iiis', $venue_id, $user_id, $rating, $review_text);
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Review submitted successfully'
        ]);
    } else {
        throw new Exception('Failed to save review');
    }

    $stmt->close();
    $check_stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log('Submit review error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to submit review'
    ]);
}
?>
