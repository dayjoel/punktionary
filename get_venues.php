<?php
// get_venues.php - Fetch venues with filtering and pagination
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$conn = get_db_connection();
if (!$conn) {
    die(json_encode(['error' => 'Database connection failed']));
}

// Get parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 12;
$perPage = in_array($perPage, [12, 24, 48]) ? $perPage : 12;

$name = isset($_GET['name']) ? trim($_GET['name']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$state = isset($_GET['state']) ? trim($_GET['state']) : '';
$age = isset($_GET['age']) ? trim($_GET['age']) : '';
$capacityMin = isset($_GET['capacity_min']) ? intval($_GET['capacity_min']) : 0;
$capacityMax = isset($_GET['capacity_max']) ? intval($_GET['capacity_max']) : 0;
$featured = isset($_GET['featured']) ? filter_var($_GET['featured'], FILTER_VALIDATE_BOOLEAN) : false;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

// Build base query with rating data
$sql = "SELECT v.id, v.name, v.type, v.city, v.state, v.capacity, v.age_restriction, v.links, v.street_address,
        COUNT(vr.id) as review_count,
        COALESCE(AVG(vr.rating), 0) as average_rating
        FROM venues v
        LEFT JOIN venue_reviews vr ON v.id = vr.venue_id
        WHERE 1=1";
$countSql = "SELECT COUNT(DISTINCT v.id) as total FROM venues v WHERE 1=1";
$params = [];
$types = '';

// Add filters
if ($name !== '') {
    $sql .= " AND v.name COLLATE utf8mb4_general_ci LIKE ?";
    $countSql .= " AND v.name COLLATE utf8mb4_general_ci LIKE ?";
    $params[] = "%$name%";
    $types .= 's';
}
if ($type !== '') {
    $sql .= " AND v.type COLLATE utf8mb4_general_ci LIKE ?";
    $countSql .= " AND v.type COLLATE utf8mb4_general_ci LIKE ?";
    $params[] = "%$type%";
    $types .= 's';
}
if ($city !== '') {
    $sql .= " AND v.city COLLATE utf8mb4_general_ci LIKE ?";
    $countSql .= " AND v.city COLLATE utf8mb4_general_ci LIKE ?";
    $params[] = "%$city%";
    $types .= 's';
}
if ($state !== '') {
    $sql .= " AND v.state COLLATE utf8mb4_general_ci LIKE ?";
    $countSql .= " AND v.state COLLATE utf8mb4_general_ci LIKE ?";
    $params[] = "%$state%";
    $types .= 's';
}
if ($age !== '') {
    $sql .= " AND v.age_restriction = ?";
    $countSql .= " AND v.age_restriction = ?";
    $params[] = $age;
    $types .= 's';
}
if ($capacityMin > 0) {
    $sql .= " AND v.capacity >= ?";
    $countSql .= " AND v.capacity >= ?";
    $params[] = $capacityMin;
    $types .= 'i';
}
if ($capacityMax > 0) {
    $sql .= " AND v.capacity <= ?";
    $countSql .= " AND v.capacity <= ?";
    $params[] = $capacityMax;
    $types .= 'i';
}

// Group by venue to aggregate reviews
$sql .= " GROUP BY v.id";

// Order
// Apply sorting
switch ($sort) {
    case 'name_desc':
        $sql .= " ORDER BY v.name DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY v.created_at DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY v.created_at ASC";
        break;
    case 'name_asc':
    default:
        $sql .= " ORDER BY v.name ASC";
        break;
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

$venues = [];
while ($row = $result->fetch_assoc()) {
    $venues[] = $row;
}

// Return paginated response
echo json_encode([
    'venues' => $venues,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $perPage,
        'total_records' => $totalRecords,
        'total_pages' => $totalPages
    ]
]);

$conn->close();
?>