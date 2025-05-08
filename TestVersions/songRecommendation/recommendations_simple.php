<?php
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
    }
    
    return json_decode($response, true);
}

// Function to post data to Spotify API
function postSpotifyData($url, $data, $access_token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Initialize results array
$results = [];
$successful_methods = [];

// METHOD 1: Get recommendations based on user's top tracks
$top_tracks = getSpotifyData('https://api.spotify.com/v1/me/top/tracks?limit=5&time_range=short_term', $access_token);
if (isset($top_tracks['items']) && !empty($top_tracks['items'])) {
    $seed_tracks = [];
    foreach ($top_tracks['items'] as $track) {
        $seed_tracks[] = $track['id'];
    }
    
    if (!empty($seed_tracks)) {
        $recommendations_url = 'https://api.spotify.com/v1/recommendations?limit=10&seed_tracks=' . implode(',', array_slice($seed_tracks, 0, 5));
        $recommendations = getSpotifyData($recommendations_url, $access_token);
        
        if (isset($recommendations['tracks']) && !empty($recommendations['tracks'])) {
            $results['top_tracks_method'] = $recommendations['tracks'];
            $successful_methods[] = 'top_tracks_method';
        }
    }
}

// METHOD 2: Get recommendations based on user's top artists
$top_artists = getSpotifyData('https://api.spotify.com/v1/me/top/artists?limit=5&time_range=short_term', $access_token);
if (isset($top_artists['items']) && !empty($top_artists['items'])) {
    $seed_artists = [];
    foreach ($top_artists['items'] as $artist) {
        $seed_artists[] = $artist['id'];
    }
    
    if (!empty($seed_artists)) {
        $recommendations_url = 'https://api.spotify.com/v1/recommendations?limit=10&seed_artists=' . implode(',', array_slice($seed_artists, 0, 5));
        $recommendations = getSpotifyData($recommendations_url, $access_token);
        
        if (isset($recommendations['tracks']) && !empty($recommendations['tracks'])) {
            $results['top_artists_method'] = $recommendations['tracks'];
            $successful_methods[] = 'top_artists_method';
        }
    }
}

// METHOD 3: Get recommendations from popular playlists (Global Top 50)
$global_top_50 = getSpotifyData('https://api.spotify.com/v1/playlists/37i9dQZEVXbMDoHDwVN2tF', $access_token);
if (isset($global_top_50['tracks']['items']) && !empty($global_top_50['tracks']['items'])) {
    $popular_tracks = [];
    foreach ($global_top_50['tracks']['items'] as $item) {
        $popular_tracks[] = $item['track'];
    }
    
    if (!empty($popular_tracks)) {
        $results['global_top_50_method'] = array_slice($popular_tracks, 0, 10);
        $successful_methods[] = 'global_top_50_method';
    }
}

// METHOD 4: Get recommendations from available genre seeds
$available_genres = getSpotifyData('https://api.spotify.com/v1/recommendations/available-genre-seeds', $access_token);
if (isset($available_genres['genres']) && !empty($available_genres['genres'])) {
    // Select 5 random genres
    $random_genres = array_rand(array_flip($available_genres['genres']), min(5, count($available_genres['genres'])));
    
    if (!is_array($random_genres)) {
        $random_genres = [$random_genres];
    }
    
    $recommendations_url = 'https://api.spotify.com/v1/recommendations?limit=10&seed_genres=' . implode(',', $random_genres);
    $recommendations = getSpotifyData($recommendations_url, $access_token);
    
    if (isset($recommendations['tracks']) && !empty($recommendations['tracks'])) {
        $results['genre_seeds_method'] = $recommendations['tracks'];
        $successful_methods[] = 'genre_seeds_method';
    }
}

// METHOD 5: Get recommendations from category playlists (e.g., "toplists")
$categories = getSpotifyData('https://api.spotify.com/v1/browse/categories?limit=50', $access_token);
if (isset($categories['categories']['items']) && !empty($categories['categories']['items'])) {
    foreach ($categories['categories']['items'] as $category) {
        if ($category['id'] == 'toplists') {
            $category_playlists = getSpotifyData('https://api.spotify.com/v1/browse/categories/' . $category['id'] . '/playlists?limit=10', $access_token);
            
            if (isset($category_playlists['playlists']['items']) && !empty($category_playlists['playlists']['items'])) {
                $playlist = $category_playlists['playlists']['items'][0];
                $playlist_tracks = getSpotifyData('https://api.spotify.com/v1/playlists/' . $playlist['id'] . '/tracks?limit=10', $access_token);
                
                if (isset($playlist_tracks['items']) && !empty($playlist_tracks['items'])) {
                    $toplist_tracks = [];
                    foreach ($playlist_tracks['items'] as $item) {
                        $toplist_tracks[] = $item['track'];
                    }
                    
                    $results['toplists_method'] = $toplist_tracks;
                    $successful_methods[] = 'toplists_method';
                    break;
                }
            }
        }
    }
}

// METHOD 6: Get new releases as fallback
$new_releases = getSpotifyData('https://api.spotify.com/v1/browse/new-releases?limit=10', $access_token);
if (isset($new_releases['albums']['items']) && !empty($new_releases['albums']['items'])) {
    $new_release_tracks = [];
    
    foreach ($new_releases['albums']['items'] as $album) {
        $album_tracks = getSpotifyData('https://api.spotify.com/v1/albums/' . $album['id'] . '/tracks?limit=1', $access_token);
        
        if (isset($album_tracks['items'][0])) {
            $track = getSpotifyData('https://api.spotify.com/v1/tracks/' . $album_tracks['items'][0]['id'], $access_token);
            if (isset($track['id'])) {
                $new_release_tracks[] = $track;
                
                if (count($new_release_tracks) >= 10) {
                    break;
                }
            }
        }
    }
    
    if (!empty($new_release_tracks)) {
        $results['new_releases_method'] = $new_release_tracks;
        $successful_methods[] = 'new_releases_method';
    }
}

// Display results
echo "<h1>Recommended Songs</h1>";

if (empty($successful_methods)) {
    echo "<p>No recommendations found with any method. Please try again later.</p>";
} else {
    // Use the first successful method
    $method = $successful_methods[0];
    $tracks = $results[$method];
    
    echo "<p>Method used: " . str_replace('_method', '', $method) . "</p>";
    echo "<ul>";
    foreach ($tracks as $track) {
        $artist_name = isset($track['artists'][0]['name']) ? $track['artists'][0]['name'] : 'Unknown Artist';
        echo "<li>{$track['name']} by {$artist_name}</li>";
    }
    echo "</ul>";
    
    // Show other available methods
    if (count($successful_methods) > 1) {
        echo "<h2>Other available methods:</h2>";
        echo "<ul>";
        for ($i = 1; $i < count($successful_methods); $i++) {
            echo "<li>" . str_replace('_method', '', $successful_methods[$i]) . "</li>";
        }
        echo "</ul>";
    }
}
