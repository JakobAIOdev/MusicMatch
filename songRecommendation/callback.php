<?php
session_start();

// Spotify API Credentials
$CLIENT_ID = "499f3c04f86c48c6a24ae6e3987853b2";
$CLIENT_SECRET = "956177b6040a46b699b143846123ec48";
$REDIRECT_URI = "http://localhost:8000/callback.php";

// Hole den Code aus der URL
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Tausche den Code gegen ein Access Token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $REDIRECT_URI,
        'client_id' => $CLIENT_ID,
        'client_secret' => $CLIENT_SECRET
    ]));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $token_data = json_decode($response, true);
    
    if (isset($token_data['access_token'])) {
        // Speichere die Token-Daten in der Session
        $_SESSION['spotify_access_token'] = $token_data['access_token'];
        $_SESSION['spotify_refresh_token'] = $token_data['refresh_token'];
        $_SESSION['spotify_token_expires'] = time() + $token_data['expires_in'];
        
        // Weiterleitung zur Recommendations-Seite
        header('Location: recommendations.php');
        exit;
    }
}

// Bei Fehler zurück zur Startseite
header('Location: index.php?error=token_error');
exit;
