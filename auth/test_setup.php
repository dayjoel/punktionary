<?php
// OAuth Setup Test Script
// This script checks if your OAuth system is configured correctly
// DELETE THIS FILE after testing for security

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== OAuth Setup Test ===\n\n";

// Test 1: Check if db_config.php is accessible
echo "1. Testing database config...\n";
$db_config_path = __DIR__ . '/../db_config.php';
if (file_exists($db_config_path)) {
    echo "   ✓ db_config.php found\n";
    require_once $db_config_path;

    $conn = get_db_connection();
    if ($conn) {
        echo "   ✓ Database connection successful\n";
        $conn->close();
    } else {
        echo "   ✗ Database connection failed\n";
    }
} else {
    echo "   ✗ db_config.php not found at: $db_config_path\n";
}

echo "\n2. Testing OAuth config...\n";
$oauth_config_path = __DIR__ . '/oauth_config.php';
if (file_exists($oauth_config_path)) {
    echo "   ✓ oauth_config.php found\n";
    require_once $oauth_config_path;

    // Check Google config
    if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== 'YOUR_GOOGLE_CLIENT_ID') {
        echo "   ✓ Google credentials configured\n";
    } else {
        echo "   ✗ Google credentials not configured (still has placeholder)\n";
    }

    // Check Facebook config
    if (defined('FACEBOOK_APP_ID') && FACEBOOK_APP_ID !== 'YOUR_FACEBOOK_APP_ID') {
        echo "   ✓ Facebook credentials configured\n";
    } else {
        echo "   ✗ Facebook credentials not configured (still has placeholder)\n";
    }

    // Check Apple config
    if (defined('APPLE_CLIENT_ID') && APPLE_CLIENT_ID !== 'YOUR_APPLE_SERVICE_ID') {
        echo "   ✓ Apple credentials configured\n";
    } else {
        echo "   ✗ Apple credentials not configured (still has placeholder)\n";
    }
} else {
    echo "   ✗ oauth_config.php not found\n";
}

echo "\n3. Testing database tables...\n";
if (isset($conn)) {
    $conn = get_db_connection();

    // Check users table
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "   ✓ 'users' table exists\n";
    } else {
        echo "   ✗ 'users' table not found - run database migration!\n";
    }

    // Check oauth_states table
    $result = $conn->query("SHOW TABLES LIKE 'oauth_states'");
    if ($result->num_rows > 0) {
        echo "   ✓ 'oauth_states' table exists\n";
    } else {
        echo "   ✗ 'oauth_states' table not found - run database migration!\n";
    }

    // Check if bands table has submitted_by column
    $result = $conn->query("SHOW COLUMNS FROM bands LIKE 'submitted_by'");
    if ($result->num_rows > 0) {
        echo "   ✓ 'bands' table has 'submitted_by' column\n";
    } else {
        echo "   ✗ 'bands' table missing 'submitted_by' column - run database migration!\n";
    }

    $conn->close();
}

echo "\n4. Testing helper functions...\n";
$helpers_path = __DIR__ . '/helpers.php';
if (file_exists($helpers_path)) {
    echo "   ✓ helpers.php found\n";
    require_once $helpers_path;

    if (function_exists('is_authenticated')) {
        echo "   ✓ is_authenticated() function exists\n";
    } else {
        echo "   ✗ is_authenticated() function not found\n";
    }

    if (function_exists('generate_state_token')) {
        echo "   ✓ generate_state_token() function exists\n";
    } else {
        echo "   ✗ generate_state_token() function not found\n";
    }
} else {
    echo "   ✗ helpers.php not found\n";
}

echo "\n5. Testing session configuration...\n";
$session_config_path = __DIR__ . '/session_config.php';
if (file_exists($session_config_path)) {
    echo "   ✓ session_config.php found\n";
    require_once $session_config_path;

    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "   ✓ Session started successfully\n";
        echo "   Session name: " . session_name() . "\n";
    } else {
        echo "   ✗ Session not active\n";
    }
} else {
    echo "   ✗ session_config.php not found\n";
}

echo "\n6. Testing PHP extensions...\n";
if (extension_loaded('curl')) {
    echo "   ✓ cURL extension loaded\n";
} else {
    echo "   ✗ cURL extension not loaded - required for OAuth!\n";
}

if (extension_loaded('openssl')) {
    echo "   ✓ OpenSSL extension loaded\n";
} else {
    echo "   ✗ OpenSSL extension not loaded - required for Apple OAuth!\n";
}

echo "\n7. Testing file permissions...\n";
echo "   oauth_config.php readable: " . (is_readable($oauth_config_path) ? 'YES' : 'NO') . "\n";
echo "   helpers.php readable: " . (is_readable($helpers_path) ? 'YES' : 'NO') . "\n";

echo "\n=== Test Complete ===\n";
echo "\nIf you see any ✗ marks above, fix those issues first.\n";
echo "After fixing issues, DELETE THIS FILE for security.\n";
?>
