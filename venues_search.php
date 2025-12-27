<?php

error_log(print_r($_POST, true));

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Return JSON
header('Content-Type: application/json');

$debug = true; // enable SQL debugging

// Database credentials
$host = 'sql.punktionary.com';
$user = 'dayjoel';
$pass = 'TETherball99!';
$db   = 'prod_punk';

// Connect to MySQL
$conn = mysqli_connect($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode([]));
}

$name     = trim($_POST['name'] ?? '');
$capacity_min = isset($_POST['capacity_min']) ? trim($_POST['capacity_min']) : '';
$capacity_max = isset($_POST['capacity_max']) ? trim($_POST['capacity_max']) : '';$type     = trim($_POST['type'] ?? '');
$city     = trim($_POST['city'] ?? '');
$state    = trim($_POST['state'] ?? '');
$age      = trim($_POST['age'] ?? '');

$sql = "SELECT name, type, capacity, city, state, age_restriction FROM venues WHERE 1=1";
$params = [];
$types = '';

if ($name !== '') {
    $sql .= " AND name COLLATE utf8mb4_general_ci LIKE ?";
    $params[] = "%$name%";
    $types .= 's';
}
if ($capacity_min !== '') {
    $sql .= " AND capacity >= ?";
    $params[] = $capacity_min;
    $types .= 'i';
}
if ($capacity_max !== '') {
    $sql .= " AND capacity <= ?";
    $params[] = $capacity_max;
    $types .= 'i';
}
if ($type !== '') {
    $sql .= " AND type COLLATE utf8mb4_general_ci LIKE ?";
    $params[] = "%$type%";
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
if ($age !== '') {
    $sql .= " AND age_restriction = ?";
    $params[] = $age;
    $types .= 's';
}

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$venues = [];
while ($row = $result->fetch_assoc()) {
    $venues[] = $row;
}

echo json_encode(array_values($venues));
$conn->close();
?>
