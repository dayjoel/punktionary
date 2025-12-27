<?php
require_once 'vendor/autoload.php'; // Use google/apiclient

$jwt = $_POST['credential'] ?? '';
if (!$jwt) { exit('No credential'); }

$client = new Google_Client(['client_id' => '468094396453-vbt8dbmg2a8qrp0ahmv48qfj6srcp2dq.apps.googleusercontent.com']);
$payload = $client->verifyIdToken($jwt);

if ($payload) {
    $userid = $payload['sub'];
    $email = $payload['email'];
    // Lookup user in your DB, create session, etc.
    echo json_encode(['success' => true, 'email' => $email]);
} else {
    echo json_encode(['success' => false]);
}
?>
