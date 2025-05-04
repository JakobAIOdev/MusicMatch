<?php
require_once './includes/session_handler.php';

$queryString = $_SERVER['QUERY_STRING'] ?? '';
parse_str($queryString, $params);

$swipeMethod = isset($params['swipe-method']) ? $params['swipe-method'] : '';
$playlistLink = isset($params['playlist-link']) ? $params['playlist-link'] : '';

if ($swipeMethod === 'playlist' && !empty($playlistLink)) {
    $playlistId = null;
    if (preg_match('/playlist\/([a-zA-Z0-9]+)/', $playlistLink, $matches)) {
        $playlistId = $matches[1];
    }
    if ($playlistId) {
        $_SESSION['seen_track_ids_playlist_' . $playlistId] = [];
    }
} elseif (in_array($swipeMethod, ['short_term', 'medium_term', 'long_term'])) {
    $_SESSION['seen_track_ids_favorites_' . $swipeMethod] = [];
} elseif ($swipeMethod === 'random') {
    $_SESSION['seen_track_ids_random'] = [];
} else {
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'seen_track_ids_') === 0) {
            $_SESSION[$key] = [];
        }
    }
}

if (isset($_SESSION['liked_tracks'])) {
    $_SESSION['liked_tracks'] = [];
}

header('Location: swiper.php' . (!empty($queryString) ? '?' . $queryString : ''));
exit;
?>