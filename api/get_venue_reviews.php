<?php
// get_venue_reviews.php - Get reviews for a venue
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../db_config.php';
    require_once __DIR__ . '/../auth/session_config.php';
    require_once __DIR__ . '/../auth/helpers.php';

    if (empty($_GET['venue_id']) || !is_numeric($_GET['venue_id'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Valid venue ID is required']));
    }

    $venue_id = intval($_GET['venue_id']);
    $conn = get_db_connection();

    // Get current user info if authenticated
    $current_user_id = is_authenticated() ? get_current_user_id() : null;
    $is_admin = is_authenticated() ? get_user_account_type() >= 1 : false;

    // Get reviews with user info
    $sql = "SELECT
                vr.id,
                vr.rating,
                vr.review_text,
                vr.created_at,
                vr.user_id,
                u.display_name as user_name,
                u.profile_picture_url
            FROM venue_reviews vr
            LEFT JOIN users u ON vr.user_id = u.id
            WHERE vr.venue_id = ?
            ORDER BY vr.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $venue_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = [];
    $total_rating = 0;
    $user_has_reviewed = false;

    while ($row = $result->fetch_assoc()) {
        $total_rating += $row['rating'];

        // Check if this is the current user's review
        if ($current_user_id && $row['user_id'] == $current_user_id) {
            $user_has_reviewed = true;
        }

        // Can delete if admin or own review
        $row['can_delete'] = $is_admin || ($current_user_id && $row['user_id'] == $current_user_id);

        $reviews[] = $row;
    }

    $review_count = count($reviews);
    $average_rating = $review_count > 0 ? $total_rating / $review_count : 0;

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'review_count' => $review_count,
        'average_rating' => $average_rating,
        'user_has_reviewed' => $user_has_reviewed
    ]);

} catch (Exception $e) {
    error_log('Get reviews error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load reviews'
    ]);
}
?>
