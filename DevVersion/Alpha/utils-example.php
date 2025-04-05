<?php
// Example usage in any file that needs to use the Spotify API
require_once 'spotify_utils.php';

// Get API instance with fresh token
$api = getSpotifyAPI();

if ($api) {
    // Make API calls
    try {
        $userPlaylists = $api->getUserPlaylists($_SESSION['userData']['id']);
        // Process data...
    } catch (Exception $e) {
        error_log('Spotify API Error: ' . $e->getMessage());
        // Handle error...
    }
} else {
    // No valid token, redirect to login
    header('Location: login.php');
    exit;
}