<?php
require_once __DIR__ . '/session_handler.php';
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';


/**
 * @return SpotifyWebAPI\SpotifyWebAPI
 */
function getSpotifyAPI() {
    $api = new SpotifyWebAPI\SpotifyWebAPI();

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
            
            $_SESSION['spotify_access_token'] = $session->getAccessToken();
            $_SESSION['spotify_token_expires'] = time() + $session->getTokenExpiration();
            
            error_log('Token refreshed successfully. New expiry: ' . date('Y-m-d H:i:s', $_SESSION['spotify_token_expires']));
        }
        
        $api->setAccessToken($_SESSION['spotify_access_token']);
    } else {
        error_log('No valid Spotify token found in session');
        return false;
    }
    
    return $api;
}

/**
 * @return bool
 */
function hasPremium() {
    if (!isset($_SESSION['userData']['subscription'])) {
        return false;
    }
    
    return $_SESSION['userData']['subscription'] === 'premium';
}

