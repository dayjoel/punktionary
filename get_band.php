<?php
// get_band.php - Fetch single band details by ID
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

// Get band ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid band ID']));
}

// Fetch band details
$sql = "SELECT * FROM bands WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die(json_encode(['error' => 'Band not found']));
}

$band = $result->fetch_assoc();

echo json_encode($band);
$conn->close();
?>