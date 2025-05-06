<?php
require_once 'session_handler.php';
header('Content-Type: application/json');

try {
    if (isset($_SESSION['liked_tracks'])) {
        unset($_SESSION['liked_tracks']);
    }
    $trackTypeKeys = ['random', 'short_term', 'medium_term', 'long_term'];
    foreach ($trackTypeKeys as $key) {
        $sessionKey = 'seen_tracks_' . $key;
        if (isset($_SESSION[$sessionKey])) {
            unset($_SESSION[$sessionKey]);
        }
    }
    
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'track') !== false || strpos($key, 'song') !== false) {
            unset($_SESSION[$key]);
        }
    }
    session_write_close();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Tracks reset successfully',
        'time' => time()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'time' => time()
    ]);
}
?>