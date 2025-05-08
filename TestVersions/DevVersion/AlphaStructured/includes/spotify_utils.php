<?php
require_once __DIR__ . '/session_handler.php';
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';


/**
 * Prüft, ob das Spotify-Token abgelaufen ist und erneuert es bei Bedarf
 * @return SpotifyWebAPI\SpotifyWebAPI API-Instanz mit gültigem Token oder false
 */
function getSpotifyAPI() {
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    
    // Prüfen, ob Token existiert und kurz vor Ablauf steht
    if (isset($_SESSION['spotify_access_token']) && 
        isset($_SESSION['spotify_token_expires']) && 
        isset($_SESSION['spotify_refresh_token'])) {
        
        // Wenn Token fast abgelaufen ist
        if ($_SESSION['spotify_token_expires'] - 60 < time()) {
            error_log('Refreshing expired Spotify token');
            
            $session = new SpotifyWebAPI\Session(
                $GLOBALS['CLIENT_ID'],
                $GLOBALS['CLIENT_SECRET'],
                $GLOBALS['CALLBACK_URL']
            );
            
            $session->setRefreshToken($_SESSION['spotify_refresh_token']);
            
            // Neues Access-Token anfordern
            $session->refreshAccessToken($_SESSION['spotify_refresh_token']);
            
            // Session mit neuen Tokens aktualisieren
            $_SESSION['spotify_access_token'] = $session->getAccessToken();
            $_SESSION['spotify_token_expires'] = time() + $session->getTokenExpiration();
            
            error_log('Token refreshed successfully. New expiry: ' . date('Y-m-d H:i:s', $_SESSION['spotify_token_expires']));
        }
        
        // Access-Token setzen
        $api->setAccessToken($_SESSION['spotify_access_token']);
    } else {
        // Kein gültiges Token vorhanden
        error_log('No valid Spotify token found in session');
        return false;
    }
    
    return $api;
}

/**
 * Prüft, ob der Benutzer Spotify Premium hat
 * @return bool true wenn Premium, false wenn nicht
 */
function hasPremium() {
    if (!isset($_SESSION['userData']['subscription'])) {
        return false;
    }
    
    return $_SESSION['userData']['subscription'] === 'premium';
}

