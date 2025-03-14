<?php
// Funktion zum Generieren der Autorisierungs-URL
function getAuthorizationUrl() {
    global $config;
    
    // Prüfe, ob bereits ein State existiert, andernfalls erzeuge einen neuen
    if (isset($_SESSION['pending_spotify_auth_state'])) {
        // Verwende den bestehenden State, wenn der Benutzer erneut auf die Login-Seite gelangt
        $state = $_SESSION['pending_spotify_auth_state'];
    } else {
        // Generiere einen neuen State nur, wenn noch keiner existiert
        $state = bin2hex(random_bytes(16));
        $_SESSION['pending_spotify_auth_state'] = $state;
    }
    
    // Speichere den State-Parameter in beiden Speicherorten
    $_SESSION['spotify_auth_state'] = $state;
    
    // Cookie mit sichereren Optionen setzen und SameSite auf None für Cross-Origin-Anfragen
    setcookie('spotify_auth_state', $state, [
        'expires' => time() + 3600, // 1 Stunde statt 10 Minuten
        'path' => '/',
        'secure' => false, // Auf true für HTTPS setzen
        'httponly' => true,
        'samesite' => 'Lax' // Kann bei Bedarf auf 'None' gesetzt werden
    ]);
    
    // Autorisierungs-URL erstellen
    $params = [
        'client_id' => $config['client_id'],
        'response_type' => 'code',
        'redirect_uri' => $config['redirect_uri'],
        'state' => $state,
        'scope' => $config['scopes']
    ];
    
    return 'https://accounts.spotify.com/authorize?' . http_build_query($params);
}

// Funktion zum Anfordern eines Access Tokens mit Authorization Code
function requestAccessToken($code) {
    global $config;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $config['redirect_uri'],
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret']
    ]));
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

// Funktion zum Erneuern des Access Tokens
function refreshAccessToken() {
    global $config;
    
    if (!isset($_SESSION['refresh_token'])) {
        return false;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'refresh_token',
        'refresh_token' => $_SESSION['refresh_token'],
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret']
    ]));
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($result, true);
    
    if (isset($data['access_token'])) {
        $_SESSION['access_token'] = $data['access_token'];
        $_SESSION['expires_at'] = time() + $data['expires_in'];
        return true;
    }
    
    return false;
}

// Funktion zum Überprüfen, ob der Benutzer authentifiziert ist
function isAuthenticated() {
    return isset($_SESSION['access_token']);
}

// Funktion zum Überprüfen, ob der Token abgelaufen ist
function isTokenExpired() {
    return isset($_SESSION['expires_at']) && time() > $_SESSION['expires_at'];
}

// Funktion zum Abrufen eines gültigen Tokens
function getValidToken() {
    if (!isAuthenticated()) {
        return null;
    }
    
    if (isTokenExpired()) {
        if (!refreshAccessToken()) {
            return null;
        }
    }
    
    return $_SESSION['access_token'];
}
