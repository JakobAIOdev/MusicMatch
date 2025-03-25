<?php
// spotify_recommendations.php
session_start();

// Überprüfe, ob der Nutzer authentifiziert ist
if (!isset($_SESSION['spotify_access_token'])) {
    die("Nicht authentifiziert. Bitte zuerst einloggen.");
}

$access_token = $_SESSION['spotify_access_token'];

// Funktion zum Abrufen von Daten von der Spotify API
function getSpotifyData($url, $access_token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code != 200) {
        error_log("Spotify API Error: " . $http_code . " - " . $response);
    }
    
    return json_decode($response, true);
}

// Hole Top-Tracks des Nutzers
$top_tracks = getSpotifyData('https://api.spotify.com/v1/me/top/tracks?limit=10', $access_token);

// Hole Audio-Features für Top-Tracks
$track_ids = [];
$track_info = [];

if (isset($top_tracks['items']) && !empty($top_tracks['items'])) {
    foreach ($top_tracks['items'] as $track) {
        $track_ids[] = $track['id'];
        $track_info[$track['id']] = [
            'name' => $track['name'],
            'artist' => $track['artists'][0]['name'],
            'image' => isset($track['album']['images'][0]['url']) ? $track['album']['images'][0]['url'] : '',
            'uri' => $track['uri']
        ];
    }
}

// Wenn keine Top-Tracks gefunden wurden, versuche es mit neuen Releases als Fallback
if (empty($track_ids)) {
    $new_releases = getSpotifyData('https://api.spotify.com/v1/browse/new-releases?limit=10', $access_token);
    
    if (isset($new_releases['albums']['items']) && !empty($new_releases['albums']['items'])) {
        foreach ($new_releases['albums']['items'] as $album) {
            $album_tracks = getSpotifyData('https://api.spotify.com/v1/albums/' . $album['id'] . '/tracks?limit=1', $access_token);
            
            if (isset($album_tracks['items'][0])) {
                $track = $album_tracks['items'][0];
                $track_ids[] = $track['id'];
                $track_info[$track['id']] = [
                    'name' => $track['name'],
                    'artist' => $track['artists'][0]['name'],
                    'image' => isset($album['images'][0]['url']) ? $album['images'][0]['url'] : '',
                    'uri' => $track['uri']
                ];
            }
        }
    }
}

// Hole Audio-Features für die Tracks
$audio_features = [];
if (!empty($track_ids)) {
    $audio_features_url = 'https://api.spotify.com/v1/audio-features?ids=' . implode(',', $track_ids);
    $audio_features_response = getSpotifyData($audio_features_url, $access_token);
    
    if (isset($audio_features_response['audio_features'])) {
        foreach ($audio_features_response['audio_features'] as $feature) {
            if ($feature !== null) {
                $audio_features[$feature['id']] = $feature;
            }
        }
    }
}

// Berechne durchschnittliche Audio-Features
$avg_features = [
    'danceability' => 0,
    'energy' => 0,
    'speechiness' => 0,
    'acousticness' => 0,
    'instrumentalness' => 0,
    'liveness' => 0,
    'valence' => 0
];

$count = 0;
foreach ($audio_features as $feature) {
    foreach ($avg_features as $key => $value) {
        $avg_features[$key] += $feature[$key];
    }
    $count++;
}

if ($count > 0) {
    foreach ($avg_features as $key => $value) {
        $avg_features[$key] = $value / $count;
    }
}

// Hole Empfehlungen basierend auf Audio-Features
$recommendations = [];
if (!empty($track_ids) && $count > 0) {
    // Verwende bis zu 5 Seed-Tracks
    $seed_tracks = array_slice($track_ids, 0, 5);
    
    $query_params = [
        'limit' => 20,
        'seed_tracks' => implode(',', $seed_tracks),
        'target_danceability' => $avg_features['danceability'],
        'target_energy' => $avg_features['energy'],
        'target_speechiness' => $avg_features['speechiness'],
        'target_acousticness' => $avg_features['acousticness'],
        'target_instrumentalness' => $avg_features['instrumentalness'],
        'target_liveness' => $avg_features['liveness'],
        'target_valence' => $avg_features['valence']
    ];
    
    $recommendations_url = 'https://api.spotify.com/v1/recommendations?' . http_build_query($query_params);
    $recommendations_response = getSpotifyData($recommendations_url, $access_token);
    
    if (isset($recommendations_response['tracks'])) {
        $recommendations = $recommendations_response['tracks'];
    }
}

// Wenn immer noch keine Empfehlungen gefunden wurden, versuche es mit Top-Künstlern
if (empty($recommendations)) {
    $top_artists = getSpotifyData('https://api.spotify.com/v1/me/top/artists?limit=5', $access_token);
    
    if (isset($top_artists['items']) && !empty($top_artists['items'])) {
        $artist_ids = [];
        foreach ($top_artists['items'] as $artist) {
            $artist_ids[] = $artist['id'];
        }
        
        if (!empty($artist_ids)) {
            $query_params = [
                'limit' => 20,
                'seed_artists' => implode(',', array_slice($artist_ids, 0, 5))
            ];
            
            $recommendations_url = 'https://api.spotify.com/v1/recommendations?' . http_build_query($query_params);
            $recommendations_response = getSpotifyData($recommendations_url, $access_token);
            
            if (isset($recommendations_response['tracks'])) {
                $recommendations = $recommendations_response['tracks'];
            }
        }
    }
}

// Wenn immer noch keine Empfehlungen gefunden wurden, versuche es mit Genres
if (empty($recommendations)) {
    $available_genres = getSpotifyData('https://api.spotify.com/v1/recommendations/available-genre-seeds', $access_token);
    
    if (isset($available_genres['genres']) && !empty($available_genres['genres'])) {
        // Wähle 5 zufällige Genres
        $random_genres = array_rand(array_flip($available_genres['genres']), min(5, count($available_genres['genres'])));
        
        if (!is_array($random_genres)) {
            $random_genres = [$random_genres];
        }
        
        $query_params = [
            'limit' => 20,
            'seed_genres' => implode(',', $random_genres)
        ];
        
        $recommendations_url = 'https://api.spotify.com/v1/recommendations?' . http_build_query($query_params);
        $recommendations_response = getSpotifyData($recommendations_url, $access_token);
        
        if (isset($recommendations_response['tracks'])) {
            $recommendations = $recommendations_response['tracks'];
        }
    }
}

// Ausgabe der Empfehlungen
?>

<!DOCTYPE html>
<html>
<head>
    <title>Spotify Musikempfehlungen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #121212;
            color: #ffffff;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
        }
        .recommendations {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .track-card {
            width: 200px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            background-color: #282828;
        }
        .track-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .track-info {
            padding: 15px;
        }
        .track-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .track-artist {
            color: #b3b3b3;
            margin-bottom: 10px;
        }
        .no-recommendations {
            text-align: center;
            padding: 50px;
            background-color: #282828;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <h1>Personalisierte Musikempfehlungen</h1>
    
    <?php if (!empty($recommendations)): ?>
        <div class="recommendations">
            <?php foreach ($recommendations as $track): ?>
                <div class="track-card">
                    <?php if (isset($track['album']['images'][0]['url'])): ?>
                        <img src="<?php echo $track['album']['images'][0]['url']; ?>" alt="<?php echo $track['name']; ?>">
                    <?php endif; ?>
                    <div class="track-info">
                        <div class="track-name"><?php echo $track['name']; ?></div>
                        <div class="track-artist"><?php echo $track['artists'][0]['name']; ?></div>
                        <?php if (isset($track['preview_url']) && $track['preview_url']): ?>
                            <audio controls style="width: 100%;">
                                <source src="<?php echo $track['preview_url']; ?>" type="audio/mpeg">
                                Dein Browser unterstützt das Audio-Element nicht.
                            </audio>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-recommendations">
            <p>Leider konnten keine personalisierten Empfehlungen gefunden werden.</p>
        </div>
    <?php endif; ?>
</body>
</html>
