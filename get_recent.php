<?php
// get_recent.php - Fetch recently added bands and venues for homepage
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');

try {
    $host = 'sql.punktionary.com';
    $user = 'dayjoel';
    $pass = 'TETherball99!';
    $db   = 'prod_punk';

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Get 4 most recent bands
    $bandsSql = "SELECT id, name FROM bands ORDER BY created_at DESC LIMIT 4";
    $bandsResult = $conn->query($bandsSql);
    $bands = [];
    while ($row = $bandsResult->fetch_assoc()) {
        $bands[] = $row;
    }

    // Get 3 most recent venues
    $venuesSql = "SELECT id, name FROM venues ORDER BY created_at DESC LIMIT 3";
    $venuesResult = $conn->query($venuesSql);
    $venues = [];
    while ($row = $venuesResult->fetch_assoc()) {
        $venues[] = $row;
    }

    echo json_encode([
        'success' => true,
        'bands' => $bands,
        'venues' => $venues
    ]);

    $conn->close();
    
} catch (Exception $e) {
    error_log('Get recent error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load recent entries'
    ]);
}
?>