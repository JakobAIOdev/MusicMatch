<?php
require_once './includes/session_handler.php';
require_once './vendor/autoload.php';
require_once './includes/spotify_utils.php';

function getRecommendedTracksLastFM($api, $username) {
    $lastFmUrl = "https://www.last.fm/player/station/user/{$username}/recommended?page=1&ajax=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $lastFmUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MusicMatch/1.0)');
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        return json_encode(['error' => 'Curl error: ' . curl_error($ch)]);
    }
    
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return json_encode(['error' => 'JSON parsing error: ' . json_last_error_msg()]);
    }
    
    if (!isset($data['playlist']) || !is_array($data['playlist'])) {
        return json_encode(['error' => 'Invalid LastFM response format']);
    }
    
    $trackData = [];
    foreach ($data['playlist'] as $song) {
        $songName = $song['name'] ?? $song['_name'] ?? '';
        $artistName = '';
        
        if (isset($song['artists']) && !empty($song['artists'])) {
            $artistName = $song['artists'][0]['name'] ?? $song['artists'][0]['_name'] ?? '';
        }
        
        if (empty($songName) || empty($artistName)) {
            continue;
        }
        try {
            $searchResults = $api->search("track:{$songName} artist:{$artistName}", 'track', ['limit' => 1]);
            
            if (isset($searchResults->tracks->items[0])) {
                $track = $searchResults->tracks->items[0];
                
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
        } catch (Exception $e) {
            error_log("Error searching Spotify for {$songName} by {$artistName}: " . $e->getMessage());
        }
    }
    $filteredTracks = filterSeenTracks($trackData, 'lastfm_' . $username);
    shuffle($filteredTracks);
    
    return json_encode($filteredTracks);
}
?>