<?php

use GeminiAPI\Client;
use GeminiAPI\Resources\Parts\TextPart;
use SpotifyWebAPI\SpotifyWebAPI;

require 'vendor/autoload.php';
include "config.php";
session_start();

if (!isset($_SESSION['spotify_access_token'])) {
    header('Location: index.php');
    exit;
}

$api = new SpotifyWebAPI();
$api->setAccessToken($_SESSION['spotify_access_token']);


$topTracks = $api->getMyTop('tracks', ['limit' => 50, 'time_range' => 'short_term']); // short_term is approximately last 4 weeks

// Initialize array to store favorite songs
$favoriteSongs = [];

// Add top tracks to favorites (only tracks from the last 4 weeks)
foreach ($topTracks->items as $track) {
    $songName = $track->name;
    $artistName = $track->artists[0]->name;

    $key = $songName . ' - ' . $artistName;
    $favoriteSongs[$key] = [
        'name' => $songName,
        'artist' => $artistName
    ];
}

// Convert associative array to indexed array
$favoriteSongs = array_values($favoriteSongs);

print_r($favoriteSongs);

