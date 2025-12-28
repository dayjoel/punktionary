<?php
// get_venue.php - Fetch single venue details by ID
header('Content-Type: application/json');

// TODO: Move these to a config file outside web root
$host = 'sql.punktionary.com';
$user = 'dayjoel';
$pass = 'TETherball99!';
$db   = 'prod_punk';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed']));
}

// Get venue ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid venue ID']));
}

// Fetch venue details
$sql = "SELECT * FROM venues WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die(json_encode(['error' => 'Venue not found']));
}

$venue = $result->fetch_assoc();

echo json_encode($venue);
$conn->close();
?>