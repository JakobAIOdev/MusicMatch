<?php
require_once './includes/session_handler.php';
require_once './vendor/autoload.php';
require_once './includes/spotify_utils.php';

ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    $api = getSpotifyApi();
    $me = $api->me();
    $userId = $me->id;
    
    $playlists = $api->getMyPlaylists(['limit' => 50]);
    
    $playlistData = [];
    foreach ($playlists->items as $playlist) {
        if ($playlist->owner->id === $userId || $playlist->collaborative) {
            $playlistData[] = [
                'id' => $playlist->id,
                'name' => $playlist->name,
                'tracks' => $playlist->tracks->total,
                'image' => !empty($playlist->images) ? $playlist->images[0]->url : null
            ];
        }
    }
    
    echo json_encode([
        'success' => true, 
        'playlists' => $playlistData
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}