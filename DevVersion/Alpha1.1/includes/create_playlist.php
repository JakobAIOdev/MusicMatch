<?php
require_once './session_handler.php';
require_once '../vendor/autoload.php';
require_once './spotify_utils.php';

ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || !isset($data['tracks']) || empty($data['tracks'])) {
        throw new Exception('Missing required data: playlist name or tracks');
    }
    
    $api = getSpotifyApi();
    
    if (isset($data['playlist_id']) && !empty($data['playlist_id'])) {
        $playlistId = $data['playlist_id'];
        
        $existingTracks = $api->getPlaylistTracks($playlistId);
        $existingUris = [];
        
        foreach ($existingTracks->items as $item) {
            if (isset($item->track) && isset($item->track->uri)) {
                $existingUris[] = $item->track->uri;
            }
        }
        
        $newTracks = array_values(array_filter($data['tracks'], function($uri) use ($existingUris) {
            return !in_array($uri, $existingUris, true);
        }));
        
        $skippedTracks = count($data['tracks']) - count($newTracks);
        
        if (!empty($newTracks)) {
            $result = $api->addPlaylistTracks($playlistId, $newTracks);
            $playlist = $api->getPlaylist($playlistId);
        } else {
            $playlist = $api->getPlaylist($playlistId);
        }
    } else {
        $playlist = $api->createPlaylist([
            'name' => $data['name'],
            'description' => 'Created with MusicMatch Swiper',
            'public' => false
        ]);
        if (!$playlist || !isset($playlist->id)) {
            throw new Exception('Failed to create playlist');
        }
        
        $result = $api->addPlaylistTracks($playlist->id, $data['tracks']);
        $skippedTracks = 0;

        try {
            $imagePath = '../assets/img/MusicMatchCover.jpg';
            if (file_exists($imagePath)) {
                $imageData = base64_encode(file_get_contents($imagePath));
                $api->updatePlaylistImage($playlist->id, $imageData);
            }
        } catch (Exception $imageEx) {
            error_log("Failed to add image to playlist: " . $imageEx->getMessage());
        }
    }
    if (isset($data['clear_liked_songs']) && $data['clear_liked_songs']) {
        $_SESSION['liked_tracks'] = [];
    }
    
    echo json_encode([
        'success' => true,
        'playlist_id' => $playlist->id,
        'playlist_url' => $playlist->external_urls->spotify ?? '#',
        'skipped_tracks' => $skippedTracks ?? 0,
        'cleared_likes' => isset($data['clear_liked_songs']) && $data['clear_liked_songs']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}