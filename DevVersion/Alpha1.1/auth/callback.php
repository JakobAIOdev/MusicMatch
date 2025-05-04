<?php
require_once '../includes/session_handler.php';
require_once '../vendor/autoload.php';
require_once '../includes/config.php';

$session = new SpotifyWebAPI\Session(
    $CLIENT_ID,
    $CLIENT_SECRET,
    $CALLBACK_URL
);

if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['spotify_auth_state']) {
    error_log('State mismatch in Spotify callback');
    
    header('Location: login.php?error=state_mismatch');
    exit;
}

if (isset($_GET['code'])) {
    $session->requestAccessToken($_GET['code']);
    
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($session->getAccessToken());
    
    try {
        $userData = $api->me();
        
        $_SESSION['userData'] = [
            'id' => $userData->id,
            'display_name' => $userData->display_name,
            'email' => $userData->email,
            'images' => $userData->images,
            'subscription' => $userData->product,
        ];
        
        $_SESSION['spotify_access_token'] = $session->getAccessToken();
        $_SESSION['spotify_refresh_token'] = $session->getRefreshToken();
        $_SESSION['spotify_token_expires'] = time() + $session->getTokenExpiration();
        
        //error_log('User logged in: ' . $_SESSION['userData']['display_name']);
        $_SESSION['spotify_access_token'] = $session->getAccessToken();
        $_SESSION['spotify_refresh_token'] = $session->getRefreshToken();
        $_SESSION['spotify_token_expires'] = time() + $session->getTokenExpiration();
        
        $redirectTo = '../index.php';
        if (isset($_SESSION['login_redirect'])) {
            $redirectTo = $_SESSION['login_redirect'];
            unset($_SESSION['login_redirect']);
        }
        
        header('Location: ' . $redirectTo);
        exit;
    } catch (Exception $e) {
        error_log('Spotify API Error in callback: ' . $e->getMessage());

        header('Location: login.php?error=api_error&message=' . urlencode($e->getMessage()));
        exit;
    }
} else {
    error_log('No code received from Spotify');
    header('Location: login.php?error=no_code');
    exit;
}