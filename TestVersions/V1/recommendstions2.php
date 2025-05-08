<?php
// Display all PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session with cookie parameters that ensure session works
session_start([
    'cookie_lifetime' => 86400, // 1 day
    'cookie_path' => '/',
    'cookie_secure' => false, // Set to true if using HTTPS
    'cookie_httponly' => true
]);

// DEBUG MODE - Show session information
echo "<div style='background: #333; color: #fff; padding: 15px; margin: 10px; border-radius: 5px;'>";
echo "<h3>DEBUG INFORMATION</h3>";
echo "<p>Current page: recommendstions2.php</p>";
echo "<p>Session active: " . (session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No') . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<pre>SESSION DATA: ";
print_r($_SESSION);
echo "</pre>";
echo "<p>Access token exists: " . (isset($_SESSION['access_token']) ? 'Yes' : 'No') . "</p>";
echo "</div>";

require_once 'config.php';

// Check if user is logged in with valid Spotify tokens
if (!isset($_SESSION['access_token'])) {
    echo "<div style='background: #f44336; color: #fff; padding: 15px; margin: 10px; border-radius: 5px;'>";
    echo "<p><strong>Error:</strong> No access token in session. You need to login first.</p>";
    echo "<p><a href='login.php' style='color: #fff; text-decoration: underline;'>Click here to login</a></p>";
    echo "</div>";
    exit();
}

// Use the current access token
$access_token = $_SESSION['access_token'];

// Check if token has expired
if (isset($_SESSION['token_expiry']) && $_SESSION['token_expiry'] <= time()) {
    // Refresh token if expired
    if (!isset($_SESSION['refresh_token'])) {
        // No refresh token available, redirect to login
        $_SESSION['error_message'] = "Your session has expired. Please log in again.";
        header('Location: login.php');
        exit();
    }
    
    $refresh_token = $_SESSION['refresh_token'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'refresh_token',
        'refresh_token' => $refresh_token
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($CLIENT_ID . ':' . $CLIENT_SECRET)
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $response_data = json_decode($response, true);
    
    if ($http_code == 200 && isset($response_data['access_token'])) {
        $_SESSION['access_token'] = $response_data['access_token'];
        $_SESSION['token_expiry'] = time() + $response_data['expires_in'];
        $access_token = $_SESSION['access_token'];
    } else {
        // Token refresh failed, redirect to login
        $_SESSION['error_message'] = "Session expired. Please log in again.";
        header('Location: login.php');
        exit();
    }
}

// Function to make Spotify API requests
function spotifyRequest($url, $access_token, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

// Get user profile
$user_profile = spotifyRequest('https://api.spotify.com/v1/me', $access_token);

echo "<div style='background: #333; color: #fff; padding: 15px; margin: 10px; border-radius: 5px;'>";
echo "<h3>API TEST RESULTS</h3>";
echo "<p>User Profile API Status: " . $user_profile['status'] . "</p>";
if ($user_profile['status'] != 200) {
    echo "<p>Error response: <pre>" . json_encode($user_profile['data'], JSON_PRETTY_PRINT) . "</pre></p>";
}
echo "</div>";

// If API request fails, handle the error
if ($user_profile['status'] != 200) {
    $_SESSION['error_message'] = "Failed to connect to Spotify. Please log in again.";
    // Log the error for debugging
    error_log("Spotify API error: " . json_encode($user_profile));
    header('Location: login.php');
    exit();
}

// Get user's top artists (medium term = ~6 months)
$top_artists_response = spotifyRequest('https://api.spotify.com/v1/me/top/artists?time_range=medium_term&limit=5', $access_token);
$top_artists = [];

if ($top_artists_response['status'] == 200 && isset($top_artists_response['data']['items'])) {
    $top_artists = $top_artists_response['data']['items'];
}

// Get user's top tracks
$top_tracks_response = spotifyRequest('https://api.spotify.com/v1/me/top/tracks?time_range=medium_term&limit=5', $access_token);
$top_tracks = [];

if ($top_tracks_response['status'] == 200 && isset($top_tracks_response['data']['items'])) {
    $top_tracks = $top_tracks_response['data']['items'];
}

// Get available genres
$genres_response = spotifyRequest('https://api.spotify.com/v1/recommendations/available-genre-seeds', $access_token);
$available_genres = [];

if ($genres_response['status'] == 200 && isset($genres_response['data']['genres'])) {
    $available_genres = $genres_response['data']['genres'];
}

// Extract seed data for recommendations
$seed_artists = [];
$seed_tracks = [];
$seed_genres = [];

// Get seed artists (max 2)
foreach (array_slice($top_artists, 0, 2) as $artist) {
    $seed_artists[] = $artist['id'];
}

// Get seed tracks (max 2)
foreach (array_slice($top_tracks, 0, 2) as $track) {
    $seed_tracks[] = $track['id'];
}

// Extract genres from top artists (max 1)
if (!empty($top_artists)) {
    $artist_genres = [];
    foreach ($top_artists as $artist) {
        if (isset($artist['genres'])) {
            $artist_genres = array_merge($artist_genres, $artist['genres']);
        }
    }
    
    // Find genres that match Spotify's available genres
    $matching_genres = array_intersect($artist_genres, $available_genres);
    
    if (!empty($matching_genres)) {
        $seed_genres[] = reset($matching_genres);
    } elseif (!empty($available_genres)) {
        // Fallback to a popular genre
        $popular_genres = ['pop', 'rock', 'hip hop', 'dance'];
        foreach ($popular_genres as $genre) {
            if (in_array($genre, $available_genres)) {
                $seed_genres[] = $genre;
                break;
            }
        }
    }
}

// Get recommendations based on seeds
$recommendations = [];
if (!empty($seed_artists) || !empty($seed_tracks) || !empty($seed_genres)) {
    $recommendation_url = 'https://api.spotify.com/v1/recommendations?limit=20';
    
    if (!empty($seed_artists)) {
        $recommendation_url .= '&seed_artists=' . implode(',', $seed_artists);
    }
    
    if (!empty($seed_tracks)) {
        $recommendation_url .= '&seed_tracks=' . implode(',', $seed_tracks);
    }
    
    if (!empty($seed_genres)) {
        $recommendation_url .= '&seed_genres=' . implode(',', $seed_genres);
    }
    
    $recommendations_response = spotifyRequest($recommendation_url, $access_token);
    
    if ($recommendations_response['status'] == 200 && isset($recommendations_response['data']['tracks'])) {
        $recommendations = $recommendations_response['data']['tracks'];
    }
}

// Get user's saved tracks to check which recommended songs are already saved
$saved_tracks_ids = [];
if (!empty($recommendations)) {
    $track_ids = array_map(function($track) {
        return $track['id'];
    }, $recommendations);
    
    // Split into chunks of 50 (API limit)
    $track_chunks = array_chunk($track_ids, 50);
    
    foreach ($track_chunks as $chunk) {
        $tracks_check_url = 'https://api.spotify.com/v1/me/tracks/contains?ids=' . implode(',', $chunk);
        $tracks_check_response = spotifyRequest($tracks_check_url, $access_token);
        
        if ($tracks_check_response['status'] == 200) {
            foreach ($tracks_check_response['data'] as $index => $is_saved) {
                if ($is_saved) {
                    $saved_tracks_ids[] = $chunk[$index];
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify Empfehlungen - MusicMatch</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #121212;
            color: #fff;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            padding: 20px 0;
            border-bottom: 1px solid #333;
        }
        .user-profile {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .user-profile img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
        }
        h1 {
            color: #1DB954;
            margin-bottom: 30px;
        }
        h2 {
            color: #fff;
            font-size: 1.5rem;
            margin: 25px 0 15px;
        }
        .preferences-section {
            margin-bottom: 40px;
        }
        .artist-list, .track-list {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }
        .artist-card, .track-card {
            background: #282828;
            border-radius: 8px;
            overflow: hidden;
            width: 200px;
            transition: all 0.3s ease;
        }
        .artist-card:hover, .track-card:hover {
            background: #333;
            transform: translateY(-5px);
        }
        .artist-card img, .track-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .artist-info, .track-info {
            padding: 15px;
        }
        .artist-name, .track-title {
            font-weight: 600;
            margin: 0 0 5px;
            font-size: 1rem;
        }
        .track-artist {
            color: #b3b3b3;
            font-size: 0.9rem;
            margin: 0;
        }
        .recommendations {
            margin-top: 40px;
        }
        .recommendations-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .recommendation-card {
            background: #282828;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        .recommendation-card:hover {
            background: #333;
        }
        .album-cover {
            width: 80px;
            height: 80px;
            margin-right: 15px;
        }
        .song-details {
            flex: 1;
        }
        .song-title {
            font-weight: 600;
            margin: 0 0 5px;
        }
        .song-artist {
            color: #b3b3b3;
            font-size: 0.9rem;
            margin: 0 0 5px;
        }
        .song-album {
            color: #b3b3b3;
            font-size: 0.8rem;
            margin: 0;
        }
        .action-buttons {
            display: flex;
            margin-top: 15px;
        }
        .play-btn, .save-btn {
            border: none;
            padding: 8px 12px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        .play-btn {
            background: #1DB954;
            color: #fff;
        }
        .play-btn:hover {
            background: #1ed760;
        }
        .save-btn {
            background: transparent;
            color: #fff;
            border: 1px solid #b3b3b3;
        }
        .save-btn:hover {
            border-color: #fff;
        }
        .saved {
            color: #1DB954;
            border-color: #1DB954;
        }
        .no-data {
            color: #b3b3b3;
            text-align: center;
            margin: 50px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="user-profile">
                <?php if (isset($user_profile['data']['images']) && !empty($user_profile['data']['images'])): ?>
                    <img src="<?= htmlspecialchars($user_profile['data']['images'][0]['url']) ?>" alt="Profile photo">
                <?php endif; ?>
                <h2>Hallo, <?= htmlspecialchars($user_profile['data']['display_name'] ?? 'Musikliebhaber') ?>!</h2>
            </div>
        </header>

        <main>
            <h1>Deine personalisierten Musikempfehlungen</h1>

            <section class="preferences-section">
                <h2>Deine Top Künstler</h2>
                <div class="artist-list">
                    <?php if (empty($top_artists)): ?>
                        <p class="no-data">Wir haben noch keine Daten zu deinen liebsten Künstlern.</p>
                    <?php else: ?>
                        <?php foreach ($top_artists as $artist): ?>
                            <div class="artist-card">
                                <?php if (!empty($artist['images'])): ?>
                                    <img src="<?= htmlspecialchars($artist['images'][0]['url']) ?>" alt="<?= htmlspecialchars($artist['name']) ?>">
                                <?php endif; ?>
                                <div class="artist-info">
                                    <p class="artist-name"><?= htmlspecialchars($artist['name']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h2>Deine Top Tracks</h2>
                <div class="track-list">
                    <?php if (empty($top_tracks)): ?>
                        <p class="no-data">Wir haben noch keine Daten zu deinen liebsten Tracks.</p>
                    <?php else: ?>
                        <?php foreach ($top_tracks as $track): ?>
                            <div class="track-card">
                                <?php if (!empty($track['album']['images'])): ?>
                                    <img src="<?= htmlspecialchars($track['album']['images'][0]['url']) ?>" alt="<?= htmlspecialchars($track['name']) ?>">
                                <?php endif; ?>
                                <div class="track-info">
                                    <p class="track-title"><?= htmlspecialchars($track['name']) ?></p>
                                    <p class="track-artist"><?= htmlspecialchars($track['artists'][0]['name']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="recommendations">
                <h2>Songs, die dir gefallen könnten</h2>
                <div class="recommendations-list">
                    <?php if (empty($recommendations)): ?>
                        <p class="no-data">Wir konnten keine Empfehlungen für dich finden. Höre mehr Musik auf Spotify, um personalisierte Vorschläge zu erhalten.</p>
                    <?php else: ?>
                        <?php foreach ($recommendations as $track): ?>
                            <div class="recommendation-card">
                                <?php if (!empty($track['album']['images'])): ?>
                                    <img class="album-cover" src="<?= htmlspecialchars($track['album']['images'][0]['url']) ?>" alt="Album Cover">
                                <?php endif; ?>
                                <div class="song-details">
                                    <p class="song-title"><?= htmlspecialchars($track['name']) ?></p>
                                    <p class="song-artist"><?= htmlspecialchars($track['artists'][0]['name']) ?></p>
                                    <p class="song-album"><?= htmlspecialchars($track['album']['name']) ?></p>
                                    
                                    <div class="action-buttons">
                                        <button class="play-btn" data-uri="<?= $track['uri'] ?>">Abspielen</button>
                                        <button class="save-btn <?= in_array($track['id'], $saved_tracks_ids) ? 'saved' : '' ?>" 
                                                data-id="<?= $track['id'] ?>" 
                                                data-saved="<?= in_array($track['id'], $saved_tracks_ids) ? 'true' : 'false' ?>">
                                            <?= in_array($track['id'], $saved_tracks_ids) ? 'Gespeichert' : 'Speichern' ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle play button clicks
            const playButtons = document.querySelectorAll('.play-btn');
            playButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const uri = this.getAttribute('data-uri');
                    playTrack(uri);
                });
            });

            // Handle save button clicks
            const saveButtons = document.querySelectorAll('.save-btn');
            saveButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const trackId = this.getAttribute('data-id');
                    const isSaved = this.getAttribute('data-saved') === 'true';
                    
                    if (isSaved) {
                        removeFromLibrary(trackId, this);
                    } else {
                        saveToLibrary(trackId, this);
                    }
                });
            });

            // Function to play a track using Spotify Web Playback SDK or redirect
            function playTrack(uri) {
                // Option 1: Open in Spotify
                window.open(uri, '_blank');
                
                // Option 2: If you implement Spotify Web Playback SDK
                // This would require additional setup
            }

            // Function to save track to library
            function saveToLibrary(trackId, buttonElement) {
                fetch('spotify_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=save&track_id=${trackId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        buttonElement.classList.add('saved');
                        buttonElement.textContent = 'Gespeichert';
                        buttonElement.setAttribute('data-saved', 'true');
                    } else {
                        alert('Fehler beim Speichern des Tracks: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ein Fehler ist aufgetreten');
                });
            }

            // Function to remove track from library
            function removeFromLibrary(trackId, buttonElement) {
                fetch('spotify_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove&track_id=${trackId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        buttonElement.classList.remove('saved');
                        buttonElement.textContent = 'Speichern';
                        buttonElement.setAttribute('data-saved', 'false');
                    } else {
                        alert('Fehler beim Entfernen des Tracks: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ein Fehler ist aufgetreten');
                });
            }
        });
    </script>
    
    <!-- Add this script to create the missing spotify_actions.php handler -->
    <script>
        // Check if spotify_actions.php exists
        fetch('spotify_actions.php', {method: 'HEAD'})
        .catch(error => {
            console.warn("spotify_actions.php not found. Save/Remove functionality will not work.");
            
            // Override functions to show alert instead of making requests to missing file
            window.saveToLibrary = function(trackId, buttonElement) {
                alert('Save functionality not available. Please ask your developer to create the spotify_actions.php file.');
            };
            
            window.removeFromLibrary = function(trackId, buttonElement) {
                alert('Remove functionality not available. Please ask your developer to create the spotify_actions.php file.');
            };
        });
    </script>
</body>
</html>
