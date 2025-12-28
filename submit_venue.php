<?php
// submit_venue.php - Handle venue submissions
header('Content-Type: application/json');

// TODO: Move these to a config file outside web root
$host = 'sql.punktionary.com';
$user = 'dayjoel';
$pass = 'TETherball99!';
$db   = 'prod_punk';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
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
$booking_contact = isset($_POST['booking_contact']) ? trim($_POST['booking_contact']) : null;
$links = isset($_POST['links']) ? trim($_POST['links']) : null;

// Validate JSON fields if provided
if ($links && !empty($links)) {
    json_decode($links);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid JSON format for links']));
    }
}

// Insert into database
$sql = "INSERT INTO venues (name, type, capacity, street_address, city, state, postal_code, country, age_restriction, booking_contact, links, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ssissssssss', $name, $type, $capacity, $street_address, $city, $state, $postal_code, $country, $age_restriction, $booking_contact, $links);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Venue submitted successfully',
        'id' => $stmt->insert_id
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to submit venue: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>