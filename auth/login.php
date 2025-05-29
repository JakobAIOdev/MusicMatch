<?php
require_once '../includes/session_handler.php';
require_once '../vendor/autoload.php';
require_once '../includes/config.php';

if (isset($_GET['error'])) {
    header('Location: callback.php?error=' . $_GET['error'] . (isset($_GET['message']) ? '&message=' . $_GET['message'] : ''));
    exit;
}

if (isset($_GET['redirect'])) {
    $_SESSION['login_redirect'] = $_GET['redirect'];
} else if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
    $referer = parse_url($_SERVER['HTTP_REFERER']);
    $path = $referer['path'];
    if ($path !== '/auth/login.php' && $path !== '/auth/callback.php') {
        $_SESSION['login_redirect'] = $path . (isset($referer['query']) ? '?' . $referer['query'] : '');
    }
}

if (isLoggedIn()) {
    header('Location: ' . $BASE_URL . '/index.php');
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
        'user-read-recently-played',
        'ugc-image-upload'
    ],
    'state' => $state,
    'show_dialog' => true
];

$session = new SpotifyWebAPI\Session(
    $CLIENT_ID,
    $CLIENT_SECRET,
    $CALLBACK_URL
);

// redirect to Spotify for authorization
header('Location: ' . $session->getAuthorizeUrl($options));
exit;