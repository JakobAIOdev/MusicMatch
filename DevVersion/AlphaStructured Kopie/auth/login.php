<?php
require_once '../includes/session_handler.php';
require_once '../vendor/autoload.php';
require_once '../includes/config.php';

if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

// State-Parameter CSRF-safty
$state = bin2hex(random_bytes(16));
$_SESSION['spotify_auth_state'] = $state;

// Spotify OAuth-Scopes
$options = [
    'scope' => [
        'user-read-email',
        'streaming',
        'user-modify-playback-state',
        'user-read-private',
        'user-top-read',
        'playlist-read-private',
        'playlist-read-collaborative',
        'playlist-modify-private',
        'playlist-modify-public',
        'user-read-recently-played'
    ],
    'state' => $state,
];

$session = new SpotifyWebAPI\Session(
    $CLIENT_ID,
    $CLIENT_SECRET,
    $CALLBACK_URL
);

// redirect to Spotify for authorization
header('Location: ' . $session->getAuthorizeUrl($options));
exit;