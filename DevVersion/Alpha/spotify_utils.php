<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

/**
 * Check if the Spotify token is expired and refresh if needed
 * @return SpotifyWebAPI\SpotifyWebAPI API instance with valid token
 */
function getSpotifyAPI() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    
    // Check if token exists and is about to expire (within 60 seconds)
    if (isset($_SESSION['spotify_access_token']) && 
        isset($_SESSION['spotify_token_expires']) && 
        isset($_SESSION['spotify_refresh_token'])) {
        
        if ($_SESSION['spotify_token_expires'] - 60 < time()) {
            error_log('Refreshing expired Spotify token');
            
            $session = new SpotifyWebAPI\Session(
                $GLOBALS['CLIENT_ID'],
                $GLOBALS['CLIENT_SECRET'],
                $GLOBALS['CALLBACK_URL']
            );
            
            $session->setRefreshToken($_SESSION['spotify_refresh_token']);
            
            $session->refreshAccessToken($_SESSION['spotify_refresh_token']);
            
            // Update session with new tokens
            $_SESSION['spotify_access_token'] = $session->getAccessToken();
            $_SESSION['spotify_token_expires'] = time() + $session->getTokenExpiration();
            
            // For debugging
            error_log('Token refreshed successfully. New expiry: ' . date('Y-m-d H:i:s', $_SESSION['spotify_token_expires']));
        }
        
        // Set the access token on the API
        $api->setAccessToken($_SESSION['spotify_access_token']);
    } else {
        // No valid token exists
        error_log('No valid Spotify token found in session');
        return false;
    }
    
    return $api;
}