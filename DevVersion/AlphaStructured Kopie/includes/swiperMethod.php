<?php
require_once './includes/session_handler.php';
require_once './vendor/autoload.php';
require_once './includes/spotify_utils.php';

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
    return $tracksJson = json_encode($trackData);
}