<?php
require_once './includes/session_handler.php';
require_once './vendor/autoload.php';
require_once './includes/spotify_utils.php';

function filterSeenTracks($tracks, $mode) {
    if (!isset($_SESSION['global_seen_track_ids'])) {
        $_SESSION['global_seen_track_ids'] = [];
    }
    $sessionKey = 'seen_track_ids_' . $mode;
    if (!isset($_SESSION[$sessionKey])) {
        $_SESSION[$sessionKey] = [];
    }
    $filteredTracks = [];
    
    foreach ($tracks as $track) {
        if (!in_array($track['id'], $_SESSION['global_seen_track_ids'])) {
            $filteredTracks[] = $track;
            $_SESSION[$sessionKey][] = $track['id'];
            $_SESSION['global_seen_track_ids'][] = $track['id'];
        }
    }
    
    if (empty($filteredTracks) && !empty($tracks)) {
        $_SESSION['notice'] = "You've seen all tracks from this category. Showing them again.";
        $_SESSION[$sessionKey] = [];
        return $tracks;
    }
    
    return $filteredTracks;
}

function playlistTracks($api, $playlistLink){
    // Extract playlist ID
    $playlistId = null;
    if (preg_match('/playlist\/([a-zA-Z0-9]+)/', $playlistLink, $matches)) {
        $playlistId = $matches[1];
    } else {
        die('Invalid playlist link');
    }
    
    if (!$playlistId) {
        die('Invalid playlist link');
    }
    
    $trackData = getPlaylistSongs($api, $playlistId);
    
    $trackData = filterSeenTracks($trackData, 'playlist_' . $playlistId);
    shuffle($trackData);
    return json_encode($trackData);
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
    
    $filteredTracks = filterSeenTracks($trackData, 'favorites_' . $time_range);
    shuffle($filteredTracks);
    return json_encode($filteredTracks);
}

function billboardHot100($api){
    $playlistId = '6UeSakyzhiEt4NB3UAd6NQ';
    $trackData =  getPlaylistSongs($api, $playlistId);
    $trackData = filterSeenTracks($trackData, 'playlist_' . $playlistId);
    shuffle($trackData);
    return json_encode($trackData);
}

function mostStreamed100($api){
    $playlistId = '5ABHKGoOzxkaa28ttQV9sE';
    $trackData =  getPlaylistSongs($api, $playlistId);
    $trackData = filterSeenTracks($trackData, 'playlist_' . $playlistId);
    shuffle($trackData);
    return json_encode($trackData);
}

function getPlaylistSongs($api, $playlistId){
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
    return $trackData;
}

function artistDiscography($api, $artistName) {
    $artistResult = $api->search($artistName, 'artist', ['limit' => 1]);
    
    if (empty($artistResult->artists->items)) {
        die('Artist not found');
    }
    
    $artist = $artistResult->artists->items[0];
    $artistId = $artist->id;
    
    $albums = $api->getArtistAlbums($artistId, [
        'limit' => 50, 
        'include_groups' => 'album,single'
    ]);
    
    $trackData = [];
    $processedTrackIds = [];
    foreach ($albums->items as $album) {
        $albumTracks = $api->getAlbumTracks($album->id);
        
        foreach ($albumTracks->items as $track) {
            if (!in_array($track->id, $processedTrackIds)) {
                $processedTrackIds[] = $track->id;
                
                $trackData[] = [
                    'uri' => $track->uri,
                    'id' => $track->id,
                    'name' => $track->name,
                    'artist' => implode(', ', array_map(function ($artist) {
                        return $artist->name;
                    }, $track->artists)),
                    'album' => $album->name,
                    'image' => $album->images[0]->url ?? 'img/default-album.png',
                    'duration_ms' => $track->duration_ms,
                    'spotify_url' => $track->external_urls->spotify ?? '#'
                ];
            }
        }
    }
    $filteredTracks = filterSeenTracks($trackData, 'artist_' . $artistId);
    shuffle($filteredTracks);
    return json_encode($filteredTracks);
}