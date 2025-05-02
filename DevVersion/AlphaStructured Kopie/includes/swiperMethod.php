<?php
require_once './includes/session_handler.php';
require_once './vendor/autoload.php';
require_once './includes/spotify_utils.php';

function filterSeenTracks($tracks) {
    if (!isset($_SESSION['seen_track_ids'])) {
        $_SESSION['seen_track_ids'] = [];
    }
    
    $filteredTracks = [];
    
    foreach ($tracks as $track) {
        if (!in_array($track['id'], $_SESSION['seen_track_ids'])) {
            $filteredTracks[] = $track;
            $_SESSION['seen_track_ids'][] = $track['id'];
        }
    }
    return $filteredTracks;
}

function playlistTracks($api, $playlistLink){

    $playlistId = null;
    if (preg_match('/playlist\/([a-zA-Z0-9]+)/', $playlistLink, $matches)) {
        $playlistId = $matches[1];
    } else {
        die('Invalid playlist link');
    }
    if (!$playlistId) {
        die('Invalid playlist link');
    }
    $playlistTracks = $api->getPlaylistTracks($playlistId);
    if (isset($playlistTracks->error)) {
        die('Error fetching playlist tracks: ' . $playlistTracks->error->message);
    }
    $trackData = [];
    foreach ($playlistTracks->items as $track) {
        $track = $track->track;

        $trackData[] = [
            'uri' => $track->uri,
            'id' => $track->id,
            'name' => $track->name,
            'artist' => implode(', ', array_map(function ($artist) {
                return $artist->name;
            }, $track->artists)),
            'album' => $track->album->name,
            'image' => $track->album->images[0]->url ?? 'img/default-album.png',
            'duration_ms' => $track->duration_ms,
            'spotify_url' => $track->external_urls->spotify ?? '#'
        ];        
    }
    
    $trackData = filterSeenTracks($trackData);
    shuffle($trackData);
    return $tracksJson = json_encode($trackData);
}


function favoritesTracks($api, $time_range){
    $topItems = $api->getMyTop('tracks', [
        'limit' => 50,
        'time_range' => $time_range
    ]);

    $trackData = [];
    foreach ($topItems->items as $item) {
        $trackData[] = [
            'uri' => $item->uri,
            'id' => $item->id,
            'name' => $item->name,
            'artist' => implode(', ', array_map(function ($artist) {
                return $artist->name;
            }, $item->artists)),
            'album' => $item->album->name,
            'image' => $item->album->images[0]->url ?? 'img/default-album.png',
            'duration_ms' => $item->duration_ms,
            'spotify_url' => $item->external_urls->spotify ?? '#'
        ];        
    }
    
    $filteredTracks = filterSeenTracks($trackData);
    
    if (empty($filteredTracks)) {
        $_SESSION['notice'] = "You've already seen all tracks from this category. Showing them again.";
        shuffle($trackData);
        return json_encode($trackData);
    }
    
    shuffle($filteredTracks);
    return json_encode($filteredTracks);
}