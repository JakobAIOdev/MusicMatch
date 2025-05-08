<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

/**
 * Check if the Spotify token is expired and refresh if needed
 * @return SpotifyWebAPI\SpotifyWebAPI API instance with valid token
 */
function getSpotifyApi() {
    $api = new SpotifyWebAPI\SpotifyWebAPI(['auto_retry' => true]);
    
    if (!isset($_SESSION['spotify_access_token']) || !isset($_SESSION['spotify_refresh_token'])) {
        return false;
    }
    
    if (!isset($_SESSION['spotify_token_expires']) || $_SESSION['spotify_token_expires'] <= time() + 600) { // 10 min buffer
        try {
            error_log('Refreshing Spotify token');
            
            $session = new SpotifyWebAPI\Session(
                $GLOBALS['CLIENT_ID'],
                $GLOBALS['CLIENT_SECRET'],
                $GLOBALS['CALLBACK_URL']
            );
            
            $session->setRefreshToken($_SESSION['spotify_refresh_token']);
            $session->refreshAccessToken($_SESSION['spotify_refresh_token']);

            $_SESSION['spotify_access_token'] = $session->getAccessToken();
            $_SESSION['spotify_token_expires'] = time() + $session->getTokenExpiration();
            
            error_log('Token refreshed successfully. Expires: ' . date('Y-m-d H:i:s', $_SESSION['spotify_token_expires']));
        } catch (Exception $e) {
            error_log('Failed to refresh token: ' . $e->getMessage());
            unset($_SESSION['spotify_access_token']);
            unset($_SESSION['spotify_refresh_token']);
            return false;
        }
    }
    
    $api->setAccessToken($_SESSION['spotify_access_token']);
    return $api;
}

/**
 * Validates the current Spotify token and redirects to login if invalid
 * @param bool $redirect Whether to redirect to login page if token is invalid
 * @return bool True if token is valid, false otherwise
 */
function validateSpotifyToken($redirect = true) {
    $api = getSpotifyApi();
    
    if (!$api) {
        if ($redirect) {
            $currentPath = $_SERVER['REQUEST_URI'];
            header("Location: /auth/login.php?redirect=" . urlencode($currentPath));
            exit;
        }
        return false;
    }
    
    try {
        $api->getMyDevices();
        return true;
    } catch (Exception $e) {
        error_log('Token validation failed: ' . $e->getMessage());
        
        if ($redirect) {
            $currentPath = $_SERVER['REQUEST_URI'];
            header("Location: /auth/login.php?redirect=" . urlencode($currentPath));
            exit;
        }
        return false;
    }
}