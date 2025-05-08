<?php
require 'vendor/autoload.php';
include "config.php";
session_start();


function getRecommendedSongs($username)
{
    $url = "https://www.last.fm/player/station/user/{$username}/recommended?page=1&ajax=1";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MusicMatch/1.0)');
    
    $response = curl_exec($ch);
    if(curl_errno($ch)){
        curl_close($ch);
        echo"Error requesting LastFM!";
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

//print_r(getRecommendedSongs("jakobAIO"));

function convertToFormat($data){
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($_SESSION['spotify_access_token']);
    $dataFormatted = [];
    foreach($data['playlist'] as $song){
        $title = $song['_name'];
        $artist = $song['artists'][0]['_name'];
        //echo "$title by $artist!"."<br>";
        $songSpotify = searchRecommendationSpotify($api, $title, $artist);
        if($songSpotify != null){
            $dataFormatted[] = $songSpotify;
        }
    }
    return $dataFormatted;
}



function searchRecommendationSpotify($api, $title, $artist){
    $query = "track:{$title} artist:{$artist}";
    $results = $api->search($query, 'track', ['limit' => 1]);
    if (isset($results->tracks->items[0])) {
        return $results->tracks->items[0];
    }
    else return null;
}


$data = getRecommendedSongs("jakobAIO");
$formatted = convertToFormat($data);
print_r($formatted);


?>