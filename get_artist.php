<?php

// Token aus der vorherigen Methode holen
$access_token = $_GET['token']; // Token wird per GET übergeben
$artist_id = '6eUKZXaKkcviH0Ku9w2n3V'; // Ed Sheeran's Spotify ID

$url = 'https://api.spotify.com/v1/artists/' . $artist_id;

// cURL vorbereiten
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Header setzen (mit Access Token)
$headers = [
    'Authorization: Bearer ' . $access_token
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Anfrage senden und Ergebnis erhalten
$response = curl_exec($ch);
curl_close($ch);

// Ergebnis zurückgeben (JSON)
header('Content-Type: application/json');
echo $response;

?>
