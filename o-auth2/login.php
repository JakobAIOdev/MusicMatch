<?php
require 'vendor/autoload.php';
include "config.php";
session_start();

$session = new SpotifyWebAPI\Session(
    $CLIENT_ID,
    $CLIENT_SECRET,
    $CALLBACK_URL
);

// State-Parameter generieren (gegen CSRF)
$state = bin2hex(random_bytes(16));
$_SESSION['spotify_auth_state'] = $state;

$options = [
    'scope' => [
        'user-read-email',
        'user-read-private',
        'user-top-read',
        'playlist-read-private',
        'playlist-read-collaborative',
        'user-read-recently-played'
    ],
    'state' => $state,
];

header('Location: ' . $session->getAuthorizeUrl($options));
die();
