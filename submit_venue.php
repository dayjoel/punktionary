<?php
// submit_venue.php - Handle venue submissions
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1); // Log errors to server log
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../db_config.php';
    require_once __DIR__ . '/auth/session_config.php';
    require_once __DIR__ . '/auth/helpers.php';

    // Require authentication
    if (!is_authenticated()) {
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => 'You must be logged in to submit venues']));
    }

    $user_id = get_current_user_id();

    $conn = get_db_connection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Validate required fields
    if (empty($_POST['name'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Venue name is required']));
    }

    // Collect and sanitize data
    $name = trim($_POST['name']);
    $type = isset($_POST['type']) ? trim($_POST['type']) : null;
    $capacity = isset($_POST['capacity']) && $_POST['capacity'] !== '' ? intval($_POST['capacity']) : null;
    $street_address = isset($_POST['street_address']) ? trim($_POST['street_address']) : null;
    $city = isset($_POST['city']) ? trim($_POST['city']) : null;
    $state = isset($_POST['state']) ? trim($_POST['state']) : null;
    $postal_code = isset($_POST['postal_code']) ? trim($_POST['postal_code']) : null;
    $country = isset($_POST['country']) ? trim($_POST['country']) : null;
    $age_restriction = isset($_POST['age_restriction']) ? trim($_POST['age_restriction']) : null;
    $talent_buyer = isset($_POST['talent_buyer']) ? trim($_POST['talent_buyer']) : null;
    $booking_contact = isset($_POST['booking_contact']) ? trim($_POST['booking_contact']) : null;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    $link_website = isset($_POST['link_website']) ? trim($_POST['link_website']) : null;
    $social_facebook = isset($_POST['social_facebook']) ? trim($_POST['social_facebook']) : null;
    $social_instagram = isset($_POST['social_instagram']) ? trim($_POST['social_instagram']) : null;
    $social_twitter = isset($_POST['social_twitter']) ? trim($_POST['social_twitter']) : null;
    $social_youtube = isset($_POST['social_youtube']) ? trim($_POST['social_youtube']) : null;
    $links = isset($_POST['links']) ? trim($_POST['links']) : null;

    // Validate JSON fields if provided
    if ($links && !empty($links)) {
        json_decode($links);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid JSON format for links']));
        }
    }

    // Build links JSON for legacy field
    $linksArray = [];
    if ($link_website) $linksArray['website'] = $link_website;
    if ($social_facebook) $linksArray['facebook'] = $social_facebook;
    if ($social_instagram) $linksArray['instagram'] = $social_instagram;
    if ($social_twitter) $linksArray['twitter'] = $social_twitter;
    if ($social_youtube) $linksArray['youtube'] = $social_youtube;
    $links = !empty($linksArray) ? json_encode($linksArray) : null;

    // Insert into database with user attribution
    $sql = "INSERT INTO venues (
        submitted_by, name, type, capacity, description, phone,
        street_address, city, state, postal_code,
        age_restriction, talent_buyer, booking_contact,
        social_facebook, social_instagram, social_twitter, social_youtube,
        links, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param(
        'ississsssssssssss',
        $user_id, $name, $type, $capacity, $description, $phone,
        $street_address, $city, $state, $postal_code,
        $age_restriction, $talent_buyer, $booking_contact,
        $social_facebook, $social_instagram, $social_twitter, $social_youtube,
        $links
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Venue submitted successfully',
            'id' => $stmt->insert_id
        ]);
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log('Venue submission error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to submit venue. Please try again.'
    ]);
}
?>