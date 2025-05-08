<?php
// Display all PHP errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();
require_once 'config.php';

// Verify state to prevent CSRF attacks
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['spotify_auth_state']) {
    echo "<p>State mismatch error. <a href='login.php'>Try again</a>.</p>";
    exit;
}

// Check if there's an error or missing code
if (isset($_GET['error'])) {
    echo "<p>Error: " . htmlspecialchars($_GET['error']) . "</p>";
    echo "<p><a href='login.php'>Try again</a></p>";
    exit;
}

if (!isset($_GET['code'])) {
    echo "<p>No authorization code provided. <a href='login.php'>Try again</a>.</p>";
    exit;
}

// Exchange authorization code for access token
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'],
    'redirect_uri' => $CALLBACK_URL
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . base64_encode($CLIENT_ID . ':' . $CLIENT_SECRET)
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// If successful, store tokens in session
if ($http_code == 200) {
    $tokens = json_decode($response, true);
    
    if (isset($tokens['access_token'])) {
        $_SESSION['access_token'] = $tokens['access_token'];
        $_SESSION['token_expiry'] = time() + $tokens['expires_in'];
        
        if (isset($tokens['refresh_token'])) {
            $_SESSION['refresh_token'] = $tokens['refresh_token'];
        }
        
        // TEST: Verify the access token by making a test request to the API
        $ch = curl_init('https://api.spotify.com/v1/me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $_SESSION['access_token']
        ]);
        $test_response = curl_exec($ch);
        $test_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($test_status == 200) {
            // Success! Redirect to recommendations page
            header('Location: recommendstions2.php');
            exit;
        } else {
            echo "<p>API test failed. Status: $test_status</p>";
            echo "<p>Response: <pre>" . htmlspecialchars($test_response) . "</pre></p>";
            echo "<p><a href='login.php'>Try again</a></p>";
            exit;
        }
    } else {
        echo "<p>No access token in response.</p>";
        echo "<p>Response: <pre>" . htmlspecialchars($response) . "</pre></p>";
        echo "<p><a href='login.php'>Try again</a></p>";
        exit;
    }
} else {
    echo "<p>Token request failed. Status: $http_code</p>";
    echo "<p>Response: <pre>" . htmlspecialchars($response) . "</pre></p>";
    echo "<p><a href='login.php'>Try again</a></p>";
    exit;
}
?>
