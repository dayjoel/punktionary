<?php
// Temporary diagnostic script - DELETE after testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "Current directory: " . __DIR__ . "\n";
echo "Looking for config at: " . __DIR__ . '/../db_config.php' . "\n";
echo "Config file exists: " . (file_exists(__DIR__ . '/../db_config.php') ? 'YES' : 'NO') . "\n";
echo "Config file readable: " . (is_readable(__DIR__ . '/../db_config.php') ? 'YES' : 'NO') . "\n\n";

if (file_exists(__DIR__ . '/../db_config.php')) {
    require_once __DIR__ . '/../db_config.php';
    echo "Config loaded successfully\n";
    echo "DB_HOST: " . DB_HOST . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";

    $conn = get_db_connection();
    if ($conn) {
        echo "\nDatabase connection: SUCCESS\n";
        $conn->close();
    } else {
        echo "\nDatabase connection: FAILED\n";
    }
} else {
    echo "\nERROR: Config file not found!\n";
    echo "Files in parent directory:\n";
    print_r(scandir(__DIR__ . '/../..'));
}
?>
