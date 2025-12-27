<?php
error_log(print_r($_POST, true));

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$debug = true; // Set to true to include SQL in the JSON for debugging

$host = 'sql.punktionary.com';
$user = 'dayjoel';
$pass = 'TETherball99!';
$db   = 'prod_punk';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode([]));
}

// Collect and trim POST data
$name   = isset($_POST['name']) ? trim($_POST['name']) : '';
$genre  = isset($_POST['genre']) ? trim($_POST['genre']) : '';
$city   = isset($_POST['city']) ? trim($_POST['city']) : '';
$state  = isset($_POST['state']) ? trim($_POST['state']) : '';
$active = isset($_POST['active']) ? $_POST['active'] : 'any';

// Build query with case-insensitive search (COLLATE utf8mb4_general_ci ensures case-insensitive)
$sql = "SELECT name, genre, city, state, active FROM bands WHERE 1=1";
$params = [];
$types = '';

if ($name !== '') {
    $sql .= " AND name COLLATE utf8mb4_general_ci LIKE ?";
    $params[] = "%$name%";
    $types .= 's';
}
if ($genre !== '') {
    $sql .= " AND genre COLLATE utf8mb4_general_ci LIKE ?";
    $params[] = "%$genre%";
    $types .= 's';
}
if ($city !== '') {
    $sql .= " AND city COLLATE utf8mb4_general_ci LIKE ?";
    $params[] = "%$city%";
    $types .= 's';
}
if ($state !== '') {
    $sql .= " AND state COLLATE utf8mb4_general_ci LIKE ?";
    $params[] = "%$state%";
    $types .= 's';
}
if ($active === 'yes') {
    $sql .= " AND active = 1";
} elseif ($active === 'no') {
    $sql .= " AND active = 0";
}

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$bands = [];
while ($row = $result->fetch_assoc()) {
    $bands[] = $row;
}

echo json_encode(array_values($bands));
$conn->close();
?>
