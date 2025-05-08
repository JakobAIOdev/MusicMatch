<?php
require 'vendor/autoload.php';
include "config.php";
session_start();

/*
$spotify_data = [
    'spotify_id' => $spotify_track->id,
    'spotify_title' => $spotify_track->name,
    'spotify_artist' => isset($spotify_track->artists[0]) ? $spotify_track->artists[0]->name : '',
    'spotify_album' => $spotify_track->album->name,
    'spotify_image' => isset($spotify_track->album->images[0]) ? $spotify_track->album->images[0]->url : '',
    'spotify_url' => $spotify_track->external_urls->spotify,
    'popularity' => $spotify_track->popularity,
    'duration_ms' => $spotify_track->duration_ms
];
*/


function getRecommendedSongs($username) {
    $url = "https://www.last.fm/player/station/user/{$username}/recommended?page=1&ajax=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MusicMatch/1.0)');
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        return ['error' => 'Curl error: ' . curl_error($ch)];
    }
    
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['playlist'])) {
        return ['error' => 'Keine Empfehlungen gefunden oder Fehler beim Dekodieren der Antwort'];
    }
    
    return [
        'success' => true,
        'playlist' => $data['playlist']
    ];
}

//print_r(getRecommendedSongs(("jakobAIO")));

function searchSongOnSpotify($api, $title, $artist) {
    $query = "track:{$title} artist:{$artist}";
    $results = $api->search($query, 'track', ['limit' => 1]);
    if (isset($results->tracks->items[0])) {
        return $results->tracks->items[0];
    }
    else return null;
}

function buildDataArray($spotify_track){
    if(!$spotify_track == null){
        $spotify_data = [
            'spotify_id' => $spotify_track->id,
            'spotify_title' => $spotify_track->name,
            'spotify_artist' => isset($spotify_track->artists[0]) ? $spotify_track->artists[0]->name : '',
            'spotify_album' => $spotify_track->album->name,
            'spotify_image' => isset($spotify_track->album->images[0]) ? $spotify_track->album->images[0]->url : '',
            'spotify_url' => $spotify_track->external_urls->spotify,
            'popularity' => $spotify_track->popularity,
            'duration_ms' => $spotify_track->duration_ms
        ];
        return $spotify_data;
    }
    else return null;
}


$api = new SpotifyWebAPI\SpotifyWebAPI();
$api->setAccessToken($_SESSION['spotify_access_token']);



/*
$testSongSearch = searchSongOnSpotify($api, "GANG GANG", "JACKBOYS");
$testSpotifyConv = buildDataArray($testSongSearch);
print_r($testSpotifyConv);
//print_r($testSongSearch);
*/


$fmRecommendations = getRecommendedSongs("jakobAIO");
print_r($fmRecommendations);

function songDataFmConverted($data){

   if($data['success']){
        $songRecommendations = [];

        foreach($data['playlist'] as $song){
            

        }
   }

}

?>