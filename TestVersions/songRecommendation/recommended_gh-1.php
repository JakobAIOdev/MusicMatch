<?php
require 'vendor/autoload.php';
session_start();

// Spotify API Konfiguration
$api = new SpotifyWebAPI\SpotifyWebAPI();
$access = $_SESSION['access'];
$api->setAccessToken($access);

// Hole Top-Tracks des Benutzers
$topTracks = $api->getMyTop('tracks', [
    'limit' => 5,
    'time_range' => 'medium_term'
]);

// Extrahiere Track-IDs für Empfehlungen
$seedTracks = [];
foreach ($topTracks->items as $track) {
    $seedTracks[] = $track->id;
    if (count($seedTracks) >= 5) break;
}

// Hole Empfehlungen basierend auf den Top-Tracks
$recommendations = $api->getRecommendations([
    'seed_tracks' => $seedTracks,
    'limit' => 20
]);

// Zeige Empfehlungen an
foreach ($recommendations->tracks as $track) {
    echo $track->name . ' - ' . $track->artists[0]->name . '<br>';
}
