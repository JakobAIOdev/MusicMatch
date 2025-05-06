<?php
require_once './includes/session_handler.php';

// Clear liked tracks from session
if (isset($_SESSION['liked_tracks'])) {
    unset($_SESSION['liked_tracks']);
}

// Clear all seen track IDs from session
if (isset($_SESSION['seen_tracks_random'])) {
    unset($_SESSION['seen_tracks_random']);
}
if (isset($_SESSION['seen_tracks_short_term'])) {
    unset($_SESSION['seen_tracks_short_term']);
}
if (isset($_SESSION['seen_tracks_medium_term'])) {
    unset($_SESSION['seen_tracks_medium_term']);
}
if (isset($_SESSION['seen_tracks_long_term'])) {
    unset($_SESSION['seen_tracks_long_term']);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Resetting...</title>
    <script>
        localStorage.removeItem('musicmatch_liked_songs');
        window.location.href = 'swiper.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>';
    </script>
</head>
<body>
    <p>Resetting tracks... Please wait.</p>
</body>
</html>