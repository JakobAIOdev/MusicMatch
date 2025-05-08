<?php
require_once 'session_handler.php';
header('Content-Type: application/json');

try {
    if (isset($_SESSION['liked_tracks'])) {
        unset($_SESSION['liked_tracks']);
    }

    $_SESSION['global_seen_track_ids'] = [];
    
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'seen_tracks_') === 0 || 
            strpos($key, 'seen_track_ids_') === 0) {
            $_SESSION[$key] = [];
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