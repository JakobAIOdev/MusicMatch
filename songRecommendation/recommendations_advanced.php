<?php
// recommendations_advanced.php
session_start();

// Check if user is authenticated
if (!isset($_SESSION['spotify_access_token'])) {
    die("Not authenticated. Please log in first.");
}

$access_token = $_SESSION['spotify_access_token'];

// Function to make API requests
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
        return ['error' => true, 'status' => $http_code, 'message' => $response];
    }
    
    return json_decode($response, true);
}

// Initialize results array
$results = [];
$successful_methods = [];
$debug_info = [];

// METHOD 1: Get user's saved tracks and use them for recommendations
$saved_tracks = getSpotifyData('https://api.spotify.com/v1/me/tracks?limit=50', $access_token);
$debug_info['saved_tracks'] = isset($saved_tracks['items']) ? count($saved_tracks['items']) : 0;

if (isset($saved_tracks['items']) && !empty($saved_tracks['items'])) {
    $seed_tracks = [];
    foreach ($saved_tracks['items'] as $item) {
        $seed_tracks[] = $item['track']['id'];
        if (count($seed_tracks) >= 5) break;
    }
    
    if (!empty($seed_tracks)) {
        $recommendations_url = 'https://api.spotify.com/v1/recommendations?limit=10&seed_tracks=' . implode(',', array_slice($seed_tracks, 0, 5));
        $recommendations = getSpotifyData($recommendations_url, $access_token);
        
        if (isset($recommendations['tracks']) && !empty($recommendations['tracks'])) {
            $results['saved_tracks_method'] = $recommendations['tracks'];
            $successful_methods[] = 'saved_tracks_method';
        }
    }
}

// METHOD 2: Get user's recently played tracks
$recent_tracks = getSpotifyData('https://api.spotify.com/v1/me/player/recently-played?limit=50', $access_token);
$debug_info['recent_tracks'] = isset($recent_tracks['items']) ? count($recent_tracks['items']) : 0;

if (isset($recent_tracks['items']) && !empty($recent_tracks['items'])) {
    $seed_tracks = [];
    foreach ($recent_tracks['items'] as $item) {
        $seed_tracks[] = $item['track']['id'];
        if (count($seed_tracks) >= 5) break;
    }
    
    if (!empty($seed_tracks)) {
        $recommendations_url = 'https://api.spotify.com/v1/recommendations?limit=10&seed_tracks=' . implode(',', array_slice($seed_tracks, 0, 5));
        $recommendations = getSpotifyData($recommendations_url, $access_token);
        
        if (isset($recommendations['tracks']) && !empty($recommendations['tracks'])) {
            $results['recent_tracks_method'] = $recommendations['tracks'];
            $successful_methods[] = 'recent_tracks_method';
        }
    }
}

// METHOD 3: Try different time ranges for top tracks
$time_ranges = ['short_term', 'medium_term', 'long_term'];
foreach ($time_ranges as $time_range) {
    $top_tracks = getSpotifyData('https://api.spotify.com/v1/me/top/tracks?limit=5&time_range=' . $time_range, $access_token);
    $debug_info['top_tracks_' . $time_range] = isset($top_tracks['items']) ? count($top_tracks['items']) : 0;
    
    if (isset($top_tracks['items']) && !empty($top_tracks['items'])) {
        $seed_tracks = [];
        foreach ($top_tracks['items'] as $track) {
            $seed_tracks[] = $track['id'];
        }
        
        if (!empty($seed_tracks)) {
            $recommendations_url = 'https://api.spotify.com/v1/recommendations?limit=10&seed_tracks=' . implode(',', array_slice($seed_tracks, 0, 5));
            $recommendations = getSpotifyData($recommendations_url, $access_token);
            
            if (isset($recommendations['tracks']) && !empty($recommendations['tracks'])) {
                $results['top_tracks_' . $time_range . '_method'] = $recommendations['tracks'];
                $successful_methods[] = 'top_tracks_' . $time_range . '_method';
            }
        }
    }
}

// METHOD 4: Use audio features from top tracks to find similar songs
$audio_features_method_worked = false;

// First get top tracks
$top_tracks = getSpotifyData('https://api.spotify.com/v1/me/top/tracks?limit=50', $access_token);
if (isset($top_tracks['items']) && !empty($top_tracks['items'])) {
    // Get audio features for top tracks
    $track_ids = [];
    foreach ($top_tracks['items'] as $track) {
        $track_ids[] = $track['id'];
    }
    
    $audio_features_url = 'https://api.spotify.com/v1/audio-features?ids=' . implode(',', array_slice($track_ids, 0, 50));
    $audio_features = getSpotifyData($audio_features_url, $access_token);
    
    if (isset($audio_features['audio_features']) && !empty($audio_features['audio_features'])) {
        // Calculate average audio features
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
        foreach ($audio_features['audio_features'] as $feature) {
            if ($feature !== null) {
                foreach ($avg_features as $key => $value) {
                    $avg_features[$key] += $feature[$key];
                }
                $count++;
            }
        }
        
        if ($count > 0) {
            foreach ($avg_features as $key => $value) {
                $avg_features[$key] = $value / $count;
            }
            
            // Use seed tracks and target audio features
            $seed_tracks = array_slice($track_ids, 0, 2);
            $seed_artists = [];
            
            // Get top artists
            $top_artists = getSpotifyData('https://api.spotify.com/v1/me/top/artists?limit=3', $access_token);
            if (isset($top_artists['items']) && !empty($top_artists['items'])) {
                foreach ($top_artists['items'] as $artist) {
                    $seed_artists[] = $artist['id'];
                }
            }
            
            $query_params = [
                'limit' => 10,
                'target_danceability' => $avg_features['danceability'],
                'target_energy' => $avg_features['energy'],
                'target_speechiness' => $avg_features['speechiness'],
                'target_acousticness' => $avg_features['acousticness'],
                'target_instrumentalness' => $avg_features['instrumentalness'],
                'target_liveness' => $avg_features['liveness'],
                'target_valence' => $avg_features['valence']
            ];
            
            if (!empty($seed_tracks)) {
                $query_params['seed_tracks'] = implode(',', $seed_tracks);
            }
            
            if (!empty($seed_artists)) {
                $query_params['seed_artists'] = implode(',', $seed_artists);
            }
            
            $recommendations_url = 'https://api.spotify.com/v1/recommendations?' . http_build_query($query_params);
            $recommendations = getSpotifyData($recommendations_url, $access_token);
            
            if (isset($recommendations['tracks']) && !empty($recommendations['tracks'])) {
                $results['audio_features_method'] = $recommendations['tracks'];
                $successful_methods[] = 'audio_features_method';
                $audio_features_method_worked = true;
            }
        }
    }
}

// METHOD 5: Use related artists of top artists
if (!$audio_features_method_worked) {
    $top_artists = getSpotifyData('https://api.spotify.com/v1/me/top/artists?limit=5', $access_token);
    $debug_info['top_artists'] = isset($top_artists['items']) ? count($top_artists['items']) : 0;
    
    if (isset($top_artists['items']) && !empty($top_artists['items'])) {
        $related_artists_tracks = [];
        
        foreach ($top_artists['items'] as $artist) {
            $related_artists = getSpotifyData('https://api.spotify.com/v1/artists/' . $artist['id'] . '/related-artists', $access_token);
            
            if (isset($related_artists['artists']) && !empty($related_artists['artists'])) {
                foreach (array_slice($related_artists['artists'], 0, 3) as $related_artist) {
                    $top_tracks = getSpotifyData('https://api.spotify.com/v1/artists/' . $related_artist['id'] . '/top-tracks?country=US', $access_token);
                    
                    if (isset($top_tracks['tracks']) && !empty($top_tracks['tracks'])) {
                        foreach (array_slice($top_tracks['tracks'], 0, 2) as $track) {
                            $related_artists_tracks[] = $track;
                            
                            if (count($related_artists_tracks) >= 10) {
                                break 3;
                            }
                        }
                    }
                }
            }
        }
        
        if (!empty($related_artists_tracks)) {
            $results['related_artists_method'] = $related_artists_tracks;
            $successful_methods[] = 'related_artists_method';
        }
    }
}

// METHOD 6: Get tracks from user's playlists
$user_playlists = getSpotifyData('https://api.spotify.com/v1/me/playlists?limit=50', $access_token);
$debug_info['user_playlists'] = isset($user_playlists['items']) ? count($user_playlists['items']) : 0;

if (isset($user_playlists['items']) && !empty($user_playlists['items'])) {
    $playlist_track_ids = [];
    
    foreach ($user_playlists['items'] as $playlist) {
        if (count($playlist_track_ids) >= 5) break;
        
        $playlist_tracks = getSpotifyData('https://api.spotify.com/v1/playlists/' . $playlist['id'] . '/tracks?limit=10', $access_token);
        
        if (isset($playlist_tracks['items']) && !empty($playlist_tracks['items'])) {
            foreach ($playlist_tracks['items'] as $item) {
                if (isset($item['track']['id'])) {
                    $playlist_track_ids[] = $item['track']['id'];
                    
                    if (count($playlist_track_ids) >= 5) break 2;
                }
            }
        }
    }
    
    if (!empty($playlist_track_ids)) {
        $recommendations_url = 'https://api.spotify.com/v1/recommendations?limit=10&seed_tracks=' . implode(',', array_slice($playlist_track_ids, 0, 5));
        $recommendations = getSpotifyData($recommendations_url, $access_token);
        
        if (isset($recommendations['tracks']) && !empty($recommendations['tracks'])) {
            $results['user_playlists_method'] = $recommendations['tracks'];
            $successful_methods[] = 'user_playlists_method';
        }
    }
}

// Display results
echo "<h1>Personalisierte Musikempfehlungen</h1>";

if (empty($successful_methods)) {
    echo "<p>Leider konnten keine personalisierten Empfehlungen gefunden werden.</p>";
    
    echo "<h2>Debug-Informationen:</h2>";
    echo "<pre>";
    print_r($debug_info);
    echo "</pre>";
} else {
    // Use the first successful method
    $method = $successful_methods[0];
    $tracks = $results[$method];
    
    $method_names = [
        'saved_tracks_method' => 'Basierend auf deinen gespeicherten Tracks',
        'recent_tracks_method' => 'Basierend auf deinen kürzlich gehörten Tracks',
        'top_tracks_short_term_method' => 'Basierend auf deinen aktuellen Lieblingssongs',
        'top_tracks_medium_term_method' => 'Basierend auf deinen Lieblingssongs der letzten Monate',
        'top_tracks_long_term_method' => 'Basierend auf deinen Lieblingssongs aller Zeiten',
        'audio_features_method' => 'Basierend auf den musikalischen Eigenschaften deiner Lieblingssongs',
        'related_artists_method' => 'Basierend auf Künstlern, die deinen Lieblingskünstlern ähnlich sind',
        'user_playlists_method' => 'Basierend auf Songs in deinen Playlists'
    ];
    
    $method_display = isset($method_names[$method]) ? $method_names[$method] : $method;
    
    echo "<p><strong>Empfehlungsmethode:</strong> " . $method_display . "</p>";
    
    echo "<ul>";
    foreach ($tracks as $track) {
        $artist_name = isset($track['artists'][0]['name']) ? $track['artists'][0]['name'] : 'Unbekannter Künstler';
        $album_name = isset($track['album']['name']) ? $track['album']['name'] : 'Unbekanntes Album';
        
        echo "<li>";
        echo "<strong>{$track['name']}</strong> von {$artist_name} (Album: {$album_name})";
        
        if (isset($track['preview_url']) && $track['preview_url']) {
            echo "<br><audio controls style='width: 300px;'>";
            echo "<source src='{$track['preview_url']}' type='audio/mpeg'>";
            echo "Dein Browser unterstützt das Audio-Element nicht.";
            echo "</audio>";
        }
        
        echo "</li>";
    }
    echo "</ul>";
    
    // Show other available methods
    if (count($successful_methods) > 1) {
        echo "<h2>Andere verfügbare Methoden:</h2>";
        echo "<ul>";
        for ($i = 1; $i < count($successful_methods); $i++) {
            $method = $successful_methods[$i];
            $method_display = isset($method_names[$method]) ? $method_names[$method] : $method;
            echo "<li>{$method_display}</li>";
        }
        echo "</ul>";
    }
}
?>
