<?php
require 'vendor/autoload.php';
include "config.php";
session_start();

$session = new SpotifyWebAPI\Session(
    $CLIENT_ID,
    $CLIENT_SECRET,
    $CALLBACK_URL
);

if (isset($_GET['code'])) {
    // Exchange the authorization code for an access token
    $session->requestAccessToken($_GET['code']);
    
    // Create an API object and set the access token
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($session->getAccessToken());
    
    // Get the user's profile data
    try {
        $userData = $api->me();
        
        // Store user data in session
        $_SESSION['userData'] = [
            'id' => $userData->id,
            'display_name' => $userData->display_name,
            'email' => $userData->email,
            'images' => $userData->images,
            'subscription' => $userData->product,
        ];
        
        // Store access token and refresh token in session
        $_SESSION['spotify_access_token'] = $session->getAccessToken();
        $_SESSION['spotify_refresh_token'] = $session->getRefreshToken();
        $_SESSION['spotify_token_expires'] = time() + $session->getTokenExpiration();
        
        // For debugging
        error_log('User logged in: ' . $_SESSION['userData']['display_name']);
        error_log('Session data: ' . print_r($_SESSION, true));
        
        // Redirect to dashboard or home page
        header('Location: dashboard.php');
        exit;
    } catch (Exception $e) {
        // Handle error
        error_log('Spotify API Error: ' . $e->getMessage());
        echo "Error fetching user data: " . $e->getMessage();
    }
} else {
    // If no code is present, initiate the authorization flow
    $state = bin2hex(random_bytes(16));
    $_SESSION['spotify_auth_state'] = $state;

    $options = [
        'scope' => [
            'user-read-email',
            'streaming',
            'user-modify-playback-state',
            'user-read-private',
            'user-top-read',
            'playlist-read-private',
            'playlist-read-collaborative',
            'user-read-recently-played'
        ],
        'state' => $state,
    ];

    header('Location: ' . $session->getAuthorizeUrl($options));
    exit;
}
