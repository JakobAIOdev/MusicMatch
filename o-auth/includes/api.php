<?php
require_once 'auth.php';

// Funktion zum Abrufen des Benutzerprofils
function getUserProfile() {
    $token = getValidToken();
    if (!$token) {
        return null;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com/v1/me');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

// Funktion zum Abrufen von Empfehlungen
function getRecommendations($limit = 20, $seed_tracks = null, $seed_artists = null, $seed_genres = null) {
    $token = getValidToken();
    if (!$token) {
        return null;
    }
    
    $params = ['limit' => $limit];
    
    // Seed-Parameter hinzufügen, falls vorhanden
    if ($seed_tracks) {
        $params['seed_tracks'] = is_array($seed_tracks) ? implode(',', $seed_tracks) : $seed_tracks;
    }
    
    if ($seed_artists) {
        $params['seed_artists'] = is_array($seed_artists) ? implode(',', $seed_artists) : $seed_artists;
    }
    
    if ($seed_genres) {
        $params['seed_genres'] = is_array($seed_genres) ? implode(',', $seed_genres) : $seed_genres;
    }
    
    // Wenn keine Seeds angegeben wurden, verwende einige Standard-Genres
    if (!$seed_tracks && !$seed_artists && !$seed_genres) {
        $params['seed_genres'] = 'pop,rock,hip-hop,electronic,indie';
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com/v1/recommendations?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

// Funktion zum Abrufen der Top-Tracks des Benutzers
function getUserTopTracks($limit = 5, $time_range = 'medium_term') {
    $token = getValidToken();
    if (!$token) {
        return null;
    }
    
    $params = [
        'limit' => $limit,
        'time_range' => $time_range // short_term, medium_term, long_term
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com/v1/me/top/tracks?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

// Funktion zum Erstellen einer Playlist
function createPlaylist($name, $description = '', $public = false) {
    $token = getValidToken();
    if (!$token) {
        return null;
    }
    
    $profile = getUserProfile();
    if (!$profile || !isset($profile['id'])) {
        return null;
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
    curl_close($ch);
    
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
