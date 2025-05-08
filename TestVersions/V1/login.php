<?php
// Start session
session_start();
require_once 'config.php';

// Generate a random state value for security
$state = bin2hex(random_bytes(16));
$_SESSION['spotify_auth_state'] = $state;

// Define Spotify API scopes needed
$scope = 'user-read-private user-read-email user-top-read user-library-read user-library-modify';

// Redirect to Spotify's authorization page
$params = [
    'response_type' => 'code',
    'client_id' => $CLIENT_ID,
    'scope' => $scope,
    'redirect_uri' => $CALLBACK_URL,
    'state' => $state,
    'show_dialog' => true // Force login dialog for testing
];

header('Location: https://accounts.spotify.com/authorize?' . http_build_query($params));
exit;
?>