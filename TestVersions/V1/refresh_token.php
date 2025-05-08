<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Token Refresh</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .section { margin-bottom: 20px; padding: 15px; border-radius: 5px; }
        .info { background-color: #e3f2fd; }
        .success { background-color: #e8f5e9; }
        .error { background-color: #ffebee; }
    </style>
</head>
<body>
    <h1>Spotify Token Refresh</h1>";

if (!isset($_SESSION['refresh_token'])) {
    echo "<div class='section error'>
        <h2>Error</h2>
        <p>No refresh token available in session. Please login again.</p>
        <p><a href='login.php'>Go to Login</a></p>
    </div>";
    exit();
}

$refresh_token = $_SESSION['refresh_token'];

echo "<div class='section info'>
    <h2>Attempting to refresh token...</h2>
</div>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'refresh_token',
    'refresh_token' => $refresh_token
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . base64_encode($CLIENT_ID . ':' . $CLIENT_SECRET)
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$response_data = json_decode($response, true);

if ($http_code == 200 && isset($response_data['access_token'])) {
    $_SESSION['access_token'] = $response_data['access_token'];
    $_SESSION['token_expiry'] = time() + $response_data['expires_in'];
    
    echo "<div class='section success'>
        <h2>Success!</h2>
        <p>Token refreshed successfully.</p>
        <p>New token expires in " . $response_data['expires_in'] . " seconds.</p>
        <p><a href='recommendstions2.php'>Go to Recommendations</a></p>
        <p><a href='check_auth.php'>Check Authentication</a></p>
    </div>";
} else {
    echo "<div class='section error'>
        <h2>Error</h2>
        <p>Failed to refresh token. HTTP status: $http_code</p>
        <p>Response: <pre>" . json_encode($response_data, JSON_PRETTY_PRINT) . "</pre></p>
        <p><a href='login.php'>Please login again</a></p>
    </div>";
}

echo "</body></html>";
?>
