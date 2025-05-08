<?php
require 'vendor/autoload.php';
include 'config.php';
session_start();

$session = new SpotifyWebAPI\Session(
    $CLIENT_ID,
    $CLIENT_SECRET,
    $CALLBACK_URL
);

// Prüfe den State-Parameter
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['spotify_auth_state']) {
    die('State mismatch error! Möglicher CSRF-Angriff.');
}

// Code gegen Token austauschen
if (isset($_GET['code'])) {
    $session->requestAccessToken($_GET['code']);
    
    // Token in der Session speichern
    $_SESSION['spotify_access_token'] = $session->getAccessToken();
    $_SESSION['spotify_refresh_token'] = $session->getRefreshToken();
    
    // Zur Profilseite weiterleiten
    header('Location: profile.php');
    die();
} else {
    die('Kein Code erhalten von Spotify!');
}
