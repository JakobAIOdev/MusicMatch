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
    die('State mismatch error! Possible CSRF-Attack.');
}

if (isset($_GET['code'])) {
    $session->requestAccessToken($_GET['code']);
    
    // safe Token in Session
    $_SESSION['spotify_access_token'] = $session->getAccessToken();
    $_SESSION['spotify_refresh_token'] = $session->getRefreshToken();
    
    header('Location: profile.php');
    die();
} else {
    die('Spotify not responding');
}
