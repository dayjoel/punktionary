<?php
// get_bands.php - Fetch bands with filtering and pagination
header('Content-Type: application/json');

// TODO: Move these to a config file outside web root
$host = 'sql.punktionary.com';
$user = 'dayjoel';
$pass = 'TETherball99!';
$db   = 'prod_punk';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

// Get parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 12;
$perPage = in_array($perPage, [12, 24, 48]) ? $perPage : 12; // Validate per_page

$name = isset($_GET['name']) ? trim($_GET['name']) : '';
$genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$state = isset($_GET['state']) ? trim($_GET['state']) : '';
$active = isset($_GET['active']) ? $_GET['active'] : 'any';
$featured = isset($_GET['featured']) ? filter_var($_GET['featured'], FILTER_VALIDATE_BOOLEAN) : false;

// Build base query
$sql = "SELECT id, name, genre, city, state, active, albums, links, photo_references FROM bands WHERE 1=1";
$countSql = "SELECT COUNT(*) as total FROM bands WHERE 1=1";
$params = [];
$types = '';

// Add filters
if ($name !== '') {
    $sql .= " AND name COLLATE utf8mb4_general_ci LIKE ?";
    $countSql .= " AND name COLLATE utf8mb4_general_ci LIKE ?";
    $params[] = "%$name%";
    $types .= 's';
}
if ($genre !== '') {
    $sql .= " AND genre COLLATE utf8mb4_general_ci LIKE ?";
    $countSql .= " AND genre COLLATE utf8mb4_general_ci LIKE ?";
    $params[] = "%$genre%";
    $types .= 's';
}
if ($city !== '') {
    $sql .= " AND city COLLATE utf8mb4_general_ci LIKE ?";
    $countSql .= " AND city COLLATE utf8mb4_general_ci LIKE ?";
    $params[] = "%$city%";
    $types .= 's';
}
if ($state !== '') {
    $sql .= " AND state COLLATE utf8mb4_general_ci LIKE ?";
    $countSql .= " AND state COLLATE utf8mb4_general_ci LIKE ?";
    $params[] = "%$state%";
    $types .= 's';
}
if ($active === 'yes') {
    $sql .= " AND active = 1";
    $countSql .= " AND active = 1";
} elseif ($active === 'no') {
    $sql .= " AND active = 0";
    $countSql .= " AND active = 0";
}

// TODO: Add featured logic when you have a featured field in DB
// For now, just order by created_at for "featured"
if ($featured) {
    $sql .= " ORDER BY created_at DESC";
} else {
    $sql .= " ORDER BY name ASC";
}

// Get total count
$countStmt = $conn->prepare($countSql);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $perPage);

// Add pagination
$offset = ($page - 1) * $perPage;
$sql .= " LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

// Execute main query
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

// Return paginated response
echo json_encode([
    'bands' => $bands,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $perPage,
        'total_records' => $totalRecords,
        'total_pages' => $totalPages
    ]
]);

$conn->close();
?>