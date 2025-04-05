<?php
require 'vendor/autoload.php';
include 'config.php';
session_start();

$session = new SpotifyWebAPI\Session(
    $CLIENT_ID,
    $CLIENT_SECRET,
    $CALLBACK_URL
);

if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['spotify_auth_state']) {
    die('State mismatch error! Möglicher CSRF-Angriff.');
}

if (isset($_GET['code'])) {
    $session->requestAccessToken($_GET['code']);
    
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($session->getAccessToken());
    
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
        
        // Store token information
        $_SESSION['spotify_access_token'] = $session->getAccessToken();
        $_SESSION['spotify_refresh_token'] = $session->getRefreshToken();
        $_SESSION['spotify_token_expires'] = time() + $session->getTokenExpiration();
        
        // For debugging
        error_log('User logged in via callback: ' . $_SESSION['userData']['display_name']);
        
        header('Location: index.php');
        die();
    } catch (Exception $e) {
        error_log('Spotify API Error in callback: ' . $e->getMessage());
        die('Error fetching user data: ' . $e->getMessage());
    }
} else {
    die('No code received from Spotify!');
}