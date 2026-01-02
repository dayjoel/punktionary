<?php
// Test script to check and create venue_reviews table
require_once __DIR__ . '/db_config.php';

$conn = get_db_connection();

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'venue_reviews'");
if ($result->num_rows === 0) {
    echo "Table 'venue_reviews' does not exist. Creating it...\n";

    // Read and execute migration
    $migration = file_get_contents(__DIR__ . '/db/migrations/CREATE_VENUE_REVIEWS_TABLE.sql');

    if ($conn->multi_query($migration)) {
        do {
            // Store first result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());

        echo "Table 'venue_reviews' created successfully!\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
} else {
    echo "Table 'venue_reviews' already exists.\n";
}

// Show table structure
echo "\nTable structure:\n";
$result = $conn->query("DESCRIBE venue_reviews");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
}

$conn->close();
?>
