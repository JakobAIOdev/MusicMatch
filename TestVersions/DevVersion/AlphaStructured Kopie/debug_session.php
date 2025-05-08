<?php
require_once './includes/session_handler.php';

echo '<h1>Session Debug</h1>';
echo '<pre>';

// Print all session variables related to seen tracks
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'seen_track_ids_') === 0) {
        echo "<h3>$key</h3>";
        print_r($value);
        echo '<hr>';
    }
}

// Print liked tracks
if (isset($_SESSION['liked_tracks'])) {
    echo "<h3>liked_tracks</h3>";
    print_r($_SESSION['liked_tracks']);
}

echo '</pre>';
echo '<a href="index.php">Back to home</a>';