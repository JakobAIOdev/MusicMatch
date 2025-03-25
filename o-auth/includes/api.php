<?php
require_once 'auth.php';

// Funktion zum Abrufen des Benutzerprofils
function getUserProfile() {
    $token = getValidToken();
    if (!$token) {
        return ['error' => ['message' => 'No valid token']];
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com/v1/me');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        return ['error' => ['message' => 'HTTP Error ' . $httpCode]];
    }
    
    return json_decode($result, true);
}

// Funktion zum Abrufen von Empfehlungen mit verbesserter Fehlerbehandlung
function getRecommendations($limit = 20, $seed_tracks = [], $seed_artists = [], $seed_genres = []) {
    $token = getValidToken();
    if (!$token) {
        return ['error' => ['message' => 'No valid token']];
    }
    
    $params = ['limit' => $limit];
    
    // Seed-Parameter hinzufügen, aber nur wenn sie nicht leer sind
    if (!empty($seed_tracks)) {
        $params['seed_tracks'] = is_array($seed_tracks) ? implode(',', array_slice($seed_tracks, 0, 5)) : $seed_tracks;
    }
    
    if (!empty($seed_artists)) {
        $params['seed_artists'] = is_array($seed_artists) ? implode(',', array_slice($seed_artists, 0, 5)) : $seed_artists;
    }
    
    if (!empty($seed_genres)) {
        $params['seed_genres'] = is_array($seed_genres) ? implode(',', array_slice($seed_genres, 0, 5)) : $seed_genres;
    }
    
    // Die Spotify API erfordert mindestens einen Seed-Parameter
    // Wenn keiner angegeben wurde, verwende Standard-Genres
    if (empty($params['seed_tracks']) && empty($params['seed_artists']) && empty($params['seed_genres'])) {
        $params['seed_genres'] = 'pop,rock,hip-hop,electronic,indie';
    }
    
    // Einige zusätzliche Parameter für bessere Ergebnisse
    $params['min_popularity'] = 50; // Nur beliebte Tracks
    
    $ch = curl_init();
    $url = 'https://api.spotify.com/v1/recommendations?' . http_build_query($params);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Debugging-Information speichern
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        return [
            'error' => [
                'message' => 'HTTP Error ' . $httpCode,
                'curl_error' => $curlError,
                'url' => $url
            ]
        ];
    }
    
    return json_decode($result, true);
}

// Funktion zum Abrufen der Top-Tracks des Benutzers mit verbesserter Fehlerbehandlung
function getUserTopTracks($limit = 5, $time_range = 'medium_term') {
    $token = getValidToken();
    if (!$token) {
        return ['error' => ['message' => 'No valid token']];
    }
    
    $params = [
        'limit' => $limit,
        'time_range' => $time_range // short_term, medium_term, long_term
    ];
    
    $ch = curl_init();
    $url = 'https://api.spotify.com/v1/me/top/tracks?' . http_build_query($params);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        // Try with a different time range if medium_term fails
        if ($time_range === 'medium_term') {
            return getUserTopTracks($limit, 'short_term');
        }
        
        return [
            'error' => [
                'message' => 'HTTP Error ' . $httpCode,
                'curl_error' => $curlError,
                'url' => $url
            ]
        ];
    }
    
    return json_decode($result, true);
}

// Funktion zum Erstellen einer Playlist
function createPlaylist($name, $description = '', $public = false) {
    $token = getValidToken();
    if (!$token) {
        return ['error' => ['message' => 'No valid token']];
    }
    
    $profile = getUserProfile();
    if (!$profile || isset($profile['error']) || !isset($profile['id'])) {
        return ['error' => ['message' => 'Could not get user profile']];
    }
    
    $user_id = $profile['id'];
    
    $data = json_encode([
        'name' => $name,
        'description' => $description,
        'public' => $public
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com/v1/users/' . $user_id . '/playlists');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        return ['error' => ['message' => 'HTTP Error ' . $httpCode]];
    }
    
    return json_decode($result, true);
}

// Funktion zum Hinzufügen von Tracks zu einer Playlist
function addTracksToPlaylist($playlist_id, $track_uris) {
    $token = getValidToken();
    if (!$token) {
        return null;
    }
    
    if (!is_array($track_uris)) {
        $track_uris = [$track_uris];
    }
    
    $data = json_encode([
        'uris' => $track_uris
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com/v1/playlists/' . $playlist_id . '/tracks');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}
