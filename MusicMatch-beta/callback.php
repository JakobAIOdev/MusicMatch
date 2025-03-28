<?php
require 'vendor/autoload.php';
include 'config.php';
session_start();

$session = new SpotifyWebAPI\Session(
    $CLIENT_ID,
    $CLIENT_SECRET,
    $CALLBACK_URL
);

// check state-parameter
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['spotify_auth_state']) {
    die('State mismatch error! Möglicher CSRF-Angriff.');
}

if (isset($_GET['code'])) {
    $session->requestAccessToken($_GET['code']);
    
    // safe token in session
    $_SESSION['spotify_access_token'] = $session->getAccessToken();
    $_SESSION['spotify_refresh_token'] = $session->getRefreshToken();
    
    header('Location: index.php');
    die();
} else {
    die('No code recieved by Spotify!');
}
