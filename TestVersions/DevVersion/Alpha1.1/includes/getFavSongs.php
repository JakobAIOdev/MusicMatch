<?php
require_once './includes/session_handler.php';
require_once './vendor/autoload.php';
require_once './includes/spotify_utils.php';


function getTopTracks($api){
    try {
        $topTracks = $api->getMyTop('tracks', [
            'limit' => 50,
            'time_range' => 'short_term' // Last 4 weeks
        ]);

        if (count($topTracks->items) === 0) {
            $topTracks = $api->getMyTop('tracks', [
                'limit' => 50,
                'time_range' => 'medium_term'
            ]);
        }
    } catch (Exception $e) {
        die('Error fetching tracks: ' . $e->getMessage());
    }

    $trackData = [];
    foreach ($topTracks->items as $track) {
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

    $filteredTracks = filterSeenTracks($trackData, 'random');
    shuffle($filteredTracks);
    return json_encode($filteredTracks);
}

?>