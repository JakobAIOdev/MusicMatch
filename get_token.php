<?php

// Deine Spotify API Zugangsdaten hier eintragen:
$client_id = '499f3c04f86c48c6a24ae6e3987853b2';
$client_secret = '956177b6040a46b699b143846123ec48';

// Spotify API Token URL
$url = 'https://accounts.spotify.com/api/token';

// cURL vorbereiten
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Header setzen (Client ID und Secret in Base64)
$headers = [
    'Authorization: Basic ' . base64_encode($client_id . ':' . $client_secret),
    'Content-Type: application/x-www-form-urlencoded'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// POST-Felder setzen
curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');

// Anfrage senden und Ergebnis erhalten
$response = curl_exec($ch);
curl_close($ch);

// Ergebnis zurückgeben (JSON)
header('Content-Type: application/json');
echo $response;

?>
