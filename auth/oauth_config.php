<?php
// OAuth Provider Configuration
// IMPORTANT: Move this file outside web root in production (like db_config.php)
// TODO: Replace placeholder values with actual OAuth credentials

// Google OAuth Configuration
// Get credentials from: https://console.cloud.google.com/
define('GOOGLE_CLIENT_ID', '468094396453-vbt8dbmg2a8qrp0ahmv48qfj6srcp2dq.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', 'https://punktionary.com/auth/google_callback.php');

// Facebook OAuth Configuration
// Get credentials from: https://developers.facebook.com/
define('FACEBOOK_APP_ID', 'YOUR_FACEBOOK_APP_ID');
define('FACEBOOK_APP_SECRET', 'YOUR_FACEBOOK_APP_SECRET');
define('FACEBOOK_REDIRECT_URI', 'https://punktionary.com/auth/facebook_callback.php');

// Apple OAuth Configuration
// Get credentials from: https://developer.apple.com/
define('APPLE_CLIENT_ID', 'YOUR_APPLE_SERVICE_ID');
define('APPLE_TEAM_ID', 'YOUR_APPLE_TEAM_ID');
define('APPLE_KEY_ID', 'YOUR_APPLE_KEY_ID');
define('APPLE_PRIVATE_KEY_PATH', '/path/to/AuthKey_XXX.p8');  // Path to your .p8 private key file
define('APPLE_REDIRECT_URI', 'https://punktionary.com/auth/apple_callback.php');

// For local development, you can override with localhost URLs:
// define('GOOGLE_REDIRECT_URI', 'http://localhost/auth/google_callback.php');
?>
