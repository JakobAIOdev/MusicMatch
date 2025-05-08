<?php
require_once './includes/session_handler.php';
require_once './vendor/autoload.php';
require_once './includes/spotify_utils.php';

function getRecommendedTracksLastFM($username){
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

$error = '';
$recommendations = null;
$recommendedSongsArray = [];



?>