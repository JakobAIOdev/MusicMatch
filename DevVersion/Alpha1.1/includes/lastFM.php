<?php
require_once './includes/session_handler.php';
require_once './vendor/autoload.php';
require_once './includes/spotify_utils.php';

function getRecommendedTracksLastFM($api, $username) {
    include './includes/config.php';
    $lastFmUrl = "http://ws.audioscrobbler.com/2.0/?method=user.gettoptracks&user={$username}&api_key={$LastFmApiKey}&format=json&limit=50";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $lastFmUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $lastFmData = json_decode($response, true);
    
    if (!isset($lastFmData['toptracks']) || !isset($lastFmData['toptracks']['track'])) {
        return json_encode([]);
    }
    
    $lastFmTracks = $lastFmData['toptracks']['track'];
    $spotifyTracks = [];
    $seenUris = [];
    
    foreach ($lastFmTracks as $lastTrack) {
        $artist = $lastTrack['artist']['name'];
        $trackName = $lastTrack['name'];
        
        try {
            $searchResult = $api->search("track:{$trackName} artist:{$artist}", 'track', ['limit' => 1]);
            
            if (!empty($searchResult->tracks->items)) {
                $track = $searchResult->tracks->items[0];
                
                if (in_array($track->uri, $seenUris)) {
                    continue;
                }
                
                $seenUris[] = $track->uri;
                
                $spotifyTracks[] = [
                    'id' => $track->id,
                    'name' => $track->name,
                    'artist' => implode(', ', array_map(function ($artist) {
                        return $artist->name;
                    }, $track->artists)),
                    'album' => $track->album->name,
                    'image' => $track->album->images[0]->url ?? 'img/default-album.png',
                    'duration_ms' => $track->duration_ms,
                    'uri' => $track->uri,
                    'spotify_url' => $track->external_urls->spotify ?? '#'
                ];
                
                if (count($spotifyTracks) >= 50) {
                    break;
                }
            }
        } catch (Exception $e) {
            continue;
        }
    }
    shuffle($spotifyTracks);
    $filteredTracks = filterSeenTracks($spotifyTracks, 'lastfm_' . $username);
    
    return json_encode($filteredTracks);
}

?>